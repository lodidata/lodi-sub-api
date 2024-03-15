<?php

use Logic\Admin\BaseController;
use \Logic\Recharge\Recharge;
use Model\FundsDealLog;
use Logic\Admin\Log;

return new class() extends BaseController {
    const STATE = '';
    const TITLE = '手动减少余额';
    const DESCRIPTION = '';

    const QUERY = [];

    const PARAMS = [
        'uid'    => 'int(required) #用户id',
        'role'   => 'int(required) #用户类型 1 会员，2 代理',
        'wid'    => 'int() #主钱包id',
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

        $result = $recharge->tzHandDecrease(
            $param['uid'],
            $param['amount'],
            $param['memo'] ?? null,
            \Utils\Client::getIp(),
            $this->playLoad['uid'],
            1,
            false,
            FundsDealLog::TYPE_REDUCE_MANUAL
        );
        if(!$result) return $this->lang->set(-2);

        //写入user_data数据中心   手动扣款不记录出款信息
        \Model\UserData::where('user_id',$param['uid'])->increment('withdraw_cj_amount',$param['amount'],['withdraw_cj_num'=>\DB::raw('withdraw_cj_num + 1')]);

        $user = \Model\Admin\User::find($param['uid']);
        $user->setTarget($user->id,$user->name);
        $user->logs_type = '手动减少余额';
        $user->opt_desc = '金额(' . ($param['amount'] / 100) . ')';
        $user->log();
        return $this->lang->set(0);
    }
};
