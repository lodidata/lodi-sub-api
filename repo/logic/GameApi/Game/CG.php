<?php

namespace Logic\GameApi\Game;

use Logic\Define\Cache3thGameKey;
use Logic\GameApi\Common;
use Logic\GameApi\GameApi;
use Logic\GameApi\GameToken;
use Model\FundsChild;
use Utils\Curl;
use Model\user as UserModel;

/**
 * CG电子
 * Class CG
 * @package Logic\GameApi\Game
 */
class CG extends \Logic\GameApi\Api
{
    /**
     * @var string 订单表
     */
    protected $orderTable = 'game_order_cg';

    public function verifyToken($params)
    {
        $error = 0;
        $data = [];
        if (!isset($params['channelId']) || $params['channelId'] != $this->config['cagent']) {
            $error = 112;//平台(渠道号)错误
        } else {
            $data = $this->aes256CbcDecrypt($params['data']);
            $data = json_decode($data, true);
            $token = $data['token'] ? json_decode($data['token'], true) : '';
            if (empty($token) || !isset($token['accountId']) || empty($token['accountId']) || !isset($token['user_id']) || empty($token['user_id'])) {
                $error = 110;
            } else {
                $account = (array)\DB::table('game_user_account')->where('user_id', $token['user_id'])->first();
                if (!$account || $account['user_account'] != $token['accountId']) {
                    $error = 9;
                } else {
                    $this->uid = $token['user_id'];
                    //$this->wid = \Db::table('user')->where('id', $token['user_id'])->value('wallet_id');
                    $this->wid = (new Common($this->ci))->getUserInfo($token['user_id'])['wallet_id'];
                    //余额转入第三方
                    $result = $this->rollInThird();
                    if (!$result['status']) {
                        $error = 10;
                    }
                }
            }
        }
        $msg = [
            'channelId' => $this->config['cagent'],
            'accountId' => $token['accountId'] ?? '',
            'nickName' => $token['accountId'] ?? '',
            'errorCode' => $error
        ];
        $return_data = $this->aes256CbcEncrypt($msg);
        $params['data'] = urlencode($params['data']);
        $params['aes_data'] = $data;
        $this->logger->debug('CG:verifyToken', ['params' => $params, 'return' => $msg, 'aes_return' => urlencode($return_data)]);
        GameApi::addElkLog(['verifyToken' => $params, 'return' => $msg, 'aes_return' => urlencode($return_data)], 'CG');
        return $return_data;
    }

    //第一步 创建游戏账号
    public function childCreateAccount(string $account, string $password)
    {
        if (!$this->checkStatus()) {
            return false;
        }
        $fields = [
            'accountId' => $account,
            'currency' => $this->config['currency']
        ];
        $res = $this->requestParam('td_create_account', $fields);
        if ($res['responseStatus']) {
            return true;
        }
        return true;
    }


    //进入游戏
    public function getJumpUrl(array $params = [])
    {
        if (!$this->checkStatus()) {
            return [
                'status' => 116,
                'message' => $this->lang->text(116), //'该游戏正在维护中',
                'url' => ''
            ];
        }
        //检测并创建账号
        $account = $this->getGameAccount();
        if (!$account) {
            return [
                'status' => 133,
                'message' => $this->lang->text(133),
                'url' => ''
            ];
        }

        try {
            $back_url = $this->ci->get('settings')['website']['game_back_url'] ?? $_SERVER['HTTP_HOST'];
            $asc_data = [
                'accountId' => $account['account'],
                'user_id' => $this->uid,
                'nickName' => $account['account'],
                'currency' => $this->config['currency']
            ];
            $fields = [
                "version" => $this->config['lobby'],
                'language' => 'en',
                'channelId' => $this->config['cagent'],
                'homeUrl' => $back_url,
                'data' => $this->aes256CbcEncrypt($asc_data),
                'showHome' => 'true',
            ];
            $url = $this->config['loginUrl'] . '/' . $params['kind_id'] . '/?' . http_build_query($fields, '', '&');
            return [
                'status' => 0,
                'url' => $url,
                'message' => 'ok'
            ];
        } catch (\Exception $e) {
            return [
                'status' => -1,
                'message' => $e->getMessage(),
                'url' => ''
            ];
        }
    }

