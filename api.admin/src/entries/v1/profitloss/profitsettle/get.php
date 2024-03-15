<?php

use Logic\Admin\BaseController;
use Logic\Set\SystemConfig;

return new class() extends BaseController {
    const TITLE       = '盈亏设置';
    const QUERY       = [];
    const SCHEMAS     = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run() {
        $data=[];
        $data['proportion'] = SystemConfig::getModuleSystemConfig('profit_loss')['proportion'];
        $data['sub_default_proportion'] = SystemConfig::getModuleSystemConfig('profit_loss')['sub_default_proportion'];
        $data['active_number'] = SystemConfig::getModuleSystemConfig('profit_loss')['active_number'];
        $data['daily_condition'] = SystemConfig::getModuleSystemConfig('profit_loss')['daily_condition'];
        $data['weekly_condition'] = SystemConfig::getModuleSystemConfig('profit_loss')['weekly_condition'];
        $data['monthly_condition'] = SystemConfig::getModuleSystemConfig('profit_loss')['monthly_condition'];

        $data['default_proportion_switch'] = SystemConfig::getModuleSystemConfig('profit_loss')['default_proportion_switch'];
        $data['fixed_proportion_switch'] = SystemConfig::getModuleSystemConfig('profit_loss')['fixed_proportion_switch'];
        $data['sub_proportion_switch'] = SystemConfig::getModuleSystemConfig('profit_loss')['sub_proportion_switch'];
        $data['default_proportion'] = SystemConfig::getModuleSystemConfig('profit_loss')['default_proportion'];
        $data['fixed_proportion'] = SystemConfig::getModuleSystemConfig('profit_loss')['fixed_proportion'];
        $data['sub_fixed_proportion'] = SystemConfig::getModuleSystemConfig('profit_loss')['sub_fixed_proportion'];

        $data['must_has_recharge'] = SystemConfig::getModuleSystemConfig('profit_loss')['must_has_recharge'] ?? false;
        //盈亏返佣结算方式
        $conf = SystemConfig::getModuleSystemConfig('rakeBack');
        $data["bkge_settle_type"] = $conf['bkge_settle_type'] ?? "";

        return $data;
    }
};
