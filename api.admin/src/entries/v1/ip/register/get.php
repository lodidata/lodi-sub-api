<?php

use Logic\Admin\BaseController;
use Logic\Set\SystemConfig;

return new class() extends BaseController {
    const TITLE       = '注册ip设置';
    const QUERY       = [];
    const SCHEMAS     = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run() {
        $data=[];
        $data['register_limit_ip_list'] = SystemConfig::getModuleSystemConfig('system')['register_limit_ip_list'];


        return $data;
    }
};
