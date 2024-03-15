<?php

use Logic\Admin\BaseController;
use lib\validate\admin\DepositValidate;
use Model\Admin\UserData;
use Logic\Admin\Log;
use Logic\GameApi\Common;
use Logic\Set\SystemConfig;

return new class() extends BaseController {
    const STATE = '';
    const TITLE = '手动增加\减少可提余额';
    const DESCRIPTION = '';
    
    const QUERY = [];

    const PARAMS = [
        'id'     => 'int(required) #用户id',
        'type'   => 'string(required) #increase 增加，decrease 减少',
        'amount' => 'int(required) #变动金额',
        'memo'   => 'string() #备注',
    ];
    const SCHEMAS = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run($id = '') {
        $this->checkID($id);

        (new DepositValidate())->paramsCheck('freemoney', $this->request, $this->response);

        $params = $this->request->getParams();

        return $this->updateFreeMoneyByUserId($id, $params);
    }

    /**
     * 更改可提余额
     *
     * @param $id
     * @param $params
     *
     * @return mixed
     */
    protected function updateFreeMoneyByUserId($id, $params) {
        if ($params['amount'] <= 0) {
            return $this->lang->set(10555);
        }

        $user = (new Common($this->ci))->getUserInfo($id);

        if (!$user) {
            return $this->lang->set(10046);
        }

        $userdata = UserData::where('user_id',$id)->first();
        $balance = \DB::table('funds')
                      ->where('id', $user['wallet_id'])
                      ->value('balance');

        $memo = isset($params['memo']) ? $params['memo'] : '';

        if ($params['type'] == 'increase') {
            //增加可提余额
            $canChangeFreeMoney = $balance - $userdata->free_money > 0 ? $balance - $userdata->free_money : 0;
            if ($canChangeFreeMoney == 0 || $params['amount'] > $canChangeFreeMoney) {
                return $this->lang->set(10552);
            }

            try{
                $params['uid'] = $id;
                //判断pin密码 和 大额需要审核
                $this->validPinPasswordAndMoney($params);
            }catch (\Exception $e){
                return $this->lang->set($e->getCode(),[$e->getMessage()]);
            }

            $changeFreeMoney = $params['amount'] <= $canChangeFreeMoney ? $params['amount'] : $canChangeFreeMoney;
            $userdata->free_money = $userdata->free_money + $changeFreeMoney;
            $memo = empty($memo) ? '厅主后台手动增加可提余额' : $memo;
            $deal_type = \Model\FundsDealLog::TYPE_INCREASE_FREEMONEY_MANUAL;
        } else {
            if ($userdata->total_bet >= $userdata->total_require_bet) {
                return $this->lang->set(10554);
            }

            //减少可提余额
            $canChangeFreeMoney = $userdata->free_money;
            if ($canChangeFreeMoney == 0 || $params['amount'] > $canChangeFreeMoney) {
                return $this->lang->set(10553);
            }

            $changeFreeMoney = $params['amount'] <= $canChangeFreeMoney ? $params['amount'] : $canChangeFreeMoney;

            $userdata->free_money = $userdata->free_money - $changeFreeMoney;

            $memo = empty($memo) ? '厅主后台手动减少可提余额' : $memo;
            $deal_type = \Model\FundsDealLog::TYPE_DECREASE_FREEMONEY_MANUAL;
        }

        $result = $userdata->save();
        if ($result) {
            $order_number = \Model\LotteryOrder::generateOrderNumber();
            // 增加资金流水
            \Model\FundsDealLog::create([
                'user_id'           => $id,
                'user_type'         => 1,
                'username'          => $user['name'],
                'admin_id'          => $this->playLoad['uid'],
                'admin_user'        => $this->playLoad['nick'],
                'order_number'      => $order_number,
                'deal_type'         => $deal_type,
                'deal_category'     => $params['type'] == 'increase' ? \Model\FundsDealLog::CATEGORY_INCOME : \Model\FundsDealLog::CATEGORY_COST,
                'deal_money'        => $changeFreeMoney,
                'balance'           => $balance,
                'memo'              => $memo,
                'wallet_type'       => \Model\FundsDealLog::WALLET_TYPE_PRIMARY,
                'total_require_bet' => $userdata->total_require_bet,
                'free_money'        => $userdata->free_money,
            ]);
            if(!$params['memo']){
                $params['memo'] = $params['type'] == 'increase' ? '厅主后台手动增加可提余额' : '厅主后台手动减少可提余额';
            }
            // 增加手动入款记录
            \Model\FundsDealManual::create([
                'user_id'       => $id,
                'username'      => $user['name'],
                'user_type'     => 1,
                'type'          => $params['type'] == 'increase' ? 11 : 12,
                'trade_no'      => $order_number,
                'operator_type' => 1,
                'front_money'   => intval($balance),
                'money'         => $changeFreeMoney,
                'balance'       => intval($balance),
                'admin_uid'     => $this->playLoad['uid'],
                'wallet_type'   => 1,
                'memo'          => $params['memo'],
                'withdraw_bet'  => 0,
            ]);

            $LogicModel = new Model\Admin\LogicModel;
            $LogicModel->setTarget($id,$user['name']);
            $LogicModel->logs_type = $params['type'] == 'increase' ? '增加可提余额' : '减少可提余额';
            $changeFreeMoney = $changeFreeMoney/100;
            $LogicModel->opt_desc = '金额('.$changeFreeMoney.')';
            $LogicModel->log();

            return $this->lang->set(0);
        }

        return $this->lang->set(-2);
    }

    /**
     * 判断pin密码
     * 大额提款转审核
     * @param $param
     */
    public function validPinPasswordAndMoney($param){
        $admin_pin_password_config = SystemConfig::getModuleSystemConfig('admin_pin_password');
        //判断pin密码
        if(isset($admin_pin_password_config['status']) && $admin_pin_password_config['status'] == 1){
            \Logic\Admin\Admin::validPinPassword($this->playLoad['uid'], $param['pin_pw']??'');
        }

        //大额判断
        if($param['amount'] >= bcmul($admin_pin_password_config['add_free_money'],100,0)){
            $user    = \Model\Admin\User::find($param['uid']);
            $balance = \Model\Admin\Funds::where('id',$user->wallet_id)->value('balance');
            $data = [
                'user_id'           => $param['uid'],
                'username'         => $user->name,
                'money'             => (int)$param['amount'],
                'balance'           => $balance,
                'coupon'            => 0,
                'memo'              => $param['memo']??'',
                'type'              => 11,
                'wallet_type'       => 1,
                'withdraw_bet'      => 0,
                'apply_admin_uid'   => $this->playLoad['uid'],
            ];

            \DB::table('funds_manual_check')->insert($data);
            throw new \Exception('',11052);
        }

    }
};
