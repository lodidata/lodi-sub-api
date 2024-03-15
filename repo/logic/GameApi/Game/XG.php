<?php

namespace Logic\GameApi\Game;

use DateTime;
use DateTimeZone;
use Logic\Define\Cache3thGameKey;
use Logic\GameApi\GameApi;
use Logic\GameApi\GameToken;
use Model\FundsChild;
use Utils\Client;
use DB;
use Utils\Curl;

/**
 * XG
 * Class XG
 * @package Logic\GameApi\Game
 */
class XG extends \Logic\GameApi\Api
{

    protected $langs = [
        'zh-cn' => 'zh-CN',
        'en-us' => 'en-US',
        'zh-tn' => 'zh-TW',
    ];

    protected $orderTable = 'game_order_xg';

    /**
     * 无需创建账户，第三方会自动判定
     * @param string $account
     * @param string $password
     * @return bool
     */
    public function childCreateAccount(string $account, string $password)
    {
        $param = [
            'Account' => $account,
            'LimitStake' => $this->config['lobby'],
            'Currency' => $this->config['currency']
        ];
        $res = $this->requestParam('CreateMember', $param);
        return $res['responseStatus'];
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
        $lang = $this->langs[LANG] ?? $this->langs['en-us'];

        $params = [
            'Lang' => $lang,
            'GameId' => $params['kind_id'],
            'Account' => $account['account']
        ];

        $res = $this->requestParam('Login', $params, false);
        if ($res['responseStatus']) {
            //余额转入第三方
            $result = $this->rollInThird();
            if (!$result['status']) {
                return [
                    'status' => 886,
                    'message' => $result['msg'] ?? 'roll in error',
                    'url' => ''
                ];
            }
            return [
                'status' => 0,
                'url' => $res['Data']['LoginUrl'],
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
            $user_id = (new GameToken())->getUserId($val['Account']);
            if (!$user_id) {
                continue;
            }
            $val['user_id'] = $user_id;
            unset($val['id'], $val['tid']);
            $batchData[] = $val;

            $orders = [
                'user_id' => $user_id,
                'game' => 'LIVE',
                'order_number' => $val['WagersId'],
                'game_type' => 'XG',
                'type_name' => $this->lang->text('XG'),
                'play_id' => 109,
                'bet' => bcmul($val['BetAmount'], 100, 0),
                'profit' => bcmul($val['PayoffAmount'], 100, 0),
                'send_money' => bcmul($val['prize_amount'], 100, 0),
                'order_time' => $val['WagersTime'],
                'date' => substr($val['WagersTime'], 0, 10),
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
        $param = [
            'Account' => $account['account'],
        ];
        $res = $this->requestParam('KickMember', $param);
        if ($res['responseStatus']) {
            return true;
        }
        return false;
    }

    /**
     * 检测金额
     */
    public function checkMoney($data = null)
    {
        //转入金额超时则在此查询
        if (is_array($data) && $data['balance']) {
            $account = $this->getGameAccount();
            $params = [
                'Account' => $account['account'],
                'TransactionId' => $data['tradeNo']
            ];
            $res = $this->requestParam('CheckTransfer', $params);
            //响应成功 data.status转帐状态 (1:成功,2:失败)
            if ($res['responseStatus']) {
                if ($res['Data']['Status'] == 1) {
                    $this->updateGameMoneyError($data, bcmul($res['Data']['Amount'], 100, 0));
                } else {
                    $this->refundAction($data);
                }
                //不存在
            } else {
                $this->refundAction($data);
            }
        }
    }

    /**
     * 获取第三方钱包信息 (分为单位)
     * @return array[可下分金额，总金额]
     * @throws \Exception
     */
    public function getThirdWalletBalance()
    {
        $account = $this->getGameAccount();
        $param = [
            'Account' => $account['account'],
        ];
        $res = $this->requestParam('Quota', $param, false);
        if ($res['responseStatus']) {
            //该会员的余额，現存結余, 不包括已经下注的金额. 精确到分
            return [bcmul($res['Data']['credit'], 100, 0), bcmul($res['Data']['credit'], 100, 0)];
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
        $param = [
            'Account' => $account['account'],
            'Amount' => bcdiv($balance, 100, 2),
            'TransactionId' => $tradeNo,
            'TransferType' => 1
        ];
        $res = $this->requestParam('Transfer', $param);
        if ($res['responseStatus']) {
            if (isset($res['Data']['Status']) && $res['Data']['Status'] === 1) {
                return [true, $balance];
            }
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
        $param = [
            'Account' => $account['account'],
            'Amount' => bcdiv($balance, 100, 2),
            'TransactionId' => $tradeNo,
            'TransferType' => 2
        ];
        $res = $this->requestParam('Transfer', $param);
        if ($res['responseStatus']) {
            if (isset($res['Data']['Status']) && $res['Data']['Status'] === 1) {
                return true;
            }
        } else {
            return false;
        }
    }

    /**
     * 检查转账状态
     * @return bool|int
     */
    public function transferCheck()
    {

    }

    /**
     * 发送请求
     * @param array $params 请求参数
     * @return array|string
     * @throws \Exception
     */
    public function requestParam(string $action, array $params, $is_post = true)
    {
        if(is_null($this->config)){
            $ret = [
                'responseStatus' => false,
                'msg' => 'no api config'
            ];
            GameApi::addElkLog($ret,'XG');
            return $ret;
        }
        $url = $this->config['apiUrl'] . $action;
        $params['AgentId'] = $this->config['cagent'];
        $params['Key'] = $this->getKey($params);
        if ($is_post) {
            $re = Curl::post($url, null, $params, null, true);
        } else {
            $url = $url . '?' . urldecode(http_build_query($params));
            $re = Curl::get($url, null, true);
        }


        if ($re['status'] != 200) {
            $ret['responseStatus'] = false;
            $ret['networkStatus'] = $re['status'];
            $ret['msg'] = $re['content'];
            GameApi::addRequestLog($url, 'XG', $params, json_encode($re, JSON_UNESCAPED_UNICODE));
        } else {
            $ret = json_decode($re['content'], true);
            $ret['networkStatus'] = $re['status'];
            if (isset($ret['ErrorCode']) && $ret['ErrorCode'] === 0) {
                $ret['responseStatus'] = true;
            } else {
                $ret['responseStatus'] = false;
            }
            GameApi::addRequestLog($url, 'XG', $params, json_encode($ret, JSON_UNESCAPED_UNICODE));
        }
        return $ret;
    }

    public function getKey($data)
    {
        return $this->getRandomString(6) . md5($this->paramString($data) . $this->getKeyG()) . $this->getRandomString(6);
    }

    public function paramString($data)
    {
        if (empty($data)) {
            return null;
        }
        $str = '';
        foreach ($data as $k => $v) {
            if (empty($v)) {
                continue;
            }
            $str .= $k . '=' . $v . '&';
        }
        $str = trim($str, '&');

        return $str;
    }

    public function getKeyG()
    {
        $default_timezone = date_default_timezone_get();
        date_default_timezone_set("Etc/GMT+4");
        $day = date('ymj');
        date_default_timezone_set($default_timezone);

        $keyG = md5($day . $this->config['cagent'] . $this->config['key']);
        return $keyG;
    }

    public function getRandomString($length)
    {
        {
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $randomString = '';

            for ($i = 0; $i < $length; $i++) {
                $index = rand(0, strlen($characters) - 1);
                $randomString .= $characters[$index];
            }

            return $randomString;
        }
    }

    function getJackpot()
    {
        return 0;
    }
}
