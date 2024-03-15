<?php

use Logic\Admin\BaseController;

return new class() extends BaseController
{
    const TITLE = '获取落地页设置';
    const QUERY = [];
    const SCHEMAS = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public $module = ['website' => ''];

    public function run()
    {
        $res['website'] = \Logic\Set\SystemConfig::getModuleSystemConfig('website');
        $landing_page_config = $res['website']['landing_page_config'];
        $app_boot = $res['website']['app_boot'];
        $data = [
            'landing_page_config' => [
                'img' => showImageUrl($landing_page_config['img']),
                'video' => showImageUrl($landing_page_config['video']),
                'button' => showImageUrl($landing_page_config['button']),
                'jump_url' => $landing_page_config['jump_url'],
            ],
            'app_boot' => [
                'type' => $app_boot['type'] ?? '',                 //app落地页风格
                'top_img' => showImageUrl($app_boot['top_img']),                 //顶部展示图
                'live_url' => $app_boot['live_url'] ?? '',                 //视频内容
                'download_img' => showImageUrl($app_boot['download_img']),         //下载引导页
            ],
        ];

        return $data;
    }
};