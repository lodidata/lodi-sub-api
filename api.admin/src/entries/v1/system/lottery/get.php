<?php

use Logic\Admin\BaseController;
use Illuminate\Database\Capsule\Manager as DB;

return new class() extends BaseController {
    const TITLE       = '系统设置';
    const QUERY       = [];
    const SCHEMAS     = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public $module = ['lottery'=>''];
    public function run() {
        $res['lottery'] = \Logic\Set\SystemConfig::getModuleSystemConfig('lottery');
        return $res;
    }
};
