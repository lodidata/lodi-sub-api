<?php

namespace Logic\GameApi\Game;

use Logic\GameApi\GameApi;
use Logic\GameApi\GameToken;
use Model\FundsChild;
use Model\Orders;
use Utils\Curl;
use Model\user as UserModel;
use Logic\Define\Cache3thGameKey;

class MG extends \Logic\GameApi\Api
{
    protected $url;
    protected $orderTable = 'game_order_mg';
    protected $langs = [
        'th' => 'th-TH',
        'zh-cn' => 'zh-CN',
        'en-us' => 'en-US',
        'pt-br' => 'en-US',
        'vn' => 'vi-VN',
        'id' => 'id-ID',
        'in' => 'hi-IN',
        'my' => 'my-MM'
    ];
    protected $jwtToken = '';

    // 获取jwt
    public function getJWTToken()
    {
        $this->jwtToken = $this->redis->get('game_authorize_mg');
        if (is_null($this->jwtToken)) {
            $fields = [
                'client_id' => $this->config['cagent'],
                'client_secret' => $this->config['key'],
                'grant_type' => 'client_credentials'
            ];
            $res = $this->requestParam('/connect/token', $fields, true, true, false, true);
            if ($res['responseStatus']) {
                $this->jwtToken = $res['access_token'];
                $this->redis->setex('game_authorize_mg', 3600, $res['access_token']);
            }
        }
        return $this->jwtToken;
    }

