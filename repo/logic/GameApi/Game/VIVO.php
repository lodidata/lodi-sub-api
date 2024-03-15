<?php

namespace Logic\GameApi\Game;

use Logic\Define\Cache3thGameKey;
use Logic\GameApi\GameApi;
use Logic\GameApi\GameToken;
use Model\FundsChild;
use Utils\Curl;
use Utils\Client;
use Model\user as UserModel;

class VIVO extends \Logic\GameApi\Api
{
    protected $url;
    protected $orderTable = 'game_order_vivo';
    protected $langs = [
        'zh-cn' => 'zh',
        'en-us' => 'EN',
        'jp' => 'JA',
        'bg' => 'BG',
        'de' => 'DE',
        'es-mx' => 'ES',
        'it' => 'IT',
        'ro' => 'RO',
        'ru' => 'RU',
        'uk' => 'UK',
        'th' => 'TH',
        'id' => 'ID',
        'vt' => 'VT',
        'pt' => 'PT',
        'ko' => 'KO',
    ];


    //第一步 创建游戏账号
    public function childCreateAccount(string $account, string $password)
    {
        return true;
    }

    /**
     * 登录授权
     * @param string $account
     * @param string $password
     * @return array|string
     */
    public function login(string $account, string $password)
    {
        $params = [
            'LoginName' => $account,
            'PlayerPassword' => $password,
            'OperatorID' => $this->config['cagent'],
            'PlayerIP' => Client::getIp(),
        ];

        $res = $this->requestParam($params, true);
        return $res;
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

        //余额转入第三方
        $result = $this->rollInThird();
        if (!$result['status']) {
            return [
                'status' => 886,
                'message' => $result['msg'],
                'url' => ''
            ];
        }

        $login = $this->login($account['account'], $account['password']);
        if (!$login['responseStatus']) {
            return [
                'status' => 886,
                'message' => $login['msg'],
                'url' => ''
            ];
        }

        $param = [
            'token' => $login['token'],
            'operatorID' => $this->config['cagent'],
            'language' => $this->langs[LANG] ?? $this->langs['en-us'],
            'application' => 'Lobby',
        ];
        $queryString = http_build_query($param, '', '&');
        $url = $this->config['pub_key'] . '?' . $queryString;
        return [
            'status' => 0,
            'url' => $url,
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
        $res = $this->transfer('CHECK');
        if (isset($res['responseStatus']) && $res['responseStatus']) {
            return [bcmul($res['balance'], 100, 0), bcmul($res['balance'], 100, 0)];
        }
        $balance = FundsChild::where('pid', $this->wid)->where('game_type', $this->game_alias)->value('balance');
        return [0, $balance];
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
                throw new \Exception('用户不存在' . $val['Username']);
            }
            $val['user_id'] = $user_id;
            unset($val['id'], $val['tid']);
            $batchData[] = $val;

            $orders = [
                'user_id' => $user_id,
                'game' => 'LIVE',
                'order_number' => $val['OCode'],
                'game_type' => 'VIVO',
                'type_name' => $this->lang->text('VIVO'),
                'play_id' => 133,
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
        return true;
    }

    public function checkMoney($data = null)
    {
        if (is_array($data) && $data['balance']) {
            // 查询玩家上下分订单
            $res = $this->transfer('VALIDATE', $data['tradeNo'], $data['balance']);
            if (isset($res['responseStatus']) && $res['responseStatus']) {
                $this->updateGameMoneyError($data, $data['balance']);
            } else {
                //转账失败 退钱
                $this->refundAction($data);
            }
        }
    }

    /**
     * 转出
     * @param int $balance
     * @param string $tradeNo
     * @return array|bool
     */
    public function rollOutChildThird(int $balance, string $tradeNo)
    {
        $res = $this->transfer('WITHDRAW', $tradeNo, $balance);
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
        $res = $this->transfer('DEPOSIT', $tradeNo, $balance);
        if ($res['responseStatus']) {
            return true;
        }
        return false;
    }

    /**
     * 转账接口
     * @param $TransactionType DEPOSIT,WITHDRAW, CHECK, ALL, VALIDATE
     * @param int $balance
     * @param string $tradeNo
     * @param string $userName
     * @param string $userPwd
     * @return array|string
     */
    public function transfer($TransactionType, $tradeNo = '', $balance = 0, $userName = '', $userPwd = '')
    {
        if (empty($userName)) {
            $account = $this->getGameAccount();
        } else {
            $account = [
                'account' => $userName,
                'password' => $userPwd
            ];
        }
        $lobby = json_decode($this->config['lobby'], true);

        $field = [
            'CasinoID' => $lobby['CasinoID'],
            'OperatorID' => $this->config['cagent'],
            'AccountNumber' => $lobby['AccountID'],
            'AccountPin' => $lobby['AccountPin'],
            'UserName' => $account['account'],
            'UserPWD' => $account['password'],
            'UserID' => $account['account'],
            'Amount' => $balance ? bcdiv($balance, 100, 2) : 0,
            'TransactionType' => strtoupper($TransactionType),
            'TransactionID' => $tradeNo ?? GameApi::generateOrderNumber(),
        ];
        $field['hash'] = md5($field['UserName'] . $field['Amount'] . $field['TransactionID'] . $this->config['key']);

        return $this->requestParam($field);
    }


    /**
     * 发送请求
     * @param array $param 请求参数
     * @param bool $is_login 是否登录接口
     * @return array|string
     */
    public function requestParam(array $param, $is_login = false)
    {
        if(is_null($this->config)){
            $ret = [
                'responseStatus' => false,
                'msg' => 'no api config'
            ];
            GameApi::addElkLog($ret,'VIVO');
            return $ret;
        }
        $url = $is_login ? $this->config['loginUrl'] : $this->config['apiUrl'];
        //echo $url.PHP_EOL;die;
        $queryString = http_build_query($param, '', '&');
        $url .= '?' . $queryString;
        $re = Curl::get($url, null, true);

        if ($re['status'] != 200) {
            $ret['responseStatus'] = false;
            $ret['netWorkStatus'] = $re['status'];
            $ret['msg'] = $re['content'];
        } else {
            $ret = json_decode($re['content'], true);
            $ret['networkStatus'] = $re['status'];
            if (isset($ret['error']) && !empty($ret['error'])) {
                $ret['responseStatus'] = false;
            } else {
                $ret['responseStatus'] = true;
            }
        }
        GameApi::addRequestLog($url, $this->game_type, $param, json_encode($ret, JSON_UNESCAPED_UNICODE));
        return $ret;
    }


    function getJackpot()
    {
        return 0;
    }
}

