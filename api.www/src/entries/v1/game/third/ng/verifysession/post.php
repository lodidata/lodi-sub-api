<?php

use Utils\Www\Action;
use Logic\GameApi\GameApi;

return new class extends Action {
    const TITLE = "NG游戏玩家验证回调";
    const TAGS = '游戏';
    const DESCRIPTION = "";
    const QUERY = [

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

        $index = strpos($params['data']['playerToken'],"_");
        $res = substr($params['data']['playerToken'],0, $index);
        $user = \DB::table('game_user_account')->where('user_account', $res)->first();
        if (!$user) {
            $msg['error'] = [
                'code' => 1205,
                'message' => 'Unknown user id'
            ];
        }

        $gameClass = \Logic\GameApi\GameApi::getApi('NG', $user->user_id);
        $msg = $gameClass->VerifySession($params);

        $this->logger->debug('ng:VerifySession', ['VerifySession' => $params, 'return' => $msg]);
        GameApi::addElkLog(['VerifySession' => $params, 'return' => $msg], 'NG');
        return $this->response->withStatus(200)
            ->withHeader('Content-Type', 'application/json')
            ->withJson($msg);
    }

};