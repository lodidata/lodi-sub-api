<?php

use Logic\GameApi\GameApi;
use Model\Funds;
use Utils\Www\Action;

return new class extends Action
{
    const TITLE = "STG游戏获取用户信息";
    const TAGS = 'STG游戏';
    const DESCRIPTION = "";
    const QUERY = [
        'PartnerId' => 'int(required) #Digitain系统中合作伙伴的标识符',
        'TimeStamp' => 'string(required) #时间',
        'Token' => 'string(required) #Token',
        'Signature' => "string(required) #加密",
    ];

    public function run()
    {
        $api_token = $this->ci->request->getHeaderLine('api-token');
        if (!$api_token) {
            die('token error');
        }
        $api_verify_token = $this->ci->get('settings')['app']['api_verify_token'];
        $token = $api_verify_token . date("Ymd");
        if ($token != $api_token) {
            die('token unequal');
        }

        $method = 'GetUserInfo';
        $return = [];
        $code = '';
        $params = $this->request->getParams();
        $gameClass = GameApi::getApi('STG', 0);
        if (!isset($params['Token']) || empty($params['Token']) || !isset($params['TimeStamp']) || empty($params['TimeStamp']) || !isset($params['Signature']) || empty($params['Signature']) || !isset($params['PartnerId']) || empty($params['PartnerId'])) {
            $code = 1013;
        } elseif ($params['PartnerId'] != $gameClass->config['cagent']) {
            $code = 1013;
        } else {
            //签名错误
            $sign = $params['Signature'];
            $md5Keys = ['PartnerId', 'TimeStamp', 'Token'];
            if ($sign != $gameClass->Signature($method, $params, $md5Keys, $gameClass->config['key'])) {
                $code = 1016;
            } else {
                //用户不存在
                $user_account = ltrim(@hex2bin($params['Token']));
                $user_id = \DB::table('game_user_account')->where('user_account', $user_account)->value('user_id');
                if (!$user_id) {
                    $code = 37;
                } else {
                    $tid = $this->ci->get('settings')['app']['tid'];
                    $wallet_id = \DB::table('user')->where('id', $user_id)->value('wallet_id');
                    $balance = Funds::where('id', $wallet_id)->value('balance');
                    $return = [
                        'ResponseCode' => 0,
                        "Description" => 'Success',
                        'TimeStamp' => time(),
                        'Token' => $params['Token'],
                        'ClientId' => hexdec($tid . $user_id),
                        'CurrencyId' => 'PHP',
                        'FirstName' => 'lodi',
                        'LastName' => $user_account,
                        'Gender' => 1,
                        'BirthDate' => '1990-01-01',
                        'TerritoryId' => '',
                        'AvailableBalance' => $balance
                    ];
                    $md5Keys = ['ResponseCode', 'Description', 'TimeStamp', 'Token', 'ClientId', 'CurrencyId', 'FirstName', 'LastName', 'Gender', 'BirthDate'];
                    $return['Signature'] = $gameClass->Signature($method, $return, $md5Keys, $gameClass->config['key']);

                    //  异步转入金额到第三方游戏
                    \Utils\MQServer::send('synchronousUserBalanceRollIn', [
                        'game_type' => 'STG',
                        'uid' => $user_id,
                    ]);

                }
            }
        }
        if (empty($return)) {
            $return = [
                'ResponseCode' => $code,
                "Description" => $gameClass->getErrorMessage($code),
                'TimeStamp' => time(),
                'Token' => $params['Token'],
                'ClientId' => 0,
                'CurrencyId' => 'PHP',
                'FirstName' => '',
                'LastName' => '',
                'Gender' => 0,
                'BirthDate' => '',
                'BetShopId' => '',
                'TerritoryId' => '',
                'AvailableBalance' => 0,
            ];
            $md5Keys = ['ResponseCode', 'Description', 'TimeStamp', 'Token', 'ClientId', 'CurrencyId', 'FirstName', 'LastName', 'Gender', 'BirthDate'];
            $return['Signature'] = $gameClass->Signature($method, $return, $md5Keys, $gameClass->config['key']);
        }

        GameApi::addElkLog(['method' => $method, 'params' => $params, 'return' => $return], 'STG');
        return $this->response->withStatus(200)
            ->withHeader('Content-Type', 'application/json')
            ->withJson($return);
    }

};