<?php

namespace Logic\GameApi\Game;

use Logic\GameApi\GameToken;
use Utils\Client;
use Logic\Define\Cache3thGameKey;
use Logic\GameApi\GameApi;
use Model\FundsChild;
use DB;
use Utils\Curl;

/**
 * TF电竞
 * Class TF
 * @package Logic\GameApi\Game
 */
class TF extends \Logic\GameApi\Api
{
    protected $orderTable = 'game_order_tf';

    /**
     * 获取游戏列表
     */
    public function getListGames()
    {
        return [];
    }

    /**
     * 无需创建账户，第三方会自动判定
     * @param string $account
     * @param string $password
     * @return bool
     */
    public function childCreateAccount(string $account, string $password)
    {
        $fields = [
            'member_code' => $account
        ];
        $res = $this->requestParam('/api/v2/members/', $fields);
        return $res ? true : false;
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

        $param = [
            'member_code' => $account['account'],
        ];
        $res = $this->requestParam('/api/v2/member-login/', $param);
        if ($res['status']) {
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
                'url' => $res['content']['launch_url'],
                'message' => 'ok'
            ];
        } else {
            return [
                'status' => -1,
                'message' => $res['content']['errors'],
                'url' => ''
            ];
        }

    }

    /**
     * 延期2分钟
     * @throws \Exception
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
            $user_id = (new GameToken())->getUserId($val['member_code']);
            if (!$user_id) {
                continue;
            }
            $val['user_id'] = $user_id;
            unset($val['id'], $val['tid']);
            $batchData[] = $val;

            $orders = [
                'user_id' => $user_id,
                'game' => 'ESPORTS',
                'order_number' => $val['order_id'],
                'game_type' => $this->game_type,
                'type_name' => $this->lang->text($this->game_type),
                'play_id' => 78,
                'bet' => bcmul($val['amount'], 100, 0),
                'profit' => bcmul($val['earnings'], 100, 0),
                'send_money' => bcmul($val['amount'] + $val['earnings'], 100, 0),
                'order_time' => $val['settlement_datetime'],
                'date' => substr($val['settlement_datetime'], 0, 10),
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
        //转入金额超时则在此查询
        if (is_array($data) && $data['balance']) {
            $params = [
                'reference_no' => $data['tradeNo']
            ];
            $res = $this->requestParam('/api/v2/transfer-status/', $params, false);
            if ($res['status']) {
                if ($res['content']['count'] == 1) {
                    $this->updateGameMoneyError($data, abs(bcmul($res['content']['results']['0']['amount'], 100, 0)));
                }
                if (!$res['content']['count']) {
                    //订单失败 或 不存在
                    $this->refundAction($data);
                }
            }

        }
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
            'member' => $account['account'],
            'operator_id' => $this->config['cagent'],
        ];
        $res = $this->requestParam('/api/v2/partner-account-logout/', $data);
        if ($res['status']) {
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
        $fields = [
            'reference_no' => $tradeNo
        ];
        $res = $this->requestParam('/api/v2/transfer-status/', $fields, false);
        if ($res['status'] && $res['content']['count'] == 1) {
            return true;
        }
        return false;
    }

    /**
     * 获取第三方钱包信息 (分为单位)
     * @return array[可下分金额，总金额]
     */
    public function getThirdWalletBalance()
    {
        $account = $this->getGameAccount();
        $data = [
            'LoginName' => $account['account'],
        ];
        $res = $this->requestParam('/api/v2/balance/', $data, false);
        if ($res['status']) {
            return [bcmul($res['content']['results'][0]['balance'], 100, 0), bcmul($res['content']['results'][0]['balance'], 100, 0)];
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
            'member' => $account['account'],
            'operator_id' => $this->config['cagent'],
            'reference_no' => $tradeNo,
            'amount' => bcdiv($balance, 100, 2),
        ];
        $res = $this->requestParam('/api/v2/withdraw/', $data);
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
            'member' => $account['account'],
            'operator_id' => $this->config['cagent'],
            'reference_no' => $tradeNo,
            'amount' => bcdiv($balance, 100, 2),
        ];
        $res = $this->requestParam('/api/v2/deposit/', $data);
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
    public function requestParam($action, array $param, bool $is_post = true, $status = true, $is_order = false)
    {
        if(is_null($this->config)){
            $ret = [
                'status' => false,
                'content' => [
                    'errors' => 'no api config'
                ]
            ];
            GameApi::addElkLog($ret,'TF');
            return $ret;
        }
        $apiUrl = $is_order ? $this->config['orderUrl'] : $this->config['apiUrl'];
        $header = [
            'Authorization:Token ' . $this->config['key'],
        ];

        $url = rtrim($apiUrl, '/') . $action;

        if ($is_post) {
            $re = Curl::post($url, null, $param, null, true, $header);
        } else {
            if ($param) {
                $url .= '?' . http_build_query($param, '', '&');
            }
            $re = Curl::get($url, null, true, $header);
        }
        GameApi::addRequestLog($url, 'TF', $param, $re['content'], isset($re['status']) ? 'status:' . $re['status']:'');
        /**
         * 3. 回应都是以 HTTP 状态码为主.
         * a. 2xx – 成功
         * b. 4xx – 请求错误
         * c. 5xx – 服务器错误
         */
        if (!is_array($re)) {
            $re['content']['errors'] = $re;
            $re['status'] = false;
        } elseif ($is_order && $re["status"] == 404) {
            //汇总无数据
            $re['status'] = true;
        } elseif (isset($re["status"]) && $re["status"] >= 200 && $re["status"] < 300) {
            $re['status'] = true;
        } else {
            $re['status'] = false;
        }
        $re['content'] = json_decode($re['content'], true);
        return $re;
    }

    function getJackpot()
    {
        return 0;
    }
}