    //创建游戏账号
    public function childCreateAccount(string $account, string $password)
    {
        $data = [
            'playerId' => $account,
        ];
        $res = $this->requestParam('/agents/' . $this->config['cagent'] . '/players', $data);
        if ($res['responseStatus']) {
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

        // 登录
        try {
            $data = [
                'platform' => 'mobile',
                'contentCode' => $params['kind_id'],
                'langCode' => $this->langs[LANG] ?? $this->langs['en-us'],//'th-TH',zh-CN,en-US
            ];
            //返回游戏位置
            $res = $this->requestParam('/agents/' . $this->config['cagent'] . '/players/' . $account['account'] . '/sessions', $data);
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
                    'url' => $res['url'],
                    'message' => 'ok'
                ];

            } else {
                throw new \Exception($res['msg']);
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
        $res = $this->requestParam('/agents/' . $this->config['cagent'] . '/players/' . $account['account'] . '?properties=balance', [], false);
        if ($res['responseStatus']) {
            if (isset($res['balance']['total'])) {
                return [bcmul($res['balance']['total'], 100, 0), bcmul($res['balance']['total'], 100, 0)];
            }
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
            118 => ['game' => 'GAME', 'type' => 'MG'],
            119 => ['game' => 'QP', 'type' => 'MGQP'],
            122 => ['game' => 'LIVE', 'type' => 'MGLIVE'],
            123 => ['game' => 'BY', 'type' => 'MGBY'],
            121 => ['game' => 'ARCADE', 'type' => 'MGJJ'],
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
            $user_id = (new GameToken())->getUserId($val['playerId']);
            if (!$user_id) {
                continue;
            }
            $val['user_id'] = $user_id;
            unset($val['id'], $val['tid']);
            $batchData[] = $val;

            $orders = [
                'user_id' => $user_id,
                'game' => $platformTypes[$val['game_id']]['game'],
                'order_number' => $val['betUID'],
                'game_type' => $platformTypes[$val['game_id']]['type'],
                'type_name' => $this->lang->text($platformTypes[$val['game_id']]['type']),
                'play_id' => $val['game_id'],
                'bet' => $val['betAmount'],
                'profit' => $val['payoutAmount'] - $val['betAmount'],
                'send_money' => $val['payoutAmount'],
                'order_time' => $val['createdTime'],
                'date' => substr($val['createdTime'], 0, 10),
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
     * 添加游戏订单错误
     * @param $game_type
     * @param $insertData
     * @param $code
     * @param $msg
     */
    public function addGameOrderError($game_type, $insertData, $code, $msg)
    {
        $tmp_err = [
            'game_type' => $game_type,
            'json' => json_encode($insertData, JSON_UNESCAPED_UNICODE),
            'error' => $msg,
        ];
        \DB::table('game_order_error')->insert($tmp_err);
        GameApi::addElkLog(['code' => $code, 'message' => $msg], $game_type);
    }

    /**
     * 登出
     * 3.1.2 注销游戏
     * @return array|bool
     * @throws \Exception
     */
    public function quitChildGame()
    {
        return true;
    }

    public function checkMoney($data = null)
    {
        return true;

        //确认转账
        $param = [];

        if (is_array($data) && $data['balance']) {
            // 查询玩家上下分订单
            $param['TransactionId'] = $this->config['cagent'] . $data['tradeNo'];
            $res = $this->requestParam('CheckTransferByTransactionId', $param, false);
            if (isset($res['ErrorCode']) && 0 === $res['ErrorCode']) {
                //成功
                if ($res['Data']['Status'] == 1) {
                    $this->updateGameMoneyError($data, abs(bcmul($res['Data']['Amount'], 100, 0)));
                }

            }
            //交易记录不存在
            if (isset($res['ErrorCode']) && 101 === $res['ErrorCode']) {
                $this->refundAction($data);
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
     * 额度转移
     * type转账类型
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
            'playerId' => $account['account'],
            'externalTransactionId' => $this->config['cagent'] . $tradeNo,
            'amount' => $balance,
            'type' => $type == 'OUT' ? 'Withdraw' : 'Deposit',
        ];
        $res = $this->requestParam('/agents/' . $this->config['cagent'] . '/WalletTransactions', $data);
        if ($res['responseStatus']) {
            return true;
        }
        return false;
    }

    /**
     * 3.1.10 查询游戏清单
     */
    public function getListGames()
    {
        $res = $this->requestParam('GetGameList', []);
        return $res;
    }

    /**
     * 发送请求
     * @param string $action 请求方法
     * @param array $param 请求参数
     * @param bool $is_post 是否为post请求
     * @param bool $status 是否返回请求状态
     * @param bool $is_header 是否带头部信息
     * @param bool $is_order 是否为获取注单
     * @return array|string
     */
    public function requestParam(string $action, array $param, bool $is_post = true, $status = true, $is_header = true, $is_login = false)
    {
        if(is_null($this->config)){
            $ret = [
                'responseStatus' => false,
                'msg' => 'no api config'
            ];
            GameApi::addElkLog($ret,'MG');
            return $ret;
        }
        $url = rtrim($is_login ? $this->config['loginUrl'] : $this->config['apiUrl'] . '/api/v1', '/') . $action;
        $headers = array(
            'Content-Type: application/x-www-form-urlencoded'
        );;
        if ($is_header) {
            $token = $this->getJWTToken();
            if (!$token) {
                return [
                    'responseStatus' => false,
                    'msg' => 'get jwt token error'
                ];
            }

            $headers = array(
                "Authorization: Bearer " . $token,
                'Content-Type: application/x-www-form-urlencoded'
            );
        }
        if ($is_post) {
            $re = Curl::commonPost($url, null, http_build_query($param), $headers, $status);
        } else {
            $queryString = http_build_query($param, '', '&');
            if ($queryString) {
                $url .= '?' . $queryString;
            }
            $re = Curl::get($url, null, $status, $headers);
        }
        GameApi::addRequestLog($url, $this->game_type, $param, json_encode($re, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $ret = json_decode($re['content'], true);
        if ($re['status'] == 200 || $re['status'] == 201) {
            $ret['responseStatus'] = true;
        } else {
            $ret['responseStatus'] = false;
            $ret['msg'] = isset($ret['error']) && isset($ret['error']['message']) ? $ret['error']['message'] : 'api error';
        }
        return $ret;
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

    function getJackpot()
    {
        return 0;
    }
}

