<?php

use Logic\Admin\BaseController;
use Illuminate\Database\Capsule\Manager as DB;

return new class() extends BaseController {
    const TITLE       = '更新推广基本配置';
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
        $tmp['direct'] = \Logic\Set\SystemConfig::getModuleSystemConfig('direct');
        $confg = new \Logic\Set\SystemConfig($this->ci);
        $confg->updateSystemConfig($data,$tmp);

        return $this->lang->set(0);
    }
};
