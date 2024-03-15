<?php

use Utils\Www\Action;
use Logic\GameApi\GameApi;

return new class extends Action {
    const TITLE = "SGMK用户授权回调";
    const TAGS = '游戏';
    const DESCRIPTION = "";
    const QUERY = [
        'acctId'  => 'string(required) #游戏玩家 ID',
        'token' => 'string(required) #token',
        'language' => 'string() #语言',
        'merchantCode' => "string(required) #标识商户 ID",
        'serialNo' => "string(required) #用于标识消息的序列，由调用者生成",
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
        if(empty($params['acctId'])){
            return $this->response->withStatus(200)
                ->withHeader('Content-Type', 'application/json')
                ->withJson(['code' => '113', 'msg' => 'AcctID不正确']);
        }
        $uid = \DB::table('game_user_account')->where('user_account', $params['acctId'])->value('user_id');
        if(is_null($uid)){
            $msg = '会员不存在';
            $this->logger->debug('sgmk:authorize', ['authorize' => $params, 'return' => $msg]);
            GameApi::addElkLog(['authorize' => $params, 'return' => $msg], 'SGMK');
            return $this->response->withStatus(200)
                ->withHeader('Content-Type', 'application/json')
                ->withJson(['code' => '10103', 'msg' => $msg]);
        }
        $gameClass = \Logic\GameApi\GameApi::getApi('SGMK', $uid);
        $msg = $gameClass->authorize($params);
        $this->logger->debug('sgmk:authorize', ['authorize' => $params, 'return' => $msg]);
        GameApi::addElkLog(['authorize' => $params, 'return' => $msg], 'SGMK');
        return $this->response->withStatus(200)
            ->withHeader('Content-Type', 'application/json')
            ->withJson($msg);
    }

};