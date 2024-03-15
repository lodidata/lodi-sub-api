<?php

use Logic\Admin\BaseController;
use Logic\Set\SystemConfig;

return new class() extends BaseController {
    const TITLE       = '代理说明设置';
    const QUERY       = [];
    const SCHEMAS     = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run() {
        $data = $this->request->getParams();
        $landingPage = SystemConfig::getModuleSystemConfig('website');
        $tmp = ['website' => $landingPage];
        $data = [
            'website' => [
                'agent_desc_config' => [
                    'img' => replaceImageUrl($data['img'])
                ]
            ]
        ];

        $confg = new SystemConfig($this->ci);
        $confg->updateSystemConfig($data, $tmp);

        return $this->lang->set(0);
    }
};