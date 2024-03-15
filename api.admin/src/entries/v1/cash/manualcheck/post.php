<?php

use Logic\Admin\BaseController;
use Logic\Admin\Log;
use Logic\GameApi\Common;
use Model\Admin\UserData;
use Model\Dml;
use Logic\Recharge\Recharge;
use Logic\Set\SystemConfig;
use Model\FundsDealLog;
use Model\FundsDealManual;

return new class() extends BaseController {
    const STATE = '';
    const TITLE = '大额加款同意或拒绝';
    const DESCRIPTION = '';

    const QUERY = [
    ];

    const PARAMS = [
        'id'        => 'array(required) ',
        'status'    => 'int() 0:待处理，1：同意，2：拒绝',

    ];
    const SCHEMAS = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run() {
        $param  = $this->request->getParams();
        $id     = $param['id'];
        $status = $param['status'];

        if($id && is_array($id)){
            foreach ($id as $v){
                if($v <= 0) continue;
                $info = \DB::table('funds_manual_check')
                    ->where(['status' =>0])
                    ->find($v,['user_id', 'money','coupon','withdraw_bet','type','memo','apply_admin_uid']);

                $res = \DB::table('funds_manual_check')
                    ->where(['status' => 0, 'id' => $v])
                    ->update(['status' => $status,'admin_uid' => $this->playLoad['uid'],'confirm_time' => date('Y-m-d H:i:s')]);
                $info && $info=(array)$info;
                //更新成功
                if($res){
                    //同意 给钱
                    if($status == 1){
                        $apply_admin = \DB::table('admin_user')
                            ->where('id',$info['apply_admin_uid'])
                            ->value('username');

                        switch ($info['type']){
                            case FundsDealManual::MANUAl_DEPOSIT://手动存款
                                $this->deposit($info['user_id'], $info['money'], $info['coupon'],$info['withdraw_bet'],$info['memo'],$apply_admin);
                                break;
                            case FundsDealManual::MANUAl_ADDMONEY://手动增加余额
                                $this->increaseMoney($info['user_id'], $info['money'],$info['withdraw_bet'],$info['memo'],$apply_admin);
                                break;
                            case FundsDealManual::MANUAl_INCREASE_FREEMONEY://手动增加可提余额
                                $this->addFreeMoney($info['user_id'], $info['money'],$info['memo'],$apply_admin);
                                break;
                            case FundsDealManual::WITHDRAWAL_INCREASE_SHARE://手动增加股东分红余额
                                $this->addShareMoney($info['user_id'], $info['money'],$info['memo'],$apply_admin);
                                break;
                            case FundsDealManual::MANUAl_INCREASE_DIRECT://手动增加直推金额
                                $this->increaseDirect($info['user_id'], $info['money'],(int)$info['withdraw_bet'],$info['memo'],$this->playLoad['uid'],$apply_admin);
                                break;
                        }
                    }
                }
            }
        }

        return $this->lang->set(0);
    }

    //手动存款
    public function deposit($userId,$amount,$discount,$playCode,$memo,$applyAdmin){
        $re = (new \Logic\Recharge\Recharge($this->ci))->handPassDeposit($userId, $amount, 0, $memo ?? null, true, \Utils\Client::getIp(), $this->playLoad['uid'], $playCode ?? 0, $discount ?? 0,$applyAdmin);

        if(!$re){
            return $this->lang->set(-2);
        }
        //幸运轮盘充值赠送免费抽奖次数
        $wallet = new \Logic\Wallet\Wallet($this->ci);
        $wallet->luckycode($userId);

        $user = \Model\Admin\User::find($userId);
        $user->setTarget($user->id,$user->name);
        $user->logs_type = '手工存款';
        $user->opt_desc = '金额(' . ($amount / 100) . ')打码量(' . ($playCode / 100) . ')优惠(' . ($discount / 100) . ')';
        $user->log();


        if ($playCode > 0) {
            //添加打码量到dml表
            $dml = new  \Model\Dml();
            $dml->addDml($userId, $playCode, $amount + $discount, '后台人工加钱添加打码量');
        }
        return $this->lang->set(0);
    }

    //手动增加余额
    public function increaseMoney($userId, $amount, $playCode, $memo, $applyAdmin){
        $recharge = (new \Logic\Recharge\Recharge($this->ci));

        $rs = $recharge->tzHandRecharge(
            $userId,
            $amount,
            $playCode ?? 0,
            $memo,
            $this->playLoad['uid'],
            FundsDealLog::TYPE_ADDMONEY_MANUAL,
            $applyAdmin
        );
        if(!$rs) return $this->lang->set(-2);

        $user = \Model\Admin\User::find($userId);
        $user->setTarget($user->id,$user->name);
        $user->logs_type = '手动增加余额';
        $user->opt_desc = '金额(' . ($amount / 100) . ')打码量('.$playCode.')';
        $user->log();

        //添加打码量到dml表
        if ($playCode > 0) {
            $dml = new Dml();
            $dml->addDml($userId, $playCode, $amount, '后台厅主手动增加余额添加打码量');

            return $this->lang->set(0);
        }
    }

    //增加可提余额
    public function addFreeMoney($userId, $amount, $memo,$applyAdmin){

        $user = (new Common($this->ci))->getUserInfo($userId);

        if (!$user) {
            return $this->lang->set(10046);
        }

        $userdata = UserData::where('user_id',$userId)->first();
        $balance = \DB::table('funds')
            ->where('id', $user['wallet_id'])
            ->value('balance');

        $memo = isset($memo) ? $memo : '';

        //增加可提余额
        $canChangeFreeMoney = $balance - $userdata->free_money > 0 ? $balance - $userdata->free_money : 0;
        if ($canChangeFreeMoney == 0 || $amount > $canChangeFreeMoney) {
            return $this->lang->set(10552);
        }

        $changeFreeMoney = $amount <= $canChangeFreeMoney ? $amount : $canChangeFreeMoney;
        $userdata->free_money = $userdata->free_money + $changeFreeMoney;
        $memo = empty($memo) ? '厅主后台手动增加可提余额' : $memo;
        $deal_type = \Model\FundsDealLog::TYPE_INCREASE_FREEMONEY_MANUAL;

        $result = $userdata->save();
        if ($result) {
            $order_number = \Model\LotteryOrder::generateOrderNumber();
            // 增加资金流水
            \Model\FundsDealLog::create([
                'user_id'           => $userId,
                'user_type'         => 1,
                'username'          => $user['name'],
                'admin_id'          => $this->playLoad['uid'],
                'admin_user'        => $this->playLoad['nick'],
                'order_number'      => $order_number,
                'deal_type'         => $deal_type,
                'deal_category'     => \Model\FundsDealLog::CATEGORY_INCOME,
                'deal_money'        => $changeFreeMoney,
                'balance'           => $balance,
                'memo'              => $memo,
                'wallet_type'       => \Model\FundsDealLog::WALLET_TYPE_PRIMARY,
                'total_require_bet' => $userdata->total_require_bet,
                'free_money'        => $userdata->free_money,
            ]);
            if(!$memo){
                $memo = '厅主后台手动增加可提余额';
            }
            // 增加手动入款记录
            \Model\FundsDealManual::create([
                'user_id'       => $userId,
                'username'      => $user['name'],
                'user_type'     => 1,
                'type'          => FundsDealManual::MANUAl_INCREASE_FREEMONEY,
                'trade_no'      => $order_number,
                'operator_type' => 1,
                'front_money'   => intval($balance),
                'money'         => $changeFreeMoney,
                'balance'       => intval($balance),
                'admin_uid'     => $this->playLoad['uid'],
                'wallet_type'   => 1,
                'memo'          => $memo."--{$applyAdmin}",
                'withdraw_bet'  => 0,
            ]);

            $LogicModel = new Model\Admin\LogicModel;
            $LogicModel->setTarget($userId, $user['name']);
            $LogicModel->logs_type = '增加可提余额';
            $changeFreeMoney = $changeFreeMoney/100;
            $LogicModel->opt_desc = '金额('.$changeFreeMoney.')';
            $LogicModel->log();

            return $this->lang->set(0);
        }

        return $this->lang->set(-2);
    }

    //手动增加股东分红余额
    public function addShareMoney($userId, $amount, $memo, $applyAdmin){
        $recharge = (new \Logic\Recharge\Recharge($this->ci));

        $rs = $recharge->increase(
            $userId,
            $amount,
            $memo,
            $this->playLoad['uid'],
            $applyAdmin
        );
        if(!$rs) return $this->lang->set(-2);

        $user = \Model\Admin\User::find($userId);
        $user->setTarget($user->id, $user->name);
        $user->logs_type = '手动增加股东分红余额';
        $user->opt_desc = '金额(' . ($amount / 100) . ')';
        $user->log();


        return $this->lang->set(0);
    }

    //手动增加直推余额
    public function increaseDirect(int $userId,int $amount, int $play_code, string $memo,int $currentUserId,string $applyAdmin=''){
        //$user = \Model\User::where('id', $userId)->first();
        $user = (new Common($this->ci))->getUserInfo($userId);
        $default_memo = "Manually increase the direct balance";
        if (!$memo) {
            $memo = $this->lang->text($default_memo);
        }

        try {
            //添加打码量可提余额等信息
            $dml = new \Logic\Wallet\Dml($this->ci);
            $dmlData = $dml->getUserDmlData($userId, $play_code, 2);

            $this->db->getConnection()
                ->beginTransaction();
            $wallet = new \Logic\Wallet\Wallet($this->ci);

            // 锁定钱包
            $oldFunds = \Model\Funds::where('id', $user['wallet_id'])
                ->lockForUpdate()
                ->first();
            $wallet->crease($user['wallet_id'],$amount,4);

            $tradeNo=FundsDealLog::generateDealNumber();

            if (isset($GLOBALS['playLoad'])) {
                $admin_id = $GLOBALS['playLoad']['uid'];
                $admin_name = $GLOBALS['playLoad']['nick'];
            } else {
                $admin_id = 0;
                $admin_name = '';
            }

            $funds = \Model\Funds::where('id', $user['wallet_id'])->first();

            // 增加资金流水
            FundsDealLog::create([
                'user_id'           => $userId,
                'user_type'         => 1,
                'username'          => $user['name'],
                'order_number'      => $tradeNo,
                'deal_type'         => FundsDealLog::TYPE_INCREASE_DIRECT_MANUAL,
                'deal_category'     => FundsDealLog::CATEGORY_INCOME,
                'deal_money'        => $amount,
                'balance'           => intval($funds['direct_balance']),
                'memo'              => $this->lang->text($default_memo),
                'wallet_type'       => FundsDealLog::WALLET_TYPE_PRIMARY,
                'total_bet'         => $dmlData->total_bet,
                'withdraw_bet'      => $play_code,
                'total_require_bet' => $dmlData->total_require_bet,
                'free_money'        => $dmlData->free_money,

                'admin_id'   => $admin_id,
                'admin_user' => $admin_name,
            ]);

            FundsDealManual::create([
                'user_id'       => $userId,
                'username'      => $user['name'],
                'user_type'     => 1,
                'type'          => FundsDealManual::MANUAl_INCREASE_DIRECT,
                'trade_no'      => $tradeNo,
                'operator_type' => 1,
                'front_money'   => intval($oldFunds['direct_balance']),
                'money'         => $amount,
                'balance'       => intval($funds['direct_balance']),
                'admin_uid'     => $currentUserId,
                'wallet_type'   => 1,
                'memo'          => $applyAdmin ? $memo."--{$applyAdmin}" : $memo,
                'withdraw_bet'  => $play_code,
            ]);

            $this->db->getConnection()
                ->commit();

            if ($play_code > 0) {
                //添加打码量到dml表
                $dml = new  \Model\Dml();
                $dml->addDml($userId, $play_code, $amount, '手动增加直推余额添加打码量');
            }
            return true;
        }catch(\Exception $e){
            $this->logger->error('手动增加直推余额错误',$e->getMessage());
            $this->db->getConnection()
                ->rollback();
            return false;
        }
    }
};
