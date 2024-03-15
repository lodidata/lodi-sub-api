<?php

namespace Logic\GameApi\Game;

use Logic\Define\Cache3thGameKey;
use Logic\GameApi\GameApi;
use Logic\GameApi\GameToken;
use Model\FundsChild;
use Utils\Curl;
use Utils\Client;
use Model\user as UserModel;

class EVORT extends \Logic\GameApi\Api
{
    protected $url;
    protected $orderTable = 'game_order_evort';
    protected $langs = [
        'th'    => 'th',
        'zh-cn' => 'zh',
        'en-us' => 'en',
        'es-mx' => 'pt',
        'vn'    => 'vn',
    ];

    protected $country = [
        'th'    => 'TH',
        'zh-cn' => 'CN',
        'en-us' => 'US',
    ];

    //第一步 创建游戏账号
    public function childCreateAccount(string $account, string $password)
    {
        return true;
    }


    //进入游戏 并创建用户
    public function getJumpUrl(array $params = [])
    {
        //检测并创建账号
        $account = $this->getGameAccount();
        if (!$account) {
            return [
                'status'  => 133,
                'message' => $this->lang->text(133),
                'url'     => ''
            ];
        }

        $back_url = $this->ci->get('settings')['website']['game_evo_back_url'];
        if(empty($back_url)){
            $back_url = $this->ci->get('settings')['website']['game_back_url'];
        }
        $data     = [
            'uuid'   => md5($account['account'] . str_random(6)),
            'player' => [
                'id'        => $account['account'],
                'update'    => false,
                'firstName' => substr($account['account'], 0, 6),
                'lastName'  => substr($account['account'], 6),
                //'nickname' => $account['account'],
                'country'   => $this->country[LANG] ?? $this->country['en-us'],
                'language'  => $this->langs[LANG] ?? $this->langs['en-us'],
                'currency'  => $this->config['currency'],
                'session'   => [
                    'id' => md5($account['account'] . str_random(6)),
                    'ip' => Client::getIp(),
                ],
            ],
            'config' => [
                'brand'   => [
                    'id'   => "1",
                    'skin' => "1"
                ],
                'game'    => [
                    'category' => $params['type'],
                    'table'    => [
                        'id' => $params['kind_id']
                    ],
                ],
                'channel' => [
                    'wrapped' => false
                ]
            ],
            'urls'   => [
                'lobby' => $back_url
            ]
        ];
        $res      = $this->requestLogin($data);
        if ($res['status'] == 200) {
            //余额转入第三方
            $result = $this->rollInThird();
            if (!$result['status']) {
                return [
                    'status'  => 886,
                    'message' => $result['msg'],
                    'url'     => ''
                ];
            }
            return [
                'status'  => 0,
                'url'     => $this->config['apiUrl'] . $res['content']['entry'],
                'message' => 'ok'
            ];
        } else {
            $res['content'] = json_decode($res['content'], true);
            return [
                'status'  => -1,
                'message' => $res['content']['errors'][0]['message'],
                'url'     => ''
            ];
        }
    }

