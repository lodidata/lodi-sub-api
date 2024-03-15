<?php
namespace Logic\Recharge\Traits;
use DB;
use Logic\Admin\Message;
trait RechargeAddActivity{

    /**
     * 手动领取活动
     * @param int    $userId        [description]
     * @param int    $activeId      [description]
     * @param string $memo          [description]
     * @param [type] $currentUserId [description]
     */
    public function addActivity(
        $userId,
        $activeId,
        $memo,
        $currentUserId = 0
    ) {

        $userType = 1;
        try {

            $memo = empty($memo) ? $this->lang->text("The main and back office of the hall gives out the activity discount") : $memo;
            $this->db->getConnection()->beginTransaction();
            $wallet = new \Logic\Wallet\Wallet($this->ci);
            $user = \Model\User::where('id', $userId)->first();

            // 判断是否禁止会员优惠
            if (in_array('refuse_sale', explode(',', $user['auth_status']))) {
                $this->db->getConnection()->rollback();
                return $this->lang->set(201);
            }

            // 锁定钱包
            $oldFunds = \Model\Funds::where('id', $user['wallet_id'])->lockForUpdate()->first();
            $active = \Model\ActiveApply::where('active_id', $activeId)
                ->where('status', 'pending')
                ->where('state','manual')->where('user_id', $userId)
                ->orderBy('created','desc')
                ->first();
            if (empty($active)) {
                $this->db->getConnection()->rollback();
                return $this->lang->set(147);
            }

            // 锁定活动申请凭证
            $active = \Model\ActiveApply::where('id', $active['id'])->lockForUpdate()->first();
            if (empty($active) || $active['status'] != 'pending') {
                $this->db->getConnection()->rollback();
                return $this->lang->set(149);
            }

            $couponMoney = $active['coupon_money'];
            $withdrawBet = $active['withdraw_require'];
            // 加钱
            $wallet->crease($user['wallet_id'], $couponMoney);

            $funds = \Model\Funds::where('id', $user['wallet_id'])->first();

            // 修改活动领取状态
            \Model\ActiveApply::where('user_id', $userId)
                                ->where('active_id', $activeId)
                                ->update(['status' => 'pass']);

            //添加打码量记录
            $dml = new \Model\Dml();
            $dml->addDml($userId,$withdrawBet,$active['coupon_money'],$this->lang->text("Additional coding amount for free activities"));

            //流水里面添加打码量可提余额等信息
            $dml = new \Logic\Wallet\Dml($this->ci);
            $dmlData =$dml->getUserDmlData((int)$userId,(int)$withdrawBet,2);

            \Model\FundsDealLog::create([
                "user_id" => $user['id'],
                "user_type" => 1,
                "username" => $user['name'],
                "deal_type" => \Model\FundsDealLog::TYPE_ACTIVITY,
                "deal_category" => \Model\FundsDealLog::CATEGORY_INCOME,
                "deal_money" => $couponMoney,
                "balance" => intval($funds['balance']),
                "memo" => $memo,
                "wallet_type" => \Model\FundsDealLog::WALLET_TYPE_PRIMARY,
                'total_bet'=>$dmlData->total_bet,
                'withdraw_bet'=> $withdrawBet,
                'total_require_bet'=>$dmlData->total_require_bet,
                'free_money'=>$dmlData->free_money
            ]);
            $tradeNo = date("YmdHis").rand(pow(10, 3), pow(10, 4) - 1);
            // 增加手动入款记录
            \Model\FundsDealManual::create([
                'user_id'       => $userId,
                'username'      => $user['name'],
                'user_type'     => $userType,
                'type'          => 5,
                'trade_no'      => $tradeNo,
                'operator_type' => 1,
                'front_money'   => intval($oldFunds['balance']),
                'money'         => $couponMoney,
                'balance'       => intval($funds['balance']),
                'admin_uid'     => $currentUserId,
                'wallet_type'   => 1,
                'memo'          => $memo,
                'withdraw_bet'  => $withdrawBet,
            ]);

            // 存款状态修改
            \Model\FundsDeposit::where('user_id', $userId)
                                ->where('money','>',0)
                                ->whereRaw("(active_id = $activeId  or active_id_other = $activeId)")
                                ->update([
                                    'coupon_money' => DB::raw("ifnull(coupon_money,0) + $couponMoney")
                                ]);

            $rule=DB::table("active_rule")->where('active_id',$activeId)->first();
            if(!empty($rule->game_type)){
                $gameType=explode(',',$rule->game_type);
                foreach($gameType as $value){
                    $registerData=array(
                        'user_id'=>$user['id'],
                        'active_id'=>$activeId,
                        'amount'=>$couponMoney,
                        'game_type'=>$value
                    );
                    DB::table("active_register")->insert($registerData);
                }
            }

            $this->db->getConnection()->commit();
            $this->logger->info('addActivity 成功', [
                'user_id'          => $userId,
                'user_name'        => $user['name'],
                'coupon_money'     => $couponMoney,
                'withdraw_require' => $withdrawBet,
                'active_id'        => $activeId,
                'memo'             => $memo,

                "user_type" => 1,
                "username" => $user['name'],
                "deal_type" => \Model\FundsDealLog::TYPE_ACTIVITY_MANUAL,
                "deal_category" => \Model\FundsDealLog::CATEGORY_INCOME,
                "deal_money" => $couponMoney,
                "balance" => intval($funds['balance']),
                "wallet_type" => \Model\FundsDealLog::WALLET_TYPE_PRIMARY,
                "ip" => \Utils\Client::getIp(),
            ]);
            $content  = ['Dear %s, %s yuan from %s has arrived', $user['name'], ($couponMoney/100), $active['active_name']];
            $insertId = $this->messageAddByMan('Activity gift', $user['name'], $content);
            (new Message($this->ci))->messagePublish($insertId);
            return $this->lang->set(146);
        } catch (\Exception $e) {
            $this->logger->error('addActivity 出错:'.$e->getMessage(), compact('userId', 'couponMoney', 'withdrawBet', 'activeId', 'memo', 'currentUserId'));
            return $this->lang->set(147, [], [], ['error' => $e->getMessage()]);
        }
    }


