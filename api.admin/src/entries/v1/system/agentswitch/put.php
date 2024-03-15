<?php

use Logic\Admin\BaseController;
use Illuminate\Database\Capsule\Manager as DB;

return new class() extends BaseController {
    const TITLE       = '更新系统设置';
    const QUERY       = [];
    const SCHEMAS     = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run() {
        $data = $this->request->getParams();
        unset($data['s']);
        $tmp['user_agent'] = \Logic\Set\SystemConfig::getModuleSystemConfig('user_agent');
        $tmp['admin_agent'] = \Logic\Set\SystemConfig::getModuleSystemConfig('admin_agent');

        $confg = new \Logic\Set\SystemConfig($this->ci);
        $confg->updateSystemConfig($data,$tmp);

        return $this->lang->set(0);
    }
};
