<?php

use Logic\Admin\BaseController;
use Logic\Admin\Log;
use Logic\Set\SystemConfig;

return new class() extends BaseController {
    const STATE = '';
    const TITLE = '手动存款';
    const DESCRIPTION = '';

    const QUERY = [
        'amount'    => 'int',
        'bank_no'   => 'string',
        'discount'  => 'int',
        'memo'      => 'string',
        'play_code' => 'int',
        'role'      => 'int',
        'uid'       => 'int',
        'wid'       => 'int',
    ];

    const PARAMS = [
        'uid'       => 'int(required) #用户id',
        'wid'       => 'int() #钱包id',
        'role'      => 'int #角色，1 会员，2 代理',
        'amount'    => 'int(required) #存款金额',
        'bank_no'   => 'string() #银行账号',
        'memo'      => 'string() #备注',
        'play_code' => 'int() #打码量',
        'discount'  => 'int() #优惠金额',
        'pin_pw'    => 'string() #pin密码',
    ];
    const SCHEMAS = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run() {
        $param = $this->request->getParams();

        $validate = new \lib\validate\BaseValidate([
            'uid'       => 'require|isPositiveInteger',
            'amount'    => 'require|egt:0',
            'play_code' => 'require|egt:0',
            'discount'  => 'require|egt:0',
        ], [
            'amount'    => '存入金额必须大于等于0',
            'play_code' => '打码量必须大于等于0',
            'discount'  => '优惠金额必须大于等于0',
        ]);
        //超过50W  不允许 加钱  以防出错
        if($param['amount'] > 50000000){
            return $this->lang->set(11021);
        }
        $validate->paramsCheck('', $this->request, $this->response);
        $param['status'] = 1;

        try{
            //判断pin密码 和 大额需要审核
            $this->validPinPasswordAndMoney($param);
        }catch (\Exception $e){
            return $this->lang->set($e->getCode(),[$e->getMessage()]);
        }

        $re = (new \Logic\Recharge\Recharge($this->ci))->handPassDeposit($param['uid'], $param['amount'], 0, $param['memo'] ?? null, $param['status'], \Utils\Client::getIp(), $this->playLoad['uid'], $param['play_code'] ?? 0, $param['discount'] ?? 0);

        if(!$re){
            return $this->lang->set(-2);
        }
        //幸运轮盘充值赠送免费抽奖次数
        $wallet = new \Logic\Wallet\Wallet($this->ci);
        $wallet->luckycode($param['uid']);

        $user = \Model\Admin\User::find($param['uid']);
        $user->setTarget($user->id,$user->name);
        $user->logs_type = '手工存款';
        $user->opt_desc = '金额(' . ($param['amount'] / 100) . ')打码量(' . ($param['play_code'] / 100) . ')优惠(' . ($param['discount'] / 100) . ')';
        $user->log();


        if ($param['play_code'] > 0) {
            //添加打码量到dml表
            $dml = new  \Model\Dml();
            $dml->addDml($param['uid'], $param['play_code'], $param['amount'] + $param['discount'], '后台人工加钱添加打码量');
        }
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
        if($param['amount'] + $param['discount'] >= bcmul($admin_pin_password_config['manual_deposit'],100,0)){
            $user    = \Model\Admin\User::find($param['uid']);
            $balance = \Model\Admin\Funds::where('id',$user->wallet_id)->value('balance');
            $data = [
                'user_id'           => $param['uid'],
                'username'         => $user->name,
                'money'             => (int)$param['amount'],
                'balance'           => $balance,
                'coupon'            => (int)$param['discount'],
                'memo'              => $param['memo']??'',
                'type'              => 1,
                'wallet_type'       => 1,
                'withdraw_bet'      => (int)$param['play_code'],
                'apply_admin_uid'   => $this->playLoad['uid'],
            ];

            \DB::table('funds_manual_check')->insert($data);
            throw new \Exception('',11052);
        }

    }

};
