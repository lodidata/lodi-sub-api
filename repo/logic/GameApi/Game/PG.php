<?php

namespace Logic\GameApi\Game;

use Logic\Define\Cache3thGameKey;
use Logic\GameApi\GameApi;
use Model\FundsChild;
use DB;
use Utils\Curl;
use Logic\GameApi\GameToken;

/**
 * PG电子
 * Class PG
 * @package Logic\GameApi\Game
 */
class PG extends \Logic\GameApi\Api
{
    protected $langs = [
        'th' => 'th',
        'zh-cn' => 'zh',
        'en-us' => 'en',
        'vn' => 'vn',
        'es-mx' => 'es',
    ];

    protected $orderTable = 'game_order_pg';

    private $trace_id;

    /**
     * 请求的唯一标识符（GUID）
     * @return string
     */
    public function guid()
    {
        if (!$this->trace_id) {
            $tid = $this->ci->get('settings')['app']['tid'];
            $charid = strtoupper(md5($tid . 'o' . $this->uid));
            $hyphen = chr(45);// "-"
            //chr(123)// "{"
            $this->trace_id = substr($charid, 0, 8) . $hyphen
                . substr($charid, 8, 4) . $hyphen
                . substr($charid, 12, 4) . $hyphen
                . substr($charid, 16, 4) . $hyphen
                . substr($charid, 20, 12);
            // .chr(125);// "}"
        }
        return $this->trace_id;
    }

    /**
     * 验证session
     * @return mixed
     */
    public function VerifySession($params)
    {
        global $app;
        $logger = $app->getContainer()->logger;
        $logger->info('pg:VerifySession-params', $params);
        $return = [
            'data' => null,
            'error' => null
        ];
        if (!isset($params['operator_token']) || !isset($params['secret_key']) || $params['operator_token'] != $this->config['cagent'] || $params['secret_key'] != $this->config['des_key']) {
            $return['error'] = [
                'code' => "1204",
                'message' => '无效运营商'
            ];
        } else {
            $account = $this->getGameAccount();
            if (!isset($params['operator_player_session']) || $params['operator_player_session'] != md5($account['account'])) {
                $return['error'] = [
                    'code' => "1300",
                    'message' => '无效玩家令牌'
                ];
            } else {
                $return['data'] = [
                    'player_name' => $account['account'],
                    'nickname' => $account['account'],
                    'currency' => $this->config['currency']
                ];
            }

        }
        $logger->info('pg:VerifySession-return', $return);
        return $return;
    }

    /**
     * 无需创建账户，第三方会自动判定
     * @param string $account
     * @param string $password
     * @return bool
     */
    public function childCreateAccount(string $account, string $password)
    {
        $param = [
            'player_name' => $account,
            'nickname' => $account,
            'currency' => $this->config['currency'],
        ];
        $res = $this->requestParam('/Player/v1/Create', $param);
        return $res['status'];
    }

