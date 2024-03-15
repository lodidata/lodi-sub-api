<?php

namespace Logic\GameApi\Game;

use Logic\Define\Cache3thGameKey;
use Logic\GameApi\GameApi;
use Logic\GameApi\GameToken;
use Model\FundsChild;
use Utils\Curl;
use Model\user as UserModel;

/**
 * AT电子
 * Class AT
 * @package Logic\GameApi\Game
 */
class AT extends \Logic\GameApi\Api
{
    /**
     * @var string 订单表
     */
    protected $orderTable = 'game_order_at';
    protected $jwtToken = '';

    /**
     * 取得密鑰 Get JWT token
     */
    public function getJWTToken()
    {
        $this->jwtToken = $this->redis->get('game_authorize_at');
        if (is_null($this->jwtToken)) {
            $fields = [
                'username' => $this->config['cagent'],
                'password' => $this->config['key']
            ];
            $res = $this->requestParam('/login', $fields, true, true, false);
            if ($res['responseStatus']) {
                $this->jwtToken = $res['token'];
                $this->redis->setex('game_authorize_at', 86400, $res['token']);
            }
        }
        return $this->jwtToken;
    }

    //第一步 创建游戏账号
    public function childCreateAccount(string $account, string $password)
    {
        if (!$this->checkStatus()) {
            return false;
        }
        $data = [
            'username' => $account,
            'nickname' => $account,
            //'currency' => 'PHP',
            //'platformId' => $this->config['lobby'],
        ];
        $res = $this->requestParam('/api/v1/players', $data);
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
            $back_url = $this->ci->get('settings')['website']['game_back_url'] ?? $_SERVER['HTTP_HOST'];
            $data = [
                "productId" => $params['kind_id'],
                "player" => $account['account'],
                "platformId" => $this->config['lobby'],
                "lang" => 'en',
            ];
            $res = $this->requestParam('/api/v1/games/gamelink', $data, false);
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
                    'url' => $res['data']['url'],
                    'message' => 'ok'
                ];
            } else {
                return [
                    'status' => -1,
                    'message' => $res['msg'],
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
            'player' => $account['account'],
            'isChildren' => false,
            //'platformId' => $this->config['lobby']
        ];
        $res = $this->requestParam('/api/v1/players', $data, false);
        if ($res['responseStatus']) {
            return [(int)($res['data'][0]['balance']), (int)($res['data'][0]['balance'])];
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
        $account = $this->getGameAccount();
        $fields = [
            'player' => $account['account'],
            //'platformId' => $this->config['lobby']
        ];
        $res = $this->requestParam('/api/v1/players/logout', $fields);
        if ($res['responseStatus']) {
            return true;
        }
        return false;
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
                'id' => $data['tradeNo']
            ];
            $res = $this->requestParam('/api/v1/profile/transactions', $params, false);
            //响应成功
            if ($res['responseStatus']) {
                if ($res['totalSize'] == 1) {
                    $this->updateGameMoneyError($data, $res['data'][0]['amount']);
                } else {
                    //requestId 不存在
                    $this->refundAction($data);
                }
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
            'player' => $account['account'],
            'amount' => $balance, //分为单位
            'transactionId' => $tradeNo,
            //'platformId' => $this->config['lobby']
        ];
        $res = $this->requestParam('/api/v1/players/withdraw', $data);
        if ($res['responseStatus']) {
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
            'player' => $account['account'],
            'amount' => $balance, //分为单位
            'transactionId' => $tradeNo,
            //'platformId' => $this->config['lobby']
        ];
        $res = $this->requestParam('/api/v1/players/deposit', $data);
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
        $fileds = [
            'type' => 'all',
            'lang' => 'en'
        ];
        $res = $this->requestParam('/api/v1/games', $fileds);
        if ($res['responseStatus']) {
            return $res;
        }
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
     * 拉单延迟30分钟，最大拉单区间30天
     * @return bool
     */
    public function synchronousChildData()
    {
        $platformTypes = [
            'slot' => ['id' => 88, 'game' => 'GAME', 'type' => 'AT'],
            'fish' => ['id' => 89, 'game' => 'BY', 'type' => 'ATBY'],
            'coc' => ['id' => 90, 'game' => 'ARCADE', 'type' => 'ATJJ'],
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
            $user_id = (new GameToken())->getUserId($val['player']);
            if (!$user_id) {
                continue;
            }
            $val['user_id'] = $user_id;
            unset($val['id'], $val['tid']);
            $batchData[] = $val;

            //未设置类型归电子
            if(!isset($platformTypes[$val['gameType']])){
                $val['gameType'] = 'slot';
            }

            $orders = [
                'user_id' => $user_id,
                'game' => $platformTypes[$val['gameType']]['game'],
                'order_number' => $val['order_number'],
                'game_type' => $platformTypes[$val['gameType']]['type'],
                'type_name' => $this->lang->text($platformTypes[$val['gameType']]['type']),
                'play_id' => $platformTypes[$val['gameType']]['id'],
                'bet' => $val['bet'],
                'profit' => $val['win'] - $val['bet'],
                'send_money' => $val['win'],
                'order_time' => $val['createdAt'],
                'date' => substr($val['createdAt'], 0, 10),
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
     * @param bool $is_post 是否为post请求
     * @param bool $status 是否返回请求状态
     * @param bool $is_header 是否带头部信息
     * @param bool $is_order 是否为获取注单
     * @return array|string
     */
    public function requestParam(string $action, array $param, bool $is_post = true, $status = true, $is_header = true, $is_order = false)
    {
        if(is_null($this->config)){
            $ret = [
                'responseStatus' => false,
                'msg' => 'no api config'
            ];
            GameApi::addElkLog($ret,'AT');
            return $ret;
        }

        $url = rtrim($is_order ? $this->config['orderUrl'] : $this->config['apiUrl'], '/') . $action;
        $headers = [];
        if ($is_header) {
            $token = $this->getJWTToken();
            if (!$token) {
                return [
                    'responseStatus' => false,
                    'msg' => 'get jwt token error'
                ];
            }

            $headers = array(
                "Authorization: Bearer " . $token
            );
        }
        if ($is_post) {
            $re = Curl::post($url, null, $param, null, $status, $headers);
        } else {
            $queryString = http_build_query($param, '', '&');
            if ($queryString) {
                $url .= '?' . $queryString;
            }
            $re = Curl::get($url, null, $status, $headers);
        }
        GameApi::addRequestLog($url, $this->game_type, $param, json_encode($re, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $ret = json_decode($re['content'], true);
        if ($re['status'] == 200) {
            $ret['responseStatus'] = true;
        } else {
            $ret['responseStatus'] = false;
            $ret['msg'] = isset($ret['error']) && isset($ret['error']['message']) ? $ret['error']['message'] : 'api error';
        }
        return $ret;
    }

    function getJackpot()
    {
        return 0;
    }
}

