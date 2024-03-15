<?php

use Logic\Admin\BaseController;
use \Logic\Recharge\Recharge;
use Model\FundsDealLog;
use Logic\Admin\Log;

return new class() extends BaseController {
    const STATE = '';
    const TITLE = '手动减少直推余额';
    const DESCRIPTION = '';

    const QUERY = [];

    const PARAMS = [
        'uid'    => 'int(required) #用户id',
        'amount' => 'int(required) #变动金额',
        'memo'   => 'string() #备注',
    ];
    const SCHEMAS = [];

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
        $wallet_id = \Model\User::where('id', $param['uid'])->value('wallet_id');

        $direct_balance = \Model\Funds::where('id', $wallet_id)->value('direct_balance');

        if($param['amount']>$direct_balance){
            return $this->lang->set(2302);
        }

        $result = $recharge->decreaseDirect(
            $param['uid'],
            $param['amount'],
            $param['memo'] ?? null,
            $this->playLoad['uid']
        );
        if(!$result) return $this->lang->set(-2);

        $user = \Model\Admin\User::find($param['uid']);
        $user->setTarget($user->id,$user->name);
        $user->logs_type = '手动减少直推余额';
        $user->opt_desc = '金额(' . ($param['amount'] / 100) . ')';
        $user->log();
        return $this->lang->set(0);
    }
};
