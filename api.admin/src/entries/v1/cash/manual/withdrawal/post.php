<?php

use Logic\Admin\BaseController;
use Logic\Admin\Log;
use Logic\Recharge\Recharge;
use Model\FundsDealLog;

return new class() extends BaseController {
    const STATE = '';
    const TITLE = '手动提款';
    const DESCRIPTION = '';

    const QUERY = [];

    const PARAMS = [
        "uid"     => 'int(required) #用户id',
        "wid"     => 'int() #钱包id',
        'role'    => 'enum[1,2](required,1) #会员 1，代理2',
        "amount"  => "int(required) #存款金额",
        "bank_id" => "int(requried) #用户申请出款银行id",
        "bank_no" => "string() #银行账号",
        "memo"    => "string() #备注",
        "status"  => 'int() #状态，1 代为申请',
    ];
    const SCHEMAS = [];

    const STATEs = [
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run() {
        $param = $this->request->getParams();

        $validate = new \lib\validate\BaseValidate([
            'uid'    => 'require|isPositiveInteger',
            'amount' => 'require|isPositiveInteger',
        ]);

        $validate->paramsCheck('', $this->request, $this->response);

        $recharge = new Recharge($this->ci);

        $result = $recharge->tzHandDecrease(
            $param['uid'],
            $param['amount'],
            $param['memo'] ?? null,
            \Utils\Client::getIp(),
            $this->playLoad['uid'],
            1,
            true,
            FundsDealLog::TYPE_WITHDRAW_MANUAL
        );
        if(!$result) return $this->lang->set(-2);

        $user = \Model\Admin\User::find($param['uid']);
        $user->setTarget($user->id,$user->name);
        $user->logs_type = '手工扣款';
        $user->opt_desc = '金额(' . ($param['amount'] / 100) . ')' ;
        $user->log();
        return $this->lang->set(0);
    }
};
