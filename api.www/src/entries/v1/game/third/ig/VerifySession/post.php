<?php

use Utils\Www\Action;
use Logic\GameApi\GameApi;

return new class extends Action {
    const TITLE = "IG游戏玩家验证回调";
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
        $this->logger->debug('IG:VerifySession', $params);
        if(!isset($params['custom_parameter']) || empty($params['custom_parameter']) || ! @hex2bin($params['custom_parameter'])){
            $msg = [
                'error' => [
                    'code' => 701,
                    'message' => 'custom_parameter exception'
                ],
                'data' => null,
            ];
        }else {
            list($tid, $uid) = explode('-', @hex2bin($params['custom_parameter']));
            $uid = intval($uid);
            if (!$uid) {
                $msg = [
                    'error' => [
                        'code' => 701,
                        'message' => 'custom_parameter exception'
                    ],
                    'data' => null,
                ];
            } else {
                $count = \DB::table('user')->where('id', $uid)->count();
                if (!$count) {
                    $msg = [
                        'error' => [
                            'code' => 701,
                            'message' => 'custom_parameter exception'
                        ],
                        'data' => null,
                    ];
                } else {
                    $gameClass = \Logic\GameApi\GameApi::getApi('IG', $uid);
                    $msg = $gameClass->VerifySession($params);
                }
            }
        }
        $this->logger->debug('IG:VerifySession', ['VerifySession' => $params, 'return' => $msg]);
        GameApi::addElkLog(['VerifySession' => $params, 'return' => $msg], 'IG');
        return $this->response->withStatus(200)
            ->withHeader('Content-Type', 'application/json')
            ->withJson($msg);
    }

};