    /**
     * 获取余额
     * @return array
     */
    public function getThirdWalletBalance()
    {
        $account = $this->getGameAccount();
        $data = [
            'accountId' => $account['account'],
        ];
        $res = $this->requestParam('td_balance', $data);
        if ($res['responseStatus']) {
            return [bcmul($res['balance'], 100, 0), bcmul($res['balance'], 100, 0)];
        }

        $balance = FundsChild::where('pid', $this->wid)->where('game_type', $this->game_alias)->value('balance');
        return [0, $balance];
    }

    /**
     * 登出
     * 4.9 TerminateSession
     * @return array|bool
     */
    public function quitChildGame()
    {
        $account = $this->getGameAccount();
        $fields = [
            'accountId' => $account['account'],
        ];
        $res = $this->requestParam('td_logout', $fields);
        if ($res['responseStatus']) {
            return true;
        }
        return false;
    }

    /**
     * 检测是否有转入转出失败的记录
     * @param null $data
     */
    public function checkMoney($data = null)
    {
        //转入金额超时则在此查询
        if (is_array($data) && $data['balance']) {
            $params = [
                'currency' => $this->config['currency'],
                'serialNumber' => $data['tradeNo']
            ];
            $res = $this->requestParam('td_userwallet_transaction_status', $params);
            //响应成功
            if ($res['responseStatus']) {
                //订单状态: -1: 错误讯息不为成功 0: 订单完成、1: 订单失败、2: 订单处理中
                if ($res['status'] == 0) {
                    $this->updateGameMoneyError($data, $data['balance']);
                } elseif ($res['status'] == 1) {
                    $this->refundAction($data);
                }
            } elseif (isset($res['errorCode']) && $res['errorCode'] > 0) {
                //订单不存在
                $this->refundAction($data);
            }
        }
    }

    /**
     * 第三方转出
     * @param int $balance
     * @param string $tradeNo
     * @return array
     */
    public function rollOutChildThird(int $balance, string $tradeNo)
    {
        $account = $this->getGameAccount();
        $data = [
            'currency' => $this->config['currency'],
            'accountId' => $account['account'],
            'amount' => bcdiv($balance, 100, 2), //元为单位
            'serialNumber' => $tradeNo,
            'type' => 1,//转点类型 (0:入款，1:提款)
            'time' => date(DATE_RFC3339_EXTENDED, time()),
            'timevalue' => 1
        ];
        $res = $this->requestParam('td_userwallet_transaction', $data);
        if ($res['responseStatus']) {
            return [true, $balance];
        } else {
            return [false, $balance];
        }

    }

