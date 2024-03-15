<?php

namespace Logic\GameApi\Game;

use Logic\Define\Cache3thGameKey;
use Logic\GameApi\GameApi;
use Logic\GameApi\GameToken;
use Model\FundsChild;
use Utils\Client;
use DB;
use Utils\Curl;

/**
 * SA真人
 * Class SA
 * @package Logic\GameApi\Game
 */
class SA extends \Logic\GameApi\Api
{
    protected $langs = [
        'th' => 'th',
        'zh-cn' => 'zh_CN',
        'en-us' => 'en_US',
        'vn' => 'vn',
        'id' => 'id',
        'in' => 'hi*',
    ];
    protected $orderTable = 'game_order_sa';


    /**
     * 无需创建账户，第三方会自动判定
     * @param string $account
     * @param string $password
     * @return bool
     */
    public function childCreateAccount(string $account, string $password)
    {
        $param = [
            'method' => 'RegUserInfo',
            'Username' => $account,
            'CurrencyType' => $this->config['currency'],
        ];
        $res = $this->requestParam($param);
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
        $data = [
            'method' => 'LoginRequest',
            'Username' => $account['account'],
            'CurrencyType' => $this->config['currency'],
        ];
        $res = $this->requestParam($data);
        if ($res['status']) {
            //余额转入第三方
            $result = $this->rollInThird();
            if (!$result['status']) {
                return [
                    'status' => 886,
                    'message' => $result['msg'] ?? 'roll in error',
                    'url' => ''
                ];
            }

            $back_url = $this->ci->get('settings')['website']['game_back_url'] ?? $_SERVER['HTTP_HOST'];
            $param = [
                'username' => $account['account'],
                'token' => $res['Token'],
                'lobby' => $this->config['lobby'],
                'lang' => $this->langs[LANG] ?? $this->langs['en-us'],
                'returnurl' => $back_url
            ];
            return [
                'status' => 0,
                'url' => $this->config['loginUrl'] . "?" . http_build_query($param, '', '&'),
                'message' => 'ok'
            ];
        } else {
            return [
                'status' => -1,
                'message' => $res['ErrorMsg'] ?? 'login error',
                'url' => ''
            ];
        }
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
                'game' => 'LIVE',
                'order_number' => $val['OCode'],
                'game_type' => $this->game_type,
                'type_name' => $this->lang->text($this->game_type),
                'play_id' => 73,
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
     * 退出游戏
     * @return bool
     * @throws \Exception
     */
    public function quitChildGame()
    {
        $account = $this->getGameAccount();
        $data = [
            'method' => 'KickUser',
            'Username' => $account['account'],
        ];
        $res = $this->requestParam($data);
        if ($res['status']) {
            $this->rollOutThird();
            return true;
        }
        return false;
    }

    /**
     * 创建订单号
     * @return string
     */
    public function generateChildOrderNumber()
    {
        $account = $this->getGameAccount();
        return date('YmdHis', time()) . $account['account'];
    }

    /**
     * 检测金额
     * @param null $data
     */
    public function checkMoney($data = null)
    {

        if (is_array($data) && $data['balance']) {
            // 查询玩家上下分订单
            $params = [
                'method' => 'CheckOrderId',
                'OrderId' => strtoupper($data['transfer_type']) . $data['tradeNo']
            ];
            $res = $this->requestParam($params, true, true);
            if (isset($res['ErrorMsgId']) && $res['ErrorMsgId'] == 0) {
                if (isset($res['isExist'])) {
                    //订单已存在
                    if ($res['isExist'] == 'true') {
                        $this->updateGameMoneyError($data, $data['balance']);
                    }
                    if ($res['isExist'] == 'false') {
                        //订单不存在
                        $this->refundAction($data);
                    }
                }
            }
        }
    }

    /**
     * 检查转账状态
     * @param $tradeNo
     * @return bool|int
     */
    public function transferCheck($tradeNo)
    {
        $data = [
            'method' => 'CheckOrderId',
            'OrderId' => $tradeNo
        ];
        $res = $this->requestParam($data);
        return $res['status'];
    }

    /**
     * 获取第三方钱包信息 (分为单位)
     * @return array[可下分金额，总金额]
     */
    public function getThirdWalletBalance()
    {
        $account = $this->getGameAccount();
        $data = [
            'method' => 'GetUserStatusDV',
            'Username' => $account['account'],
        ];
        $res = $this->requestParam($data);
        if ($res['status']) {
            //该会员的余额，現存結余, 不包括已经下注的金额. 精确到分
            return [bcmul($res['Balance'], 100, 0), bcmul($res['Balance'], 100, 0)];
        }

        $balance = FundsChild::where('pid', $this->wid)->where('game_type', $this->game_alias)->value('balance');
        return [0, $balance];
    }

    /***
     * 退出第三方,并回收至钱包
     * @param int $balance
     * @param string $tradeNo
     * @return array(是否成功,转出后第三方剩余金额)
     */
    public function rollOutChildThird(int $balance, string $tradeNo)
    {
        $account = $this->getGameAccount();
        $data = [
            'method' => 'DebitAllBalanceDV',
            'Username' => $account['account'],
            //'OrderId'   => 'OUT' . date('YmdHis', time()).$account['account'],
            'OrderId' => 'OUT' . $tradeNo,
        ];
        $res = $this->requestParam($data);
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
            'method' => 'CreditBalanceDV',
            'Username' => $account['account'],
            //'OrderId'       => 'IN' . date('YmdHis', time()).$account['account'],
            'OrderId' => 'IN' . $tradeNo,
            'CreditAmount' => bcdiv($balance, 100, 2),
        ];
        $res = $this->requestParam($data);
        return $res['status'];
    }

