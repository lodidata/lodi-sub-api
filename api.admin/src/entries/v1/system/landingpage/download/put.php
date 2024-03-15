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