    /**
     * 转入第三方
     * @param int $balance
     * @param string $tradeNo
     * @return array|bool
     */
    public function rollInChildThird(int $balance, string $tradeNo)
    {
        $account = $this->getGameAccount();
        $data = [
            'currency' => $this->config['currency'],
            'accountId' => $account['account'],
            'amount' => bcdiv($balance, 100, 2), //元为单位
            'serialNumber' => $tradeNo,
            'type' => 0,//转点类型 (0:入款，1:提款)
            'time' => date(DATE_RFC3339_EXTENDED, time()),
            'timevalue' => 1
        ];
        $res = $this->requestParam('td_userwallet_transaction', $data);
        if ($res['responseStatus']) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 查询游戏清单
     */
    public function getListGames()
    {
        return [];
    }

    /**
     * 检查接口状态
     * @return bool
     */
    public function checkStatus()
    {
        return true;
    }

    /**
     * 同步第三方游戏订单
     * 遊戲結束後大約八秒可以撈取到注單
     * @return bool
     */
    public function synchronousChildData()
    {
        $platformTypes = [
            'slot' => ['id' => 91, 'game' => 'GAME', 'type' => 'CG'],
            'pvp' => ['id' => 92, 'game' => 'QP', 'type' => 'CGQP'],
        ];

        if (!$data = $this->getSupperOrder($this->config['type'])) {
            return true;
        }

        //注单列表
        $batchData = [];
        //orders列表
        $batchOrderData = [];
        //打码量配置
        $auditSetting = \Logic\Set\SystemConfig::getModuleSystemConfig('audit');
        foreach ($data as $key => $val) {
            $user_id = (new GameToken())->getUserId($val['ThirdPartyAccount']);
            if (!$user_id) {
                continue;
            }
            $val['user_id'] = $user_id;
            unset($val['id'], $val['tid']);
            $batchData[] = $val;

            $orders = [
                'user_id' => $user_id,
                'game' => $platformTypes[$val['gameCategoryType']]['game'],
                'order_number' => $val['SerialNumber'],
                'game_type' => $platformTypes[$val['gameCategoryType']]['type'],
                'type_name' => $this->lang->text($platformTypes[$val['gameCategoryType']]['type']),
                'play_id' => $platformTypes[$val['gameCategoryType']]['id'],
                'bet' => bcmul($val['BetMoney'], 100, 0),
                'profit' => bcmul($val['MoneyWin'] - $val['BetMoney']+$val['JackpotMoney'], 100, 0),
                'send_money' => bcmul($val['MoneyWin']+$val['JackpotMoney'], 100, 0),
                'order_time' => $val['LogTime'],
                'date' => substr($val['LogTime'], 0, 10),
                'created' => date('Y-m-d H:i:s')
            ];
            $gameAduitSetting = isset($auditSetting[$orders['game']]) && $auditSetting[$orders['game']] ? bcdiv($auditSetting[$orders['game']], 100, 2) : 1;//游戏类型打码量设置，如果不存在则为1
            $orders['dml'] = $orders['bet'] * $gameAduitSetting;
            $batchOrderData[] = $orders;

        }
        $this->addGameOrders($this->game_type, $this->orderTable, $batchData);
        $this->addGameToOrdersTable($batchOrderData);

        unset($data, $val, $key, $query);

        return true;
    }

    /**
     * 发送请求
     * @param string $action 请求方法
     * @param array $param 请求参数
     * @param bool $is_post 是否为post请求
     * @param bool $status 是否返回请求状态
     * @param bool $is_order
     * @return array|string
     */
    public function requestParam(string $action, array $param, bool $is_post = true, $status = true, $is_order = false)
    {
        if(is_null($this->config)){
            $ret = [
                'responseStatus' => false,
                'message' => 'no api config'
            ];
            GameApi::addElkLog($ret,'CG');
            return $ret;
        }

        if ($action) {
            $action = '/' . $action;
        }
        $url = rtrim($is_order ? $this->config['orderUrl'] : $this->config['apiUrl'], '/') . $action;
        $headers = [
            'Content-Type : '
        ];
        if ($is_order) {
            $headers = [
                'Content-Type: application/x-www-form-urlencoded'
            ];
        }

        $postParams = [
            'version' => $this->config['lobby'],
            'channelId' => $this->config['cagent'],
            'data' => $this->aes256CbcEncrypt($param),
        ];
        //echo $url.PHP_EOL;
//var_dump(http_build_query($postParams, '', '&'));
        $re = Curl::commonPost($url, null, http_build_query($postParams, '', '&'), $headers);
        // var_dump($re);
        //
        if ($re === false) {
            $ret['responseStatus'] = false;
        } else {
            $re = $this->aes256CbcDecrypt($re);
            //var_dump($re);
            $ret = json_decode($re, true);
            if ($ret['errorCode'] == 0) {
                $ret['responseStatus'] = true;
            } else {
                $ret['responseStatus'] = false;
                if (isset($ret['data'])) {
                    $ret['data'] = $this->aes256CbcDecrypt($ret['data']);
                }
            }
        }
        $logs = $ret;
        unset($logs['responseStatus']);
        GameApi::addRequestLog($url, $this->game_type, $param, json_encode($logs, JSON_UNESCAPED_UNICODE));
        return $ret;
    }

    private function aes256CbcDecrypt($data)
    {
        $raw_key = base64_decode($this->config['key']);
        $raw_iv = base64_decode($this->config['des_key']);
        //$raw_data = base64_decode($data);
        $raw_data = $data;

        return openssl_decrypt($raw_data, "AES-256-CBC", $raw_key, 0, $raw_iv);
    }

    private function aes256CbcEncrypt($data)
    {
        $raw_key = base64_decode($this->config['key']);
        $raw_iv = base64_decode($this->config['des_key']);
        //$raw_data = base64_decode($data);
        $raw_data = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return openssl_encrypt($raw_data, "AES-256-CBC", $raw_key, 0, $raw_iv);
    }

    function getJackpot()
    {
        return 0;
    }
}