    /**
     * 限制列表
     * @throws \Exception
     */
    function QueryBetLimit()
    {
        $redisKey = 'QueryBetLimit-SA';
        $params = [
            'method' => 'QueryBetLimit',
            'Currency' => $this->config['currency'],
        ];
        $data = $this->redis->get($redisKey);
        if (empty($data)) {
            $res = $this->requestParam($params);
            $data = [];
            if ($res['status'] && isset($res['BetLimitList']) && isset($res['BetLimitList']['BetLimit'])) {
                foreach ($res['BetLimitList']['BetLimit'] as $key => $val) {
                    if ($key > 4) {
                        break;
                    }
                    $data['Set' . ($key + 1)] = $val['RuleID'];
                }
            }
            $this->redis->setex($redisKey, 86400, json_encode($data));
        }else{
            $data = json_decode($data, true);
        }
        return $data;
    }

    function SetBetLimit($Username)
    {
        $data = [
            'method' => 'SetBetLimit',
            'Username' => $Username,
            'Currency' => $this->config['currency'],
        ];
        $limitData = $this->QueryBetLimit();
        $params = array_merge($data, $limitData);
        $params['Gametype'] = 'roulette,sicbo,pokdeng,andarbahar,others';
        $res = $this->requestParam($params);
        return $res;
    }

    /**
     * 发送请求
     * @param array $param 请求参数
     * @param bool $is_generic 是否为post请求
     * @param bool $status 是否返回请求状态
     * @return array|string
     * @throws \Exception
     */
    public function requestParam(array $param, bool $is_generic = true, $status = false)
    {
        if (is_null($this->config)) {
            $ret = [
                'status' => false,
                'ErrorMsg' => 'no api config'
            ];
            GameApi::addElkLog($ret, 'SA');
            return $ret;
        }

        $md5Key = $this->config['cagent'];
        $secretKey = $this->config['pub_key'];
        $encryptKey = $this->config['key'];
        $Time = date('YmdHis', time());
        $option = [
            'method' => $param['method'],
            'Key' => $secretKey,
            'Time' => $Time
        ];
        unset($param['method']);
        $option = array_merge($option, $param);
        $queryString = http_build_query($option, '', '&');
        $crypt = new DES($encryptKey);
        $q = $crypt->encrypt($queryString);
        $s = md5($queryString . $md5Key . $Time . $secretKey);

        $url = $is_generic ? $this->config['apiUrl'] : $this->config['orderUrl'];
        $params = array(
            's' => $s,
            'q' => $q
        );
        $re = Curl::commonPost($url, null, http_build_query($params), array('Content-Type: application/x-www-form-urlencoded'));
        $re = $this->parseXML($re);
        $remark = '';
        if (is_array($re)) {
            $remark = isset($re['status']) ? 'status:' . $re['status'] : '';
            $re = json_encode($re, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        GameApi::addRequestLog($url, 'SA', array_merge($option, $params), $re, $remark);
        $res = json_decode($re, true);
        if ($status) {
            return $res;
        }
        //用户锁定强制退出
        if (isset($res) && isset($res['ErrorMsgId']) && $res['ErrorMsgId'] == 130) {
            $re = $this->quitChildGame();
            if ($re) {
                $res['status'] = false;
                $res['ErrorMsg'] = 'game error';
            }
        } elseif (isset($res) && isset($res['ErrorMsgId']) && ($res['ErrorMsgId'] == 0 || $res['ErrorMsgId'] == 113)) {
            $res['status'] = true;
        } else {
            $res['status'] = false;
        }
        return $res;
    }

    function getJackpot()
    {
        return 0;
    }
}

class DES
{
    var $key;
    var $iv;

    function __construct($key, $iv = 0)
    {
        $this->key = $key;
        if ($iv == 0) {
            $this->iv = $key;
        } else {
            $this->iv = $iv;
        }
    }

    function encrypt($str)
    {
        return base64_encode(openssl_encrypt($str, 'DES-CBC', $this->key, OPENSSL_RAW_DATA, $this->iv));
    }

    function decrypt($str)
    {
        $str = openssl_decrypt(base64_decode($str), 'DES-CBC', $this->key, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING, $this->iv);
        return rtrim($str, "\x1\x2\x3\x4\x5\x6\x7\x8");
    }

    function pkcs5Pad($text, $blocksize)
    {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }
}