    //进入游戏
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
        //余额转入第三方
        $res = $this->rollInThird();
        if (!$res['status']) {
            return [
                'status' => 886,
                'message' => $res['msg'],
                'url' => ''
            ];
        }
        $tid = $this->ci->get('settings')['app']['tid'];
        $back_url = $this->ci->get('settings')['website']['game_back_url'] ?? $_SERVER['HTTP_HOST'];
        $origins = ['pc' => 1, 'h5' => 1, 'ios' => 4, 'android' => 3];
        $origin = isset($this->request->getHeaders()['HTTP_PL']) && is_array($this->request->getHeaders()['HTTP_PL']) ? current($this->request->getHeaders()['HTTP_PL']) : 'h5';
        $param = [
            'btt' => 1,//$origins[$origin],
            'ot' => $this->config['cagent'],
            'ops' => md5($account['account']),
            'l' => $this->langs[LANG]?? $this->langs['en-us'],
            'op' => $tid . '-' . $this->uid,
            'f' => $back_url,
            //'rurl' => $back_url,
            //'ct' => 2
        ];
        $loginUrl = $this->config['loginUrl'] . '/' . $params['kind_id'] . '/index.html';
        return [
            'status' => 0,
            'url' => $loginUrl . "?" . http_build_query($param, '', '&'),
            'message' => 'ok'
        ];

    }

    /**
     * 同步超管订单
     * @return bool
     */
    public function synchronousChildData()
    {
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
            $user_id = (new GameToken())->getUserId($val['Username']);
            if (!$user_id) {
                continue;
            }
            $val['user_id'] = $user_id;
            unset($val['id'], $val['tid']);
            $batchData[] = $val;

            $orders = [
                'user_id' => $user_id,
                'game' => 'GAME',
                'order_number' => $val['OCode'],
                'game_type' => 'PG',
                'type_name' => $this->lang->text('PG'),
                'play_id' => 76,
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
     * 检测金额
     * @param null $data
     */
    public function checkMoney($data = null)
    {
        if (is_array($data) && $data['balance']) {
            $account = $this->getGameAccount();
            $params = [
                'player_name' => $account['account'],
                'transfer_reference' => $data['tradeNo'],
            ];
            $res = $this->requestParam('/Cash/v3/GetSingleTransaction', $params, true, true);

            if (isset($res) && (is_null($res["error"]))) {
                $this->updateGameMoneyError($data, bcmul($res['data']['transactionAmount'], 100, 0));
            }

            //交易不存在
            if (isset($res["error"]['code']) && $res["error"]['code'] == 3040) {
                $this->refundAction($data);
            }
        }
    }

    /**
     * 退出游戏
     * @return bool
     * @throws \Interop\Container\Exception\ContainerException
     * @throws \Exception
     */
    public function quitChildGame()
    {
        $account = $this->getGameAccount();
        $data = [
            'player_name' => $account['account'],
        ];
        $res = $this->requestParam('/Player/v1/Kick', $data);
        if ($res['status'] && $res['data']['action_result'] == 1) {
            $this->rollOutThird();
            return true;
        }
        return false;
    }

    /**
     * 检查转账状态
     * @param $tradeNo
     * @return bool|int
     */
    public function transferCheck($tradeNo)
    {
        $account = $this->getGameAccount();
        $data = [
            'player_name' => $account['account'],
            'transfer_reference' => $tradeNo,
        ];
        $res = $this->requestParam('/Cash/v3/GetSingleTransaction', $data);
        return $res['status'];
    }

    /**
     * 获取第三方钱包信息 (分为单位)
     * @return array[可下分金额，总金额]
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function getThirdWalletBalance()
    {
        $account = $this->getGameAccount();
        $data = [
            'player_name' => $account['account'],
        ];
        $res = $this->requestParam('/Cash/v3/GetPlayerWallet', $data);
        if ($res['status']) {
            return [bcmul($res['data']['cashBalance'], 100, 0), bcmul($res['data']['cashBalance'], 100, 0)];
        }

        $balance = FundsChild::where('pid', $this->wid)->where('game_type', $this->game_alias)->value('balance');
        return [0, $balance];
    }

    /***
     * 退出第三方,并回收至钱包
     * @param int $balance
     * @param string $tradeNo
     * @return array(是否成功,转出后第三方剩余金额)
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function rollOutChildThird(int $balance, string $tradeNo)
    {
        $account = $this->getGameAccount();
        $data = [
            'player_name' => $account['account'],
            'transfer_reference' => $tradeNo,
            'currency' => $this->config['currency']
        ];
        $res = $this->requestParam('/Cash/v3/TransferAllOut', $data);
        if ($res['status']) {
            return [true, $balance];
        } else {
            return [false, $balance];
        }
    }

    /**
     * 进入第三方，并转入钱包
     * @param int $balance
     * @param string $tradeNo
     * @return bool|int
     */
    function rollInChildThird(int $balance, string $tradeNo)
    {
        $account = $this->getGameAccount();
        $data = [
            'player_name' => $account['account'],
            'transfer_reference' => $tradeNo,
            'amount' => bcdiv($balance, 100, 2),
            'currency' => $this->config['currency']
        ];
        $res = $this->requestParam('/Cash/v3/TransferIn', $data);
        return $res['status'];
    }

    /**
     * 发送请求
     * @param string $action
     * @param array $param 请求参数
     * @param bool $is_post 是否为post请求
     * @param bool $status 是否返回请求状态
     * @param bool $is_order 是否请求订单接口
     * @return array|string
     */
    public function requestParam($action, array $param, bool $is_post = true, $status = false, $is_order = false)
    {
        if(is_null($this->config)){
            $ret = [
                'status' => false,
                'message' => 'no api config'
            ];
            GameApi::addElkLog($ret,'PG');
            return $ret;
        }

        $xdate = date('Ymd', time());
        $option = [
            'operator_token' => $this->config['cagent'],
            'secret_key' => $this->config['des_key']
        ];
        $option = array_merge($option, $param);

        $queryString = http_build_query($option, '', '&');
        $apiUrl = $is_order ? $this->config['orderUrl'] : $this->config['apiUrl'];
        $hosts = parse_url($apiUrl);
        $host = $hosts['host'];
        $contentsha256 = $this->hash_sha256($queryString);
        $Credential = $xdate . '/' . $this->config['cagent'] . '/pws/v1';
        $SignedHeaders = 'host;x-content-sha256;x-date';
        $Signature = $this->hash_sha256($host . $contentsha256 . $xdate);
        $authorization = "PWS-HMAC-SHA256Credential=" . $Credential . ',SignedHeaders=' . $SignedHeaders . ',Signature=' . $Signature;
        $header = [
            'Content-Type: application/x-www-form-urlencoded',
            'Host:' . $host,
            'x-date:' . $xdate,
            'x-content-sha256:' . $contentsha256,
            'Authorization:' . $authorization
        ];


        $url = rtrim($apiUrl, '/') . $action;
        $trace_id = $this->guid();
        $url .= '?trace_id=' . $trace_id;
        //echo $url.PHP_EOL;
        //print_r($option);
        //print_r($header);
        $re = Curl::commonPost($url, null, $queryString, $header);
        //var_dump($re);die;
        is_array($re) && $re = json_encode($re, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        GameApi::addRequestLog($url, 'PG', array_merge($option, $param), $re);
        $res = json_decode($re, true);
        if ($status) {
            return $res;
        }
        if (isset($res) && (is_null($res["error"]) || (isset($res["error"]['code']) && $res["error"]['code'] == 1305))) {
            $res['status'] = true;
        } else {
            $res['status'] = false;
        }
        return $res;
    }

    public function hash_sha256($string)
    {
        return strtoupper(hash_hmac('sha256', utf8_encode($string), utf8_encode($this->config['key']), false));
    }

    /**
     * 获取头奖
     * @return integer
     */
    function getJackpot()
    {
        $data = [
            'currency' => $this->config['currency']
        ];
        $res = $this->requestParam('/Jackpot/v1/Get', $data);
        if ($res['status'] && isset($res['data']) || !empty($res['data'])) {
            foreach ($res['data'] as $val){
                //1: 至尊奖 2: 大奖 3: 好运奖
                if($val['jackpotType'] == 1){
                    return $val['amount'];
                }
            }
            //没有取大奖
            foreach ($res['data'] as $val){
                //1: 至尊奖 2: 大奖 3: 好运奖
                if($val['jackpotType'] == 2){
                    return $val['amount'];
                }
            }
        }
        return 0;
    }
}
