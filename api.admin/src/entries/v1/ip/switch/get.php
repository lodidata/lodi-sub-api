<?php

use Logic\Admin\BaseController;

return new class() extends BaseController {
    const TITLE = 'IP白名单状态';
    const DESCRIPTION = '获取IP白名单开关状态';
    
    const QUERY = [
    ];
    const SCHEMAS = [
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run() {
        $settings = \Logic\Set\SystemConfig::getModuleSystemConfig('login');

        return $this->lang->set(0, [], [
            'switch' => $settings['ip_limit_whitelist'] ?? false,
        ]);
    }
};





