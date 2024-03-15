<?php

use Logic\Admin\BaseController;
use Logic\Set\SystemConfig;

return new class() extends BaseController {
    const TITLE       = '代理股份占成设置';
    const QUERY       = [];
    const SCHEMAS     = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run() {
        $data=[];
        $data['proportion'] = SystemConfig::getModuleSystemConfig('agent')['proportion'];
        $data['shares'] = SystemConfig::getModuleSystemConfig('agent')['shares'];
        $data['user_proportion'] = SystemConfig::getModuleSystemConfig('agent')['user_proportion'];


        return $data;
    }
};
