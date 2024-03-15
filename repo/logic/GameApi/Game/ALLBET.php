<?php

namespace Logic\GameApi\Game;

use Logic\GameApi\GameApi;
use Logic\GameApi\GameToken;
use Model\FundsChild;
use Utils\Curl;

class ALLBET extends \Logic\GameApi\Api
{
    protected $url;
    protected $orderTable = 'game_order_allbet';
    protected $langs = [
        'zh-cn' => 'zh_CN',
        'en-us' => 'en',
        'jp' => 'ja',
        'es-mx' => 'es-es',
        'ro' => 'RO',
        'th' => 'th',
        'vt' => 'vi',
        'ko' => 'ko',
        'ms' => 'ms',
        'id' => 'id'
    ];


    //第一步 创建游戏账号
    public function childCreateAccount(string $account, string $password)
    {
        $params = [
            'agent' => $this->config['cagent'],
            'player' => $account . $this->config['lobby'],
        ];

        $res = $this->requestParam('CheckOrCreate', $params);
        if ($res['responseStatus']) {
            return true;
        }
        return false;
    }


    //进入游戏 并创建用户
    public function getJumpUrl(array $params = [])
    {
        //检测并创建账号
        $account = $this->getGameAccount();
        if (!$account) {
            return [
                'status' => 133,
                'message' => $this->lang->text(133),
                'url' => ''
            ];
        }
        $parmas = [
            'player' => $account['account'] . $this->config['lobby'],
            'language' => $this->langs[LANG] ?? $this->langs['en-us'],
            'returnUrl' => $this->ci->get('settings')['website']['game_back_url'] ?? $_SERVER['HTTP_HOST']
        ];
        $res = $this->requestParam('Login', $parmas);
        if (!$res['responseStatus']) {
            return [
                'status' => 886,
                'message' => $res['message'] ?? 'api error',
                'url' => ''
            ];
        }

        //余额转入第三方
        $result = $this->rollInThird();
        if (!$result['status']) {
            return [
                'status' => 886,
                'message' => $result['msg'],
                'url' => ''
            ];
        }

        return [
            'status' => 0,
            'url' => $res['data']['gameLoginUrl'] ?? '',
            'message' => 'ok'
        ];
    }

    /**
     * 获取余额
     * 3.1.4 查询会员状态
     * API 查询会员账号当前状态、现有额度等信息
     * @return array
     */
    public function getThirdWalletBalance()
    {
        $account = $this->getGameAccount();
        $params = [
            'agent' => $this->config['cagent'],
            'pageSize' => 1,
            'pageIndex' => 1,
            'recursion' => 0,
            'players' => [
                $account['account'] . $this->config['lobby']
            ]
        ];
        $res = $this->requestParam('GetBalances', $params);
        if (isset($res['responseStatus']) && $res['responseStatus'] && isset($res['data']['count']) && $res['data']['count'] == 1) {
            return [bcmul($res['data']['list'][0]['amount'], 100, 0), bcmul($res['data']['list'][0]['amount'], 100, 0)];
        }
        $balance = FundsChild::where('pid', $this->wid)->where('game_type', $this->game_alias)->value('balance');
        return [0, $balance];
    }

