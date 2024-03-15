<?php

use Logic\Admin\BaseController;

return new class() extends BaseController {
    const TITLE       = '获取app下载落地页设置';
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
        $data = [
            'app_boot' => $res['app_boot']
        ];

        $data['app_boot']['top_img'] = isset($data['app_boot']['top_img']) ? showImageUrl($data['app_boot']['top_img']) : '';
        $data['app_boot']['download_img'] = isset($data['app_boot']['download_img']) ? showImageUrl($data['app_boot']['download_img']) : '';

        return $data;
    }
};