<?php

namespace Logic\GameApi\Game;

use Logic\Define\Cache3thGameKey;
use Logic\GameApi\GameApi;
use Logic\GameApi\GameToken;
use Model\FundsChild;
use Utils\Curl;
use Model\user as UserModel;

class PNG extends \Logic\GameApi\Api
{
    protected $url;
    protected $orderTable = 'game_order_png';
    protected $langs = [
        'th' => 'th-TH',
        'zh-cn' => 'zh-CN',
        'en-us' => 'en-US',
        'es-mx' => 'pt_BR',
        'id' => 'id-ID',
    ];

    protected $country = [
        'th' => 'TH',
        'zh-cn' => 'CN',
        'en-us' => 'US',
        'id' => 'ID',
        'vn' => 'VN',
        'es-mx' => 'PT'
    ];

    //第一步 创建游戏账号
    public function childCreateAccount(string $account, string $password)
    {
        $lobby = json_decode($this->config['lobby'], true);
        $fields = [
            "UserInfo" => [
                'ExternalUserId' => $account,
                'Username' => $account,
                'Nickname' => $account,
                'Currency' => $this->config['currency'],
                'Country' => $this->country[LANG] ?? $this->country['en-us'],
                'Birthdate' => '2000-01-01',
                'Registration' => date('Y-m-d', time()),
                'BrandId' => $lobby['brand'],
                'Language' => $this->langs[LANG] ?? $this->langs['en-us'],
                "IP" => "",
                "Locked" => false,
                "Gender" => ""
            ]
        ];
        $res = $this->requestParam('RegisterUser', $fields);
        if ($res['status']) {
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
                'message' => $this->lang->text(116),
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
            $ticket = $this->GetTicket();
            if (!$ticket) {
                return [
                    'status' => 116,
                    'message' => $this->lang->text(116),
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
            $lobby = json_decode($this->config['lobby'], true);
            $back_url = $this->ci->get('settings')['website']['game_back_url'] ?? $_SERVER['HTTP_HOST'];
            $data = [
                'pid' => $lobby['pid'],
                "ticket" => $ticket,
                "practice" => 0,
                "gameid" => $params['kind_id'],
                "lang" => $this->langs[LANG] ?? $this->langs['en-us'],
                "channel" => 'mobile',
                "origin" => $back_url,
                'brand' => $lobby['brand'],
            ];
            $querystring = urldecode(http_build_query($data, '', '&'));
            $url = $this->config['loginUrl'] . "/casino/ContainerLauncher?" . $querystring;
            return [
                'status' => 0,
                'url' => $url,
                'message' => 'ok'
            ];
        } catch (\Exception $e) {
            return [
                'status' => -1,
                'message' => $e->getMessage(),
                'url' => ''
            ];
        }
    }

    /**
     * 4.7 GetTicket （获取 Ticket )
     * 通过呼叫 GetTicket 获得启动游戏的会话令牌。
     * @return string|bool
     */
    public function GetTicket()
    {
        $account = $this->getGameAccount();
        $data = [
            'ExternalUserId' => $account['account'],
        ];
        $res = $this->requestParam('GetTicket', $data);
        if ($res['status']) {
            return $res['content']['Ticket'];
        } elseif($res['status'] === false && isset($res['message']['faultstring']) && $res['message']['faultstring']=="Unknown user") {
            $this->childCreateAccount($account['account'], '');
        }else{
            return false;
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
            'ExternalUserId' => $account['account'],
        ];
        $res = $this->requestParam('Balance', $data);
        if ($res['status']) {
            $balance = $res['content']['UserBalance']['Real'];
            return [bcmul($balance, 100, 0), bcmul($balance, 100, 0)];
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
                continue;
            }
            $val['user_id'] = $user_id;
            unset($val['id'], $val['tid']);
            $batchData[] = $val;

            $orders = [
                'user_id' => $user_id,
                'game' => 'GAME',
                'order_number' => $val['OCode'],
                'game_type' => 'PNG',
                'type_name' => $this->lang->text('PNG'),
                'play_id' => 68,
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

    /**
     * 检测是否有转入转出失败的记录
     * @param null $data
     */
    public function checkMoney($data = null)
    {
        return true;
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
            'ExternalUserId' => $account['account'],
            'Amount' => bcdiv($balance, 100, 2),
            "Currency" => $this->config['currency'],
            'ExternalTransactionId' => $this->config['cagent'] . $tradeNo,
        ];
        $res = $this->requestParam('Debit', $data);
        if ($res['status']) {
            return [true, $balance];
        } else {
            return [false, $balance];
        }
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
            'ExternalUserId' => $account['account'],
            'Amount' => bcdiv($balance, 100, 2),
            "Currency" => $this->config['currency'],
            'ExternalTransactionId' => $this->config['cagent'] . $tradeNo,
            "Game" => "",
            "ExternalGameSessionId" => ""
        ];
        $res = $this->requestParam('Credit', $data);
        if ($res['status']) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 检查转账状态
     * @param $tradeNo
     * @return bool|int
     */
    public function transferCheck($tradeNo)
    {
        return true;
    }

    /**
     * 3.1.10 查询游戏清单
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
     * 发送请求
     * @param string $action 请求方法
     * @param array $param 请求参数
     * @return array|string
     */
    public function requestParam($action, array $param)
    {
        if (is_null($this->config)) {
            $ret = [
                'status' => false,
                'message' => [
                    'faultcode' => 99999,
                    'faultstring' => 'no api config',
                ]
            ];
            GameApi::addElkLog($ret, 'PNG');
            return $ret;
        }
        $wsdl = $this->config['apiUrl'];
        $location = $this->config['orderUrl'];
        try {
            $options = [
                "location" => $location,
                "trace" => 1,
                "login" => $this->config['cagent'],
                "password" => $this->config['key']
            ];
            $client = new \SoapClient($wsdl, $options);
            $content = $client->__soapCall($action, [$param]);
            $res = [
                'status' => true,
                'content' => $this->object_to_array($content)
            ];
        } catch (\SoapFault $fault) {
            $res = [
                'status' => false,
                'message' => [
                    'faultcode' => $fault->faultcode,
                    'faultstring' => $fault->faultstring,
                ]
            ];
            //var_dump($client->__getLastRequest());
        }
        //var_dump($res);die;
        GameApi::addRequestLog($action, 'PNG', $param, json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
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

    public function object_to_array($obj)
    {
        $_arr = is_object($obj) ? get_object_vars($obj) : $obj;
        $arr = null;
        foreach ($_arr as $key => $val) {
            $val = (is_array($val)) || is_object($val) ? $this->object_to_array($val) : $val;
            $arr[$key] = $val;
        }
        return $arr;
    }

    function getJackpot()
    {
        return 0;
    }
}

