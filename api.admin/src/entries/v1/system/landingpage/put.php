<?php

use Logic\Admin\BaseController;
use Logic\Set\SystemConfig;

return new class() extends BaseController {
    const TITLE = '落地页设置';
    const QUERY = [];
    const SCHEMAS = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run()
    {
        $params = $this->request->getParams();
        $landingPage = SystemConfig::getModuleSystemConfig('website');
        $tmp = ['website' => $landingPage];

        $data = [];

        if (isset($params['jump_url'])) {
            if (!preg_match("/^(http:\/\/|https:\/\/).*$/", $params['jump_url'])) {
                return $this->lang->set(11041);
            }
            $data['website']['landing_page_config'] = [
                'img' => replaceImageUrl($params['img']),
                'jump_url' => $params['jump_url'],
                'video' => replaceImageUrl($params['video']),
                'button' => replaceImageUrl($params['button'])
            ];
        }

        if (isset($params['type'])) {
            $data['website']['app_boot'] = [
                'type' => $params['type'],
                'top_img' => replaceImageUrl($params['top_img']),
                'live_url' => $params['live_url'],
                'download_img' => replaceImageUrl($params['download_img'])
            ];
        }

        $confg = new SystemConfig($this->ci);
        $confg->updateSystemConfig($data, $tmp);

        return $this->lang->set(0);
    }
};