<?php

use Logic\Admin\BaseController;
use Logic\Recharge\Recharge;
use Model\FundsDealLog;
use Model\Dml;
use Logic\Admin\Log;
use Logic\Set\SystemConfig;

return new class() extends BaseController {
    const STATE = '';
    const TITLE = '手动增加股东分红余额';
    const DESCRIPTION = '';
    const HINT = '';
    const QUERY = [];
    
    const PARAMS = [
        'uid'          => 'int(required) #用户id',
        'amount'       => 'int(required) #变动金额',
        'memo'         => 'string() #备注',
    ];
    const SCHEMAS = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run() {
        $params = $this->request->getParams();

        $validate = new \lib\validate\BaseValidate([
            'uid'    => 'require|isPositiveInteger',
            'amount' => 'require|isPositiveInteger',
        ]);
        //超过50W  不允许 加钱  以防出错
        if($params['amount'] > 50000000){
            return $this->lang->set(11021);
        }
        $validate->paramsCheck('', $this->request, $this->response);

        try{
            //判断pin密码 和 大额需要审核
            $this->validPinPasswordAndMoney($params);
        }catch (\Exception $e){
            return $this->lang->set($e->getCode(),[$e->getMessage()]);
        }

        $recharge = new Recharge($this->ci);

        $rs = $recharge->increase(
            $params['uid'],
            $params['amount'],
            $params['memo'],
            $this->playLoad['uid']
        );
        if(!$rs) return $this->lang->set(-2);

        $user = \Model\Admin\User::find($params['uid']);
        $user->setTarget($user->id,$user->name);
        $user->logs_type = '手动增加股东分红余额';
        $user->opt_desc = '金额(' . ($params['amount'] / 100) . ')';
        $user->log();


        return $this->lang->set(0);

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
        if($param['amount'] >= bcmul($admin_pin_password_config['add_share_balance'],100,0)){
            $user    = \Model\Admin\User::find($param['uid']);
            $balance = \Model\Admin\Funds::where('id',$user->wallet_id)->value('balance');
            $data = [
                'user_id'           => $param['uid'],
                'username'          => $user->name,
                'money'             => (int)$param['amount'],
                'balance'           => $balance,
                'coupon'            => 0,
                'memo'              => $param['memo']??'',
                'type'              => 13,
                'wallet_type'       => 2,
                'withdraw_bet'      => 0,
                'apply_admin_uid'   => $this->playLoad['uid'],
            ];

            \DB::table('funds_manual_check')->insert($data);
            throw new \Exception('',11052);
        }

    }
};
