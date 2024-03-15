<?php

use Logic\Admin\BaseController;

return new class() extends BaseController {
    const TITLE       = '获取代理说明设置';
    const QUERY       = [];
    const SCHEMAS     = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public $module = ['website' => ''];
    public function run() {
        $res = \Logic\Set\SystemConfig::getModuleSystemConfig('website');
        $return = $res['agent_desc_config'];
        $return['img'] = showImageUrl($return['img']);
        return $return;
    }
};