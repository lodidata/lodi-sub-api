<?php

use Utils\Www\Action;
use Logic\GameApi\GameApi;

return new class extends Action {
    const TITLE = "PG游戏玩家验证回调";
    const TAGS = '游戏';
    const DESCRIPTION = "";
    const QUERY = [
        'trace_id'  => 'string(required) #请求的唯一标识符（GUID）URL 参数',
        'operator_token' => 'string(required) #运营商独有的身份识别',
        'secret_key'    => 'string(required) #PGSoft 与运营商之间共享密码',
        'operator_player_session' => "string(required) #运营商系统生成的令牌",
        'ip' => "string() #玩家 IP 地址",
        "custom_parameter" => 'string() #URL scheme18中的operator_param 值',
        'game_id' => 'string() #游戏的独有代码',
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
        list($tid, $uid) = explode('-', $params['custom_parameter'] ?? '');
        $uid = intval($uid);
        $gameClass = \Logic\GameApi\GameApi::getApi('PG', $uid);
        $msg = $gameClass->VerifySession($params);
        $this->logger->debug('pg:VerifySession', ['VerifySession' => $params, 'return' => $msg]);
        GameApi::addElkLog(['VerifySession' => $params, 'return' => $msg], 'PG');
        return $this->response->withStatus(200)
            ->withHeader('Content-Type', 'application/json')
            ->withJson($msg);
    }

};