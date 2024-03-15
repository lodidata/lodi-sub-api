<?php

namespace Logic\GameApi\Game;

use Logic\GameApi\GameApi;
use Logic\GameApi\GameToken;
use Model\FundsChild;
use Utils\Curl;
use Model\user as UserModel;

class QT extends \Logic\GameApi\Api
{
    //中国大陆及美国不在地区范围内，默认指向墨西哥
    protected $langs = [
        'th' => 'th_TH',
        'zh-cn' => 'es_MX',
        'en-us' => 'es_MX',
        'es-mx' => 'es_MX'
    ];

    protected $country = [
        'th' => 'TH',
        'zh-cn' => 'MX',
        'en-us' => 'MX',
        'id' => 'ID',
        'vn' => 'VN',
        'pt-br' => 'PT',
        'es-mx' => 'MX'
    ];

    protected $url;
    protected $orderTable = 'game_order_qt';

    /**
     * 玩家身份验证
     * 玩家令牌的默认时效为6小时
     * @return mixed|string
     */
    public function authorize($account)
    {
        $authtoken = $this->redis->get('game_authorize_qt:'.$account);
        if (empty($authtoken)) {
            $lobby = json_decode($this->config['lobby'], true);
            $param = [
                'grant_type' => "password",
                'response_type' => "token",
                'username' => $this->config['cagent'],
                'password' => $lobby['api_password']
            ];
            $res = $this->requestParam('v1/auth/token', $param, false);
            if ($res['status']) {
                $authtoken = $res['access_token'];
                $this->redis->setex('game_authorize_qt:'.$account, $res['expires_in']/1000, $res['access_token']);
            } else {
                $authtoken = '';
            }
        }
        return $authtoken;
    }

    //第一步 创建游戏账号
    public function childCreateAccount(string $account, string $password)
    {
        $res = $this->authorize($account);
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
        $res = $this->authorize($account['account']);
        if (!$res) {
            return [
                'status' => -1,
                'message' => 'token error',
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

        $backUrl = $this->ci->get('settings')['website']['game_home_url'] ?? $_SERVER['HTTP_HOST'];
        $param = [
            'playerId' => $account['account'],
            'currency' => $this->config['currency'],
            'country' => $this->country[LANG] ?? $this->country['en-us'],
            'lang' => $this->langs[LANG] ?? $this->langs['en-us'],
            'mode' => 'real',
            'device' => 'mobile',
            'returnUrl' => $backUrl
        ];
        $header[] = 'Authorization: Bearer ' . $this->authorize($account['account']);

        $res = $this->requestParam('v1/games/'.$params['kind_id'].'/launch-url', $param, true, $header);

        if (!$res)
            return ['status' => 1, 'message' => $this->lang->text('Login failed')];
        if ($res['status'] === true && !isset($res['code'])) {
            return ['status' => 0, 'url' => $res['url']];
        }
        return ['status' => 1, 'message' => $this->lang->text('Failed to get login link')];
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
        $header[] = 'Authorization: Bearer ' . $this->authorize($account['account']);

        $res = $this->requestParam('v1/wallet/ext/'.$account['account'], [], false, $header);

        if (isset($res['status']) && $res['status'] === true) {
            if (isset($res['amount'])) {
                return [bcmul($res['amount'], 100, 0), bcmul($res['amount'], 100, 0)];
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
            'TABLEGAME' => ['id' => 131, 'game' => 'TABLE', 'type' => 'QTTAB'],
            'SLOT' => ['id' => 132, 'game' => 'GAME', 'type' => 'QT']
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

            $val['user_id'] = $user_id ?: 0;
            $val['game_type'] = $platformTypes[$val['gameCategory']]['type'] ?? 'QT';
            unset($val['id'], $val['tid']);

            $batchData[] = $val;

            $orders = [
                'user_id' => $user_id,
                'game' => $platformTypes[$val['gameCategory']]['game'] ?? 'GAME',
                'order_number' => $val['round_id'],
                'game_type' => $platformTypes[$val['gameCategory']]['type'] ?? 'QT',
                'type_name' => $this->lang->text($platformTypes[$val['gameCategory']]['type']),
                'play_id' => $platformTypes[$val['gameCategory']]['id'] ?? 132,
                'bet' => bcmul($val['totalBet'], 100, 0),
                'profit' => bcmul(bcsub($val['totalPayout'], $val['totalBet'], 2), 100, 0),
                'send_money' => bcmul($val['totalPayout'], 100, 0),
                'order_time' => $val['completed'],
                'date' => substr($val['completed'], 0, 10),
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
            'type' => $type == 'OUT' ? 'DEBIT' : 'CREDIT',
            'referenceId' => $tradeNo,
            'playerId' => $account['account'],
            'amount' => $balance,
            'currency' => $this->config['currency']
        ];
        $header[] = 'Authorization: Bearer ' . $this->authorize($account['account']);
        $res = $this->requestParam('v1/fund-transfers', $data, true, $header);

        if ($res['status'] == true) {
            //转账交易需要确认完成更新
            $data = [
                'status' => 'COMPLETED'
            ];
            $res = $this->requestParam('v1/fund-transfers/'.$res['id'].'/status', $data, true, $header, false, false, 'PUT');
            if ($res['status'] == true) {
                return true;
            } else {
                return false;
            }
        } else {
            //充值/提现失败则退还充值金额
            $data = [
                'status' => 'CANCELLED'
            ];
            $res = $this->requestParam('v1/fund-transfers/'.$res['id'].'/status', $data, true, $header, false, false, 'PUT');
            if ($res['status'] == true) {
                return false;
            }
            return false;
        }
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
     * @param array $header 请求头
     * @param array $param2 请求参数 不加密码
     * @param string $method 请求方法
     * @return array|string
     */
    public function requestParam($action, array $param, bool $is_post = true, $header = [], $status = false, $is_order = false, $method = null)
    {
        if(is_null($this->config)){
            $ret = [
                'status' => false,
                'message' => 'no api config'
            ];
            GameApi::addElkLog($ret,'QT');
            return $ret;
        }

        $apiUrl = $is_order ? $this->config['orderUrl'] : $this->config['apiUrl'];
        $header[] = 'Accept:application/json';

        $url = $apiUrl . $action;

        if ($is_post) {
            $re = Curl::post($url, null, $param, $method, null, $header);
        } else {
            if ($param) {
                $queryString = http_build_query($param, '', '&');
                $url .= '?' . $queryString;
            }
            $re = Curl::get($url, null, false, $header);
        }

        GameApi::addRequestLog($url, 'QT', $param, $re, isset($re['status']) ? 'status:' . $re['status']:'');
        $res = json_decode($re, true);

        if ($status) {
            return $res;
        }
        if (!is_array($res)) {
            $res['message'] = $re;
            $res['status'] = false;
        } elseif (isset($res["code"])) {
            $res['status'] = false;
        } else {
            $res['status'] = true;
        }
        return $res;
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