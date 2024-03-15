<?php

use Utils\Www\Action;
use Logic\GameApi\GameApi;

return new class extends Action {
    const TITLE = "CG用户授权回调";
    const TAGS = '游戏';
    const DESCRIPTION = "";
    const QUERY = [
        'version'  => 'string(required) #1.0，此为固定值',
        'channelId' => 'string(required) #此为前面启动游戏 URL 中所带入的 channelId',
        'data' => "string(required) #加密后的结果",
    ];
    public function run()
    {
        $api_token = $this->ci->request->getHeaderLine('api-token');
        if(!$api_token){
            die('token error');
        }
        $api_verify_token = $this->ci->get('settings')['app']['api_verify_token'];
        $token = $api_verify_token . date("Ymd");
        if($token != $api_token){
            die('token unequal');
        }

        $params = $this->request->getParams();

        $gameClass = \Logic\GameApi\GameApi::getApi('CG', 0);
        $msg = $gameClass->verifyToken($params);
        echo $msg;
        die;
    }

};