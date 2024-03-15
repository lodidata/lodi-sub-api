<?php

use Utils\Www\Action;
use Logic\GameApi\GameApi;

return new class extends Action {
    const TITLE = "BSG游戏玩家验证回调";
    const TAGS = '游戏';
    const DESCRIPTION = "";
    const QUERY = [];
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

        $default_timezone = date_default_timezone_get();
        date_default_timezone_set("Etc/GMT");
        $return = [
            'EXTSYSTEM' => [
                'REQUEST'  => [
                    'TOKEN' => $params['token'],
                    'HASH'  => $params['hash']
                ],
                'TIME'     => date('Y-m-d H:i:s'),
                'RESPONSE' => []
            ]
        ];
        date_default_timezone_set($default_timezone);


        $this->logger->debug('BSG:VerifySession', $params);
        if(!isset($params['token']) || empty($params['hash']) || ! @hex2bin($params['token'])){
            $return['EXTSYSTEM']['RESPONSE'] = [
                'RESULT' => 'Internal Error',
                'CODE'   => 399
            ];
        }else {
            list($tid, $account) = explode('-', @hex2bin($params['token']));

            if (!$account) {
                $return['EXTSYSTEM']['RESPONSE'] = [
                    'RESULT' => 'Unknown user id',
                    'CODE'   => 310
                ];
            } else {
                $user = \DB::table('game_user_account')->where('user_account', $account)->first();
                if (!$user) {
                    $return['EXTSYSTEM']['RESPONSE'] = [
                        'RESULT' => 'Unknown user id',
                        'CODE'   => 310
                    ];
                } else {
                    $gameClass = \Logic\GameApi\GameApi::getApi('BSG', $user->user_id);
                    $res = $gameClass->VerifySession($params);
                    if($res['RESULT'] === 'OK'){
                        \Utils\MQServer::send('synchronousUserBalanceRollIn', [
                            'game_type' => 'BSG',
                            'uid' => $user->user_id,
                        ]);
                    }
                    $return['EXTSYSTEM']['RESPONSE']=$res;
                }
            }

        }
        $this->logger->debug('BSG:VerifySession', ['VerifySession' => $params, 'return' => $return]);
        GameApi::addElkLog(['VerifySession' => $params, 'return' => $return], 'BSG');
        return $this->response->withStatus(200)
            ->withJson($return);
    }

};