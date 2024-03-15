<?php

namespace Logic\GameApi\Game;

use Logic\Define\Cache3thGameKey;
use Logic\GameApi\GameApi;
use Logic\GameApi\GameToken;
use Model\FundsChild;
use Utils\Curl;
use Model\user as UserModel;

class PP extends \Logic\GameApi\Api
{
    protected $url;
    protected $orderTable = 'game_order_pp';
    protected $langs = [
        'th' => 'th',
        'zh-cn' => 'zh',
        'en-us' => 'en',
        'es-mx' => 'pt',
        'vn' => 'vn',
        'id' => 'id',
    ];

    //第一步 创建游戏账号
    public function childCreateAccount(string $account, string $password)
    {
        $data = [
            'externalPlayerId' => $account,
            'currency' => $this->config['currency'],
        ];
        $res = $this->requestParam('player/account/create/', $data);
        //0成功 101账户已经存在
        if (isset($res['error']) && $res['error'] == 0) {
            return true;
        }
        return false;
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
        try {
            $back_url = $this->ci->get('settings')['website']['game_home_url'] ?? $_SERVER['HTTP_HOST'];
            $data = [
                'externalPlayerId' => $account['account'],
                'gameId' => $params['kind_id'],
                'language' => $this->langs[LANG]?? $this->langs['en-us'],//'th',en,zh
                'lobbyURL' => $back_url,
            ];
            $res = $this->requestParam('game/start/', $data);
            if ($res['error'] == 0) {
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
                    'url' => $res['gameURL'],
                    'message' => 'ok'
                ];
            }else{
                return [
                    'status' => -1,
                    'message' => $res['message'] ?? ' api error',
                    'url' => ''
                ];
            }
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
     * 3.1.4 查询会员状态
     * API 查询会员账号当前状态、现有额度等信息
     * @return array
     */
    public function getThirdWalletBalance()
    {
        $account = $this->getGameAccount();
        $data = [
            'externalPlayerId' => $account['account'],
        ];
        $res = $this->requestParam('balance/current/', $data);
        if (isset($res['error']) && $res['error'] == 0) {
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
        $platformTypes = [
            'Slot' => ['id' => 64, 'game' => 'GAME', 'type' => 'PP'],
            'BY' => ['id' => 65, 'game' => 'BY', 'type' => 'PPBY'],
            'ECasino' => ['id' => 66, 'game' => 'LIVE', 'type' => 'PPLIVE'],
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
            $user_id = (new GameToken())->getUserId($val['Username']);
            if (!$user_id) {
                continue;
            }
            $val['user_id'] = $user_id;
            unset($val['id'], $val['tid']);
            $batchData[] = $val;

            $orders = [
                'user_id' => $user_id,
                'game' => $platformTypes[$val['dataType']]['game'],
                'order_number' => $val['OCode'],
                'game_type' => $platformTypes[$val['dataType']]['type'],
                'type_name' => $this->lang->text($platformTypes[$val['dataType']]['type']),
                'play_id' => $platformTypes[$val['dataType']]['id'],
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
     * 验证服务接口状态
     * @return array|string
     */
    public function healthCheck()
    {
        $res = $this->requestParam('health/heartbeatCheck', [], false, null, true);
        if (isset($res['status']) && $res['status'] == 200) {
            return true;
        }
        return false;
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
            'externalPlayerId' => $account['account']
        ];
        $res = $this->requestParam('game/session/terminate/', $fields);
        if (isset($res['error']) && $res['error'] == 0) {
            return true;
        }
        return false;
    }

    public function checkMoney($data = null)
    {
        //确认转账
        $param = [];
        //转入金额超时则在此查询
        if (is_array($data) && $data['balance']) {
            // 查询玩家上下分订单
            $param['externalTransactionId'] = $this->config['cagent'] . $data['tradeNo'];
            $res = $this->requestParam('balance/transfer/status/', $param);

            if (isset($res['error']) && 0 === $res['error']) {
                //订单不存在  退钱
                if ($res['status'] == 'Not found') {
                    $this->refundAction($data);
                }
                //订单存在  把game_money_error改成已完成
                if ($res['status'] == 'Success') {
                    $this->updateGameMoneyError($data, abs(bcmul($res['amount'], 100, 0)));
                }
            }

        }
    }

    public function rollOutChildThird(int $balance, string $tradeNo)
    {
        $res = $this->transfer($balance, $tradeNo, 'OUT');
        return [$res, $balance];
    }

    public function rollInChildThird(int $balance, string $tradeNo)
    {
        return $this->transfer($balance, $tradeNo, 'IN');
    }

    /**
     * 确认转账
     * 3.1.13 额度转移
     * TransferType转账类型
     * 1: 从 游戏商 转移额度到 平台商 (不看 amount 值，全部转出)
     * 2: 从 平台商 转移额度到 游戏商
     * 3: 从 游戏商 转移额度到 平台商
     * @param $balance
     * @param $tradeNo
     * @param string $type
     * @return bool|int
     */
    public function transfer($balance, $tradeNo, $type = 'IN')
    {
        $balance = bcdiv($balance, 100, 2);  //这边金额为分，  第三方金额为元
        $account = $this->getGameAccount();

        $data = [
            'externalPlayerId' => $account['account'],
            'externalTransactionId' => $this->config['cagent'] . $tradeNo,
            'amount' => $type == 'OUT' ? bcmul($balance, -1, 2) : $balance,
        ];
        $res = $this->requestParam('balance/transfer/', $data);
        if (isset($res['error']) && $res['error'] == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 3.1.10 查询游戏清单
     */
    public function getListGames()
    {
        $fields = [
            'options' => 'GetDataTypes'
        ];
        $res = $this->requestParam('getCasinoGames/', $fields);
        return $res;
    }

    /**
     * 发送请求
     * @param string $action 请求方法
     * @param array $param 请求参数
     * @param bool $is_post 是否为post请求
     * @param array $options 请求参数 不加密码
     * @param bool $status 是否返回请求状态
     * @return array|string
     */
    public function requestParam(string $action, array $param, bool $is_post = true, $options = null, $status = false)
    {
        if(is_null($this->config)){
            $ret = [
                'error' => 99999,
                'message' => 'no api config'
            ];
            GameApi::addElkLog($ret,'PP');
            return $ret;
        }

        $param['secureLogin'] = $this->config['cagent'];
        $querystring = urldecode(http_build_query($param, '', '&'));
        //echo $querystring.PHP_EOL;
        $hash = $this->GetSignature($param);
        $querystring .= '&hash=' . $hash;
        if ($options) {
            $querystring .= urldecode(http_build_query($options, '', '&'));
        }
        $url = $this->config['apiUrl'] . $action . '?' . $querystring;
        //echo $url.PHP_EOL;die;
        if ($is_post) {
            $re = Curl::post($url, null, null, null, $status);
        } else {
            $re = Curl::get($url, null, $status);
        }
        $remark = '';
        if(is_array($re)){
            $remark = isset($re['status']) ? 'status:' . $re['status'] : '';
            $re = json_encode($re, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        GameApi::addRequestLog($url, 'PP', $param, $re, $remark);
        return json_decode($re, true);
    }

    /**
     * 发送请求
     * @param string $action 请求方法
     * @param array $param 请求参数
     * @param array $config 配置参数
     * @return array|string
     */
    public function requestOrderParam($action, array $param)
    {
        $param['login'] = $this->config['cagent'];
        $param['password'] = $this->config['key'];
        $querystring = http_build_query($param, '', '&');
        //echo $querystring.PHP_EOL;

        $url = $this->config['orderUrl'] . $action . '?' . $querystring;
        //echo $url . PHP_EOL;
        $re = Curl::get($url, null, true);
        is_array($re) && $re = json_encode($re, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        GameApi::addRequestLog($url, 'PP', $param, $re);
        return json_decode($re, true);
    }

    public function GetSignature($fields)
    {
        ksort($fields);
        $signature = md5(urldecode(http_build_query($fields, '', '&')) . $this->config['key']);
        return $signature;
    }

    /**
     * 获取用户钱包余额
     * @param $userId
     * @return array
     * @throws \Exception
     */
    public function getBalance($userId)
    {
        $balance = UserModel::getBalance($userId);
        return $balance;
    }

    /**
     * 获取头奖
     * 8.5 有效累积奖金
     * @return integer
     */
    function getJackpot()
    {
        $res = $this->requestParam('/JackpotFeeds/jackpots/', [], false);
        if (isset($res['error']) && $res['error'] == 0 && isset($res['jackpots'])) {
           return $res['jackpots'][0]['amount'];
        }
        return 0;
    }
}

