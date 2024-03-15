<?php

use Utils\Www\Action;
use Logic\User\Agent;

return new class() extends Action {
    const TITLE = '直推配置中的推广文本';
    const DESCRIPTION = '获取直推配置中的推广文本';
    const QUERY = [

    ];
    const SCHEMAS = [];

    public function run()
    {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $user_id = $this->auth->getUserId();
        $params = $this->request->getParams();

        //获取直推配置中推广文本
        $direct_conf = \Logic\Set\SystemConfig::getModuleSystemConfig('direct');
        if (!isset($direct_conf['direct_content'])) {
            return createRsponse($this->response, 200, -2, "系统配置数据为空", [], []);
        }

        $agent = new Agent($this->ci);
        $res['marker_link'] = $agent->generalizeList($user_id);

        $text = sprintf($direct_conf['direct_content'], $res['marker_link']['h5_url'][0]);

        $res = [
            'text' => $text
        ];


        return $this->lang->set(0, [], $res, []);
    }
};
