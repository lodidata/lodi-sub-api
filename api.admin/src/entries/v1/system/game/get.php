<?php

use Logic\Admin\BaseController;
use Logic\Set\SystemConfig;

return new class() extends BaseController {
    const TITLE       = '首页全部游戏排序';
    const QUERY       = [];
    const SCHEMAS     = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run() {
        $data=[];
        $data['all_game'] = SystemConfig::getModuleSystemConfig('game')['all_game'];
        return $data;
    }
};