    /**
     * 同步超管订单
     * @return bool
     * @throws \Exception
     */
    public function synchronousChildData()
    {
        if (!$data = $this->getSupperOrder($this->game_type)) {
            return true;
        }

        //注单列表
        $batchData = [];
        //orders列表
        $batchOrderData = [];
        //打码量配置
        $auditSetting = \Logic\Set\SystemConfig::getModuleSystemConfig('audit');
        foreach ($data as $key => $val) {
            $user_id = (new GameToken())->getUserId($val['Username']);
            if (!$user_id) {
                continue;
            }
            $val['user_id'] = $user_id;
            unset($val['id'], $val['tid']);
            $batchData[] = $val;

            $orders = [
                'user_id' => $user_id,
                'game' => 'LIVE',
                'order_number' => $val['OCode'],
                'game_type' => $this->game_type,
                'type_name' => $this->lang->text($this->game_type),
                'play_id' => 134,
                'bet' => $val['betAmount'],
                'profit' => $val['income'],
                'send_money' => $val['winAmount'],
                'order_time' => $val['gameDate'],
                'date' => substr($val['gameDate'], 0, 10),
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
     * 登出
     * 4.9 TerminateSession
     * @return array|bool
     */
    public function quitChildGame()
    {
        $account = $this->getGameAccount();
        $parmas = [
            'player' => $account['account'] . $this->config['lobby'],
        ];
        $res = $this->requestParam('Logout', $parmas);
        if ($res['responseStatus']) {
            return true;
        }
        return false;
    }

    public function checkMoney($data = null)
    {
        if (is_array($data) && $data['balance']) {
            $params = [
                'sn' => $data['tradeNo']
            ];
            // 查询玩家上下分订单
            $res = $this->requestParam('GetTransferState', $params);

            //转账不存在
            if(isset($res['resultCode']) && $res['resultCode'] == 'TRANS_NOT_EXIST'){
                //转账失败 退钱
                $this->refundAction($data);
            }
            if (isset($res['responseStatus']) && $res['responseStatus']) {
                $this->updateGameMoneyError($data, $data['balance']);
            } elseif(isset($res['responseStatus']) && isset($res['data'])  && isset($res['data']['transferState']) && $res['data']['transferState'] == 2) {
                //转账失败 退钱
                $this->refundAction($data);
            }
        }
    }

    /**
     * 创建订单号
     * @return string
     */
    public function generateChildOrderNumber()
    {
        return $this->config['key'] . str_replace('.','', sprintf('%.3f', microtime(TRUE)));
    }

    /**
     * 转出
     * @param int $balance
     * @param string $tradeNo
     * @return array|bool
     */
    public function rollOutChildThird(int $balance, string $tradeNo)
    {
        $account = $this->getGameAccount();
        $params = [
            'sn' => $tradeNo,
            'agent' => $this->config['cagent'],
            'player' => $account['account'] . $this->config['lobby'],
            'type' => 0,
            'amount' => bcdiv($balance, 100, 2)
        ];
        $res = $this->requestParam('Transfer', $params);
        if ($res['responseStatus']) {
            return [true, $balance];
        }
        return [false, $balance];
    }

    /**
     * 转入
     * @param int $balance
     * @param string $tradeNo
     * @return bool
     */
    public function rollInChildThird(int $balance, string $tradeNo)
    {
        $account = $this->getGameAccount();
        $params = [
            'sn' => $tradeNo,
            'agent' => $this->config['cagent'],
            'player' => $account['account'] . $this->config['lobby'],
            'type' => 1,
            'amount' => bcdiv($balance, 100, 2)
        ];
        $res = $this->requestParam('Transfer', $params);
        if ($res['responseStatus']) {
            return true;
        }
        return false;
    }


    /**
     * 发送请求
     * @param string $action
     * @param array $params 请求参数
     * @param bool $is_login 是否登录接口
     * @return array|string
     */
    public function requestParam(string $action, array $params, $is_login = false)
    {
        if(is_null($this->config)){
            $ret = [
                'responseStatus' => false,
                'message' => 'no api config'
            ];
            GameApi::addElkLog($ret,'ALLBET');
            return $ret;
        }
        $default_timezone = date_default_timezone_get();
        date_default_timezone_set("Etc/GMT-8");
        $requestTime = date('d M Y H:m:s T'); // "Wed, 28 Apr 2021 06:13:54 UTC";
        date_default_timezone_set($default_timezone);

        //Build the request parameters according to the API documentation
        $requestBodyString = json_encode($params, JSON_UNESCAPED_UNICODE);
        $contentMD5 =  base64_encode(pack('H*', md5($requestBodyString)));

        //The steps to generate HTTP authorization headers
        $stringToSign = "POST" . "\n"
            . $contentMD5 . "\n"
            . "application/json" . "\n"
            . $requestTime . "\n"
            . "/".$action;
        //Use HMAC-SHA1 to sign and generate the authorization
        $deKey = base64_decode($this->config['des_key']);
        $hash_hmac = hash_hmac("sha1", $stringToSign, $deKey, true);
        $encrypted = base64_encode($hash_hmac);
        $authorization = "AB" . " " . $this->config['key'] . ":" . $encrypted;

        //Send the Http request
        $url = $this->config['apiUrl'] . $action;
        //echo $url.PHP_EOL;
        $header = [
            "Content-Type: application/json",
            "Accept: application/json",
            "Authorization:" . $authorization,
            "Date:" . $requestTime,
            "Content-MD5:" . $contentMD5,
        ];
        //var_dump($header);die;
        $re = curl::commonPost($url, null,  $requestBodyString, $header, true );
        //var_dump($result);die;
        if ($re['status'] != 200) {
            $ret['responseStatus'] = false;
            $ret['netWorkStatus'] = $re['status'];
            $ret['message'] = $re['content'];
        } else {
            $ret = json_decode($re['content'], true);
            $ret['networkStatus'] = $re['status'];
            if (isset($ret['resultCode']) && $ret['resultCode'] != 'OK') {
                $ret['responseStatus'] = false;
            } else {
                $ret['responseStatus'] = true;
            }
        }

        GameApi::addRequestLog($url, $this->game_type, ['params' => $params, 'header' => $header], json_encode($ret, JSON_UNESCAPED_UNICODE));
        return $ret;
    }


    function getJackpot()
    {
        return 0;
    }
}

