<?php

use Logic\Admin\BaseController;
use Illuminate\Database\Capsule\Manager as DB;

return new class() extends BaseController {
    const TITLE       = '更新充值提现设置';
    const QUERY       = [];
    const SCHEMAS     = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run() {
        $data = $this->request->getParams();
        $withdraw = \Logic\Set\SystemConfig::getModuleSystemConfig('withdraw');
        $recharge = \Logic\Set\SystemConfig::getModuleSystemConfig('recharge');
        $recharge_type = \Logic\Set\SystemConfig::getModuleSystemConfig('recharge_type');
        $tmp = ["withdraw"=>$withdraw,"recharge"=>$recharge,"recharge_type"=>$recharge_type];

        if($data['withdraw']['withdraw_card_money']['withdraw_min'] < $data['withdraw']['withdraw_money']['withdraw_min']){
            return $this->lang->set(11055);
        }
        if($data['withdraw']['withdraw_card_money']['withdraw_max'] > $data['withdraw']['withdraw_money']['withdraw_max']){
            return $this->lang->set(11056);
        }
        if($data['withdraw']['withdraw_bkge_money']['withdraw_min'] > $data['withdraw']['withdraw_bkge_money']['withdraw_max']){
            return $this->lang->set(11057);
        }
        $confg = new \Logic\Set\SystemConfig($this->ci);
        $confg->updateSystemConfig($data,$tmp);
        return $this->lang->set(0);
    }


};
