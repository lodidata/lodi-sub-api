<?php

use Logic\Admin\BaseController;
use Illuminate\Database\Capsule\Manager as DB;

return new class() extends BaseController {
    const TITLE       = '获取充值提现设置';
    const QUERY       = [];
    const SCHEMAS     = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public $module = ['withdraw'=>'','recharge'=>'','recharge_type'=>''];
    public function run() {
        $res['withdraw'] = \Logic\Set\SystemConfig::getModuleSystemConfig('withdraw');
        $res['recharge'] = \Logic\Set\SystemConfig::getModuleSystemConfig('recharge');
        $res['recharge_type'] = \Logic\Set\SystemConfig::getModuleSystemConfig('recharge_type');
        return $res;
    }
};
