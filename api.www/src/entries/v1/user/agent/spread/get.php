<?php

use Utils\Www\Action;

return new class extends Action
{
    const TOKEN = true;
    const TITLE = "获取代理推广码(人人代理)";
    const TAGS = "代理返佣";
    const SCHEMAS = [
        'invite_code' => "string(required) #推广码(ua_id)",
        'invite_url' => "string() #推广页下载APP地址",
        'invite_content' => "string() #推广页描述",
    ];


    public function run() {
        $verify = $this->auth->verfiyToken();

        if (!$verify->allowNext()) {
            return $verify;
        }

        $uid = $this->auth->getUserId();
        $agent = new \Logic\User\Agent($this->ci);
        $marker = $agent->generalizeList($uid);

        $url = is_array($marker['spread_url']) ? array_pop($marker['spread_url']) : '';
        $res = [
            'invite_code' => $marker['code'],
            'invite_url' => $url,
            'invite_content' => vsprintf($marker['spread_desc'],[$marker['code'],$url])
        ];
        return $res;
    }
};