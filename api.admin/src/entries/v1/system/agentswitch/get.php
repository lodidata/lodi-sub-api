<?php

use Logic\Admin\BaseController;
use Illuminate\Database\Capsule\Manager as DB;

return new class() extends BaseController {
    const TITLE       = '获取系统设置';
    const QUERY       = [];
    const SCHEMAS     = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public $module = ['user_agent'=>'','admin_agent'=>''];
    public function run() {
        $res['user_agent'] = \Logic\Set\SystemConfig::getModuleSystemConfig('user_agent');
        $res['admin_agent'] = \Logic\Set\SystemConfig::getModuleSystemConfig('admin_agent');
        $game = \Model\Admin\GameMenu::where('pid',0)->where('switch','enabled')->get(['type','name'])->toArray();
        $game = array_column($game,null,'type');

        return $res;
    }
};
