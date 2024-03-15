<?php

namespace Logic\GameApi\Game;

use Logic\Define\Cache3thGameKey;
use Logic\GameApi\GameApi;
use Logic\GameApi\GameToken;
use Model\FundsChild;
use Utils\Curl;

/**
 * DS88斗鸡
 * Class DS88
 * @package Logic\GameApi\Game
 */
class DS88 extends \Logic\GameApi\Api
{
    /**
     * @var string 订单表
     */
    protected $orderTable = 'game_order_ds88';


    //第一步 创建游戏账号
    public function childCreateAccount(string $account, string $password)
    {
        if (!$this->checkStatus()) {
            return false;
        }
        $data = [
            'account' => $account,
            'password' => $password,
            'name' => $account
        ];
        $res = $this->requestParam('/api/merchant/players', $data);
        if ($res['responseStatus']) {
            return true;
        }
        return false;
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
            $data = [
                "login" => $account['account'],
                "password" => $account['password'],
            ];
            $res = $this->requestParam('/api/merchant/player/login', $data);
            if ($res['responseStatus']) {
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
                    'url' => $res['game_link'],
                    'message' => 'ok'
                ];
            } else {
                return [
                    'status' => -1,
                    'message' => $res['message'],
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
            'account' => $account['account'],
        ];
        $res = $this->requestParam('/api/merchant/player/balance', $data, false);
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
        return true;
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
                'merchant_order_num' => $data['tradeNo']
            ];
            $res = $this->requestParam('/api/merchant/player/check', $params, false);
            //响应成功
            if ($res['responseStatus']) {
                if ($res['status'] == 'success') {
                    $this->updateGameMoneyError($data, abs(bcmul($res['amount'], 100,0)));
                } elseif ($res['status'] == 'failed') {
                    $this->refundAction($data);
                }
            } elseif (isset($res['networkStatus']) && $res['networkStatus'] === 400) {
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
            'account' => $account['account'],
            'amount' => bcmul(-1, bcdiv($balance, 100, 2), 2), //转账金额，最多支持2位小数，负数
            'merchant_order_num' => $tradeNo,
        ];
        $res = $this->requestParam('/api/merchant/player/withdraw', $data);
        return [$res['responseStatus'], $balance];

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
            'account' => $account['account'],
            'amount' => bcdiv($balance, 100, 2), //转账金额，最多支持2位小数，不可为负数
            'merchant_order_num' => $tradeNo,
        ];
        //转账
        $res = $this->requestParam('/api/merchant/player/deposit', $data);
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
            $user_id = (new GameToken())->getUserId($val['account']);
            if (!$user_id) {
                continue;
            }
            $val['user_id'] = $user_id;
            unset($val['id'], $val['tid']);
            $batchData[] = $val;

            $orders = [
                'user_id' => $user_id,
                'game' => 'SABONG',
                'order_number' => $val['slug'],
                'game_type' => 'DS88',
                'type_name' => $this->lang->text('DS88'),
                'play_id' => 100,
                'bet' => bcmul($val['bet_amount'], 100, 0),
                'profit' => bcmul($val['net_income'], 100, 0),
                'send_money' => bcmul($val['bet_return'], 100, 0),
                'order_time' => $val['settled_at'],
                'date' => substr($val['settled_at'], 0, 10),
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
     * @param bool $is_post 是否POST请求
     * @param bool $is_order 是否为获取注单
     * @return array|string
     */
    public function requestParam(string $action, array $param, $is_post = true, $is_order = false)
    {
        if(is_null($this->config)){
            $ret = [
                'responseStatus' => false,
                'message' => 'no api config'
            ];
            GameApi::addElkLog($ret,'DS88');
            return $ret;
        }
        $url = rtrim($is_order ? $this->config['orderUrl'] : $this->config['apiUrl'], '/') . $action;
        $headers = array(
            "Authorization:Bearar " . $this->config['key'],
            "Accept: application/json",
            "Content-Type:application/json"
        );

        $queryString = http_build_query($param, '', '&');
        if ($is_post) {
            $re = Curl::post($url, null, $param, null, true, $headers);
        } else {
            $url .= '?' . $queryString;
            $re = Curl::get($url, null, true, $headers);
        }

        if (!isset($re['status'])) {
            GameApi::addRequestLog($url, $this->game_type, $param, json_encode($re, JSON_UNESCAPED_UNICODE));
            $ret['responseStatus'] = false;
            $ret['message'] = $re['content'];
            return $ret;
        }

        $re['content'] = json_decode($re['content'], true);
        GameApi::addRequestLog($url, $this->game_type, $param, json_encode($re, JSON_UNESCAPED_UNICODE));
        $ret = $re['content'];
        $ret['networkStatus'] = $re['status'];
        //201登录成功
        if (in_array($re['status'], [200, 201])) {
            $ret['responseStatus'] = true;
        } else {
            $ret['responseStatus'] = false;
        }
        return $ret;
    }

    function getJackpot()
    {
        return 0;
    }
}

