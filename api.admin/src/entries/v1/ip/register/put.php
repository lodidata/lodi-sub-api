<?php

use Logic\Admin\BaseController;
use Illuminate\Database\Capsule\Manager as DB;

return new class() extends BaseController {
    const TITLE       = '更新注册ip设置';
    const QUERY       = [];
    const SCHEMAS     = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run() {
        $module = ['system'=>''];
        $data = $this->request->getParams();
        unset($data['s']);
        $tmp['system'] = \Logic\Set\SystemConfig::getModuleSystemConfig('system');
        $confg = new \Logic\Set\SystemConfig($this->ci);

        $confg->updateSystemConfig($data,$tmp);

        return $this->lang->set(0);
    }
};