    /**
     * 获取余额
     * 3.1.4 查询会员状态
     * API 查询会员账号当前状态、现有额度等信息
     * https://<hostname>/api/ecashier?cCode=RWA&ecID=9v30eegd1pek63755p8dpleuuxy24h3b&euID=poker1@test.com&output=1
     * @return array
     */
    public function getThirdWalletBalance()
    {
        $account = $this->getGameAccount();
        $data    = [
            'cCode' => 'RWA',
            'euID'  => $account['account'],
        ];
        $res     = $this->requestParam($data, false);
        if (isset($res['status']) && $res['status'] == 200 && isset($res['content']) && $res['content']['result'] == 'Y') {
            return [bcmul($res['content']['tbalance'], 100, 0), bcmul($res['content']['tbalance'], 100, 0)];
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

        foreach ($data as $key => $val) {
            $user_id = (new GameToken())->getUserId($val['Username']);
            if (!$user_id) {
                throw new \Exception('用户不存在' . $val['Username']);
            }
            $val['user_id'] = $user_id;
            unset($val['id'], $val['tid']);
            $batchData[] = $val;

            $orders           = [
                'user_id'      => $user_id,
                'game'         => 'GAME',
                'order_number' => $val['OCode'],
                'game_type'    => 'EVORT',
                'type_name'    => $this->lang->text('EVORT'),
                'game_id'      => 138,
                'server_id'    => 0,
                'account'      => $val['Username'],
                'bet'          => $val['betAmount'],
                'send_money'    => $val['winAmount'],
                'profit'       => $val['income'],
                'date'         => $val['gameDate'],
            ];
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

    ///api/ecashier? cCode=TRI&ecID=9v30eegd1pek63755p8dpleuuxy24h3b&euID=poker1@test.com&output=1&eTransI D=licenseeTransaction345
    public function checkMoney($data = null)
    {
        if (is_array($data) && $data['balance']) {
            $account = $this->getGameAccount();
            $params  = [
                'cCode'    => 'TRI',
                'euID'     => $account['account'],
                'eTransID' => $data['tradeNo'],
            ];
            // 查询玩家上下分订单
            $res = $this->requestParam($params, false);
            if (isset($res['status']) && $res['status'] == 200) {
                if (isset($res['content']['transaction'])) {
                    if ($res['content']['transaction']['result'] == 'Y') {
                        $this->updateGameMoneyError($data, bcmul($res['content']['transaction']['amount'], 100, 0));
                    }
                }
                if (isset($res['content']['result']) && $res['content']['result'] == 'N') {
                    //转账失败 退钱
                        $this->refundAction($data);
                }

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
        $account = $this->getGameAccount();
        $data    = [
            'cCode'    => 'EDB',
            'euID'     => $account['account'],
            'amount'   => bcdiv($balance, 100, 2),
            'eTransID' => $tradeNo,
        ];
        $res     = $this->requestParam($data, false);
        if (isset($res['status']) && $res['status'] == 200 && isset($res['content']['result']) && $res['content']['result'] == 'Y') {
            return [true, $balance];
        }
        return [false, $balance];
    }

    /**
     * 转入
     * /api/ecashier?cCode=ECR&ecID=9v30eegd1pek63755p8dpleuuxy24h3b&euID=p oker1@test.com&amount=10&eTransID=1234567890123456&createuser=Y&output=1
     * @param int $balance
     * @param string $tradeNo
     * @return array|void
     */
    public function rollInChildThird(int $balance, string $tradeNo)
    {
        $account = $this->getGameAccount();
        $data    = [
            'cCode'      => 'ECR',
            'euID'       => $account['account'],
            'amount'     => bcdiv($balance, 100, 2),
            'eTransID'   => $tradeNo,
            'createuser' => 'Y',
        ];
        $res     = $this->requestParam($data, false);
        if (isset($res['status']) && $res['status'] == 200 && isset($res['content']['result']) && $res['content']['result'] == 'Y') {
            return true;
        }
        return false;
    }

    /**
     * 验证转账状态
     * @param $tradeNo
     * @return bool
     */
    public function checkTransStatus($tradeNo)
    {
        $account = $this->getGameAccount();
        $data    = [
            'cCode'    => 'TRI',
            'euID'     => $account['account'],
            'eTransID' => $tradeNo,
        ];
        $res     = $this->requestParam($data, false);
        if (isset($res['status']) && $res['status'] == 200 && isset($res['content']['transaction']) && $res['content']['transaction']['result'] == 'Y') {
            return true;
        }
        return false;
    }

    /**
     * 获取游戏列表
     */
    public function getListGames()
    {
        return [];
    }

    /**
     * 发送请求
     * @param string $action 请求方法
     * @param array $param 请求参数
     * @param bool $is_post 是否为post请求
     * @param bool $status 是否返回请求状态
     * @return array|string
     */
    public function requestParam(array $param, bool $is_post = true, $status = true)
    {
        $param['ecID']   = $this->config['des_key'];
        $param['output'] = 1;
        $querystring     = urldecode(http_build_query($param, '', '&'));
        $url             = $this->config['apiUrl'] . '/api/ecashier?' . $querystring;
        //echo $url.PHP_EOL;die;
        if ($is_post) {
            $re = Curl::post($url, null, $param, null, $status);
        } else {
            $re = Curl::get($url, null, $status);
        }
        if (isset($re['status']) && $re['status'] == 200) {
            $re['content'] = $this->parseXML($re['content']);
        }
        is_array($re) && $re = json_encode($re, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        GameApi::addRequestLog($url, 'EVORT', $param, $re);
        return json_decode($re, true);
    }

    /**
     * 登录发送请求
     * @param array $param 请求参数
     * @return array|string
     */
    public function requestLogin(array $param)
    {
        $url = $this->config['apiUrl'] . '/ua/v1/' . $this->config['cagent'] . '/' . $this->config['key'];
        $re  = Curl::post($url, null, $param, null, true);
        if (isset($re['status']) && $re['status'] == 200) {
            $re['content'] = json_decode($re['content'], true);
        }
        is_array($re) && $re = json_encode($re, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        GameApi::addRequestLog($url, 'EVORT', $param, $re);
        return json_decode($re, true);
    }

    /**
     * 发送请求
     * @param array $param 请求参数
     * @return array|string
     */
    public function requestOrder(array $param)
    {
        $querystring = urldecode(http_build_query($param, '', '&'));
        $headers     = [
            'Authorization: Basic ' . base64_encode($this->config['cagent'] . ':' . $this->config['key'])
        ];
        $url         = $this->config['orderUrl'] . '/api/gamehistory/v1/casino/games?' . $querystring;
        //echo $url;die;
        $re = Curl::get($url, null, true, $headers);
        if (isset($re['status']) && $re['status'] == 200) {
            $re['content'] = json_decode($re['content'], true);
        }
        is_array($re) && $re = json_encode($re, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        GameApi::addRequestLog($url, 'EVORT', $param, $re, 'status:' . $re['status']);
        return json_decode($re, true);
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