    /**
     * 手动领取活动
     * @param int    $userId        [description]
     * @param int    $activeId      [description]
     */
    public function updateActivity($userId, $activeApplyId ,$tradeId ,$memo,$tradeNo = '') {

        $userType = 1;
        try {

            $memo = empty($memo) ? $this->lang->text("The main and back office of the hall gives out the activity discount") : $memo;
            $this->db->getConnection()->beginTransaction();
            $wallet = new \Logic\Wallet\Wallet($this->ci);
            $user = \Model\User::where('id', $userId)->first();

            $auth_status = explode(',', $user['auth_status']);
            // 判断是否禁止会员优惠
            if (in_array('refuse_sale', $auth_status)) {
                $this->db->getConnection()->rollback();
                return $this->lang->set(201);
            }

            // 锁定活动申请凭证
            $active = \Model\ActiveApply::where('id', $activeApplyId)->lockForUpdate()->first();
            if (empty($active) || $active['state'] != 'manual' ||  $active['status'] != 'pending') {
                $this->db->getConnection()->rollback();
                return $this->lang->set(149);
            }

            // 锁定钱包
            $oldFunds = \Model\Funds::where('id', $user['wallet_id'])->lockForUpdate()->first();

            $couponMoney = $active['coupon_money'];
            $withdrawBet = $active['withdraw_require'];
            // 加钱
            $wallet->crease($user['wallet_id'], $couponMoney);

            $funds = \Model\Funds::where('id', $user['wallet_id'])->first();

            // 修改活动领取状态
            \Model\ActiveApply::where('id', $activeApplyId)->update(['status' => 'pass']);

            //添加打码量记录
            $dml = new \Model\Dml();
            $dml->addDml($userId,$withdrawBet,$active['coupon_money'],$this->lang->text("The main and back office of the hall gives out the activity discount"));

            //流水里面添加打码量可提余额等信息
            $dml = new \Logic\Wallet\Dml($this->ci);
            $dmlData =$dml->getUserDmlData((int)$userId,(int)$withdrawBet,2);

            \Model\FundsDealLog::create([
                "user_id" => $user['id'],
                "user_type" => 1,
                "username" => $user['name'],
                "deal_type" => \Model\FundsDealLog::TYPE_ACTIVITY_MANUAL,
                "deal_category" => \Model\FundsDealLog::CATEGORY_INCOME,
                "order_number" => $tradeNo,
                "deal_money" => $couponMoney,
                "balance" => intval($funds['balance']),
                "memo" => $memo,
                "wallet_type" => \Model\FundsDealLog::WALLET_TYPE_PRIMARY,
                'total_bet'=>$dmlData->total_bet,
                'withdraw_bet'=> $withdrawBet,
                'total_require_bet'=>$dmlData->total_require_bet,
                'free_money'=>$dmlData->free_money
            ]);
            $tradeNo = date("YmdHis").rand(pow(10, 3), pow(10, 4) - 1);
            // 增加手动入款记录
            \Model\FundsDealManual::create([
                'user_id'       => $userId,
                'username'      => $user['name'],
                'user_type'     => $userType,
                'type'          => 5,
                'trade_no'      => $tradeNo,
                'operator_type' => 1,
                'front_money'   => intval($oldFunds['balance']),
                'money'         => $couponMoney,
                'balance'       => intval($funds['balance']),
                'wallet_type'   => 1,
                'memo'          => $memo,
                'withdraw_bet'  => $withdrawBet,
            ]);

            // 存款状态修改
            \Model\FundsDeposit::where('user_id', $userId)
                ->where('id',$tradeId)
                ->update([
                    'coupon_money' => DB::raw("ifnull(coupon_money,0) + $couponMoney")
                ]);

            $this->db->getConnection()->commit();
            $this->logger->info('addActivity 成功', [
                'user_id'          => $userId,
                'user_name'        => $user['name'],
                'coupon_money'     => $couponMoney,
                'withdraw_require' => $withdrawBet,
                'active_id'        => $activeApplyId,
                'memo'             => $memo,
                "user_type" => 1,
                "username" => $user['name'],
                "deal_type" => \Model\FundsDealLog::TYPE_ACTIVITY_MANUAL,
                "deal_category" => \Model\FundsDealLog::CATEGORY_INCOME,
                "deal_money" => $couponMoney,
                "balance" => intval($funds['balance']),
                "wallet_type" => \Model\FundsDealLog::WALLET_TYPE_PRIMARY,
                "ip" => \Utils\Client::getIp(),
            ]);
            $content  = ["Dear %s, Hello! Receive %s yuan from %s by hand", $user['name'], ($couponMoney/100), $active['active_name']];
            $insertId = $this->messageAddByMan("Recharge gift", $user['name'], $content);
            (new Message($this->ci))->messagePublish($insertId);
            return $this->lang->set(146);
        } catch (\Exception $e) {
            $this->logger->error('addActivity 出错:'.$e->getMessage(), compact('userId', 'couponMoney', 'withdrawBet', 'activeId', 'memo', 'currentUserId'));
            return $this->lang->set(147, [], [], ['error' => $e->getMessage()]);
        }
    }
}