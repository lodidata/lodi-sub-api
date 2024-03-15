<?php

namespace Logic\GameApi\Game;

use Logic\Define\Cache3thGameKey;
use Logic\GameApi\GameApi;
use Logic\GameApi\GameToken;
use Model\FundsChild;
use Utils\Curl;
use Utils\Client;
use Model\user as UserModel;

class EVOPLAY extends \Logic\GameApi\Api
{
    protected $url;
    protected $orderTable = 'game_order_evoplay';
    protected $userEVOTable = 'game_order_evoplay_user';
    protected $langs = [
        'zh-cn' => 'zh',
        'en-us' => 'en',
        'jp' => 'ja',
        'bg' => 'bg',
        'de' => 'de',
        'es-mx' => 'es',
        'it' => 'it',
        'ro' => 'ro',
        'ru' => 'ru',
        'uk' => 'uk',
    ];

    /**
     * @var string 用户ID与EVO中用户ID对应关系
     */
    private $user_evo_redis = 'game_evo_user:';


    //第一步 创建游戏账号
    public function childCreateAccount(string $account, string $password)
    {
        $params = [
            'user_name' => $account,
            'currency' => $this->config['currency'],
        ];

        $res = $this->requestParam('User/registerWithName', $params);
        if (isset($res['responseStatus']) && $res['responseStatus']) {
            $evo_user_id = $res['user_id'];
            $user_id = $this->uid;
            \DB::table($this->userEVOTable)->insert([
                'user_id' => $this->uid,
                'evo_user_id' => $res['user_id']
            ]);
            $this->redis->setex($this->user_evo_redis . $user_id, 86400, $evo_user_id);
            return true;
        }else{
            return false;
        }
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

        $evo_user_id = $this->getEVOUserId();
        if(!$evo_user_id){
            return [
                'status' => 133,
                'message' => $this->lang->text(133),
                'url' => ''
            ];
        }

        $back_url = $this->ci->get('settings')['website']['game_back_url'] ?? $_SERVER['HTTP_HOST'];
        $param = [
            'user_id' => $evo_user_id,
            'game' => $params['kind_id'],
            'settings' => [
                'back_url' => $back_url,
                'language' => $this->langs[LANG] ?? $this->langs['en-us'],
                'cash_url' => $back_url,
                'https' => 1,
                /*'extra_bonuses' => [
                    'bonus_spins' => [
                        'spins_count' => 0,
                        'bet_in_money' => 0,
                        'freespins_on_start' => [
                            'frees pins_count' => 0,
                            'bet_in_money' => 0,
                        ]
                    ]
                ],
                'extra_bonuses_settings' => [
                    'expire' => date('Y-m-d H:i:s'),
                    'registration_id' => '',
                    'bypass' => [
                        'promoCode' => ''
                    ]
                ],
                'payout' => 0*/
            ],
            'denomination' => 1,
            'return_url_info' => 0
        ];
        $project = $this->config['cagent'];
        $version = $this->config['lobby'];
        $param['signature'] = $this->getSignature($project, $version, $param, $this->config['key']);
        $param['project'] = $project;
        $param['version'] = $version;
        $url = $this->config['apiUrl'] . '/Game/getIFrameURLAdvanced' ;
        $queryString = http_build_query($param, '', '&');
        $url .= '?' . $queryString;
        //$res = $this->requestParam('Game/getIFrameURLAdvanced', $data, false);
        //if (isset($res['responseStatus']) && $res['responseStatus']) {
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
                'url' => $url,
                'message' => 'ok'
            ];
       /* } else {
            return [
                'status' => -1,
                'message' => $res['error']['message'],
                'url' => ''
            ];
        }*/
    }

    /**
     * 获取余额
     * 3.1.4 查询会员状态
     * API 查询会员账号当前状态、现有额度等信息
     * @return array
     */
    public function getThirdWalletBalance()
    {
        $data = [
            'user_id' => $this->getEVOUserId(),
        ];
        $res = $this->requestParam('User/infoById', $data);
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

        $platformTypes = [
            125 => ['id' => 125, 'game' => 'GAME', 'type' => 'EVOPLAY'],
            126 => ['id' => 126, 'game' => 'TABLE', 'type' => 'EVOPLAYTAB'],
            127 => ['id' => 127, 'game' => 'SMALL', 'type' => 'EVOPLAYSMALL'],
        ];

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
                'game' => $platformTypes[$val['game_id']]['game'],
                'order_number' => $val['OCode'],
                'game_type' => $platformTypes[$val['game_id']]['type'],
                'type_name' => $this->lang->text($platformTypes[$val['game_id']]['type']),
                'play_id' => $val['game_id'],
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
            $params = [
                'user_id' => $this->getEVOUserId(),
                'type' => $data['transfer_type'] == 'in' ? 'deposit' : 'withdrawal',
            ];
            // 查询玩家上下分订单
            $res = $this->requestParam('Finance/getTransactions', $params);
            if (isset($res['responseStatus']) && $res['responseStatus']) {
                unset($res['responseStatus'], $res['networkStatus']);
                $tradeNoArr = array_column($res, 'wl_transaction_id');
                if (in_array($data['tradeNo'], $tradeNoArr)) {
                    $this->updateGameMoneyError($data, $data['balance']);
                } else {
                    //转账失败 退钱
                    $this->refundAction($data);
                }

            }elseif(isset($res['error']) && isset($res['error']['code']) && $res['error']['code'] == 0){
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
        $data = [
            'wl_transaction_id' => $tradeNo,
            'user_id' => $this->getEVOUserId(),
            'sum' => bcdiv($balance, 100, 2),
            'currency' => $this->config['currency'],
        ];
        $res = $this->requestParam('Finance/withdrawal', $data);
        if ($res['responseStatus'] && isset($res['transaction_id']) && $res['transaction_id'] > 0) {
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
        $data = [
            'wl_transaction_id' => $tradeNo,
            'user_id' => $this->getEVOUserId(),
            'sum' => bcdiv($balance, 100, 2),
            'currency' => $this->config['currency'],
        ];
        $res = $this->requestParam('Finance/deposit', $data);
        if ($res['responseStatus'] && isset($res['transaction_id']) && $res['transaction_id'] > 0) {
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
     * @param bool $is_post
     * @return array|string
     */
    public function requestParam($action, array $param, $is_post = true)
    {
        if(is_null($this->config)){
            $ret = [
                'responseStatus' => false,
                'msg' => 'no api config'
            ];
            GameApi::addElkLog($ret,'EVOPLAY');
            return $ret;
        }
        $project = $this->config['cagent'];
        $version = $this->config['lobby'];
        $param['signature'] = $this->getSignature($project, $version, $param, $this->config['key']);
        $param['project'] = $project;
        $param['version'] = $version;
        $url = $this->config['apiUrl'] . '/' . $action;
        //echo $url.PHP_EOL;die;
        if($is_post){
            $re = Curl::post($url, null, $param, null, true);
        }else{
            $queryString = http_build_query($param, '', '&');
            $url .= '?'.$queryString;
            $re = Curl::get($url, null, true);
        }
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



    function getSignature($project_id, $api_version, array $required_args, $secrete_key)
    {
        $md5 = array();
        $md5[] = $project_id;
        $md5[] = $api_version;
        $required_args = array_filter($required_args, function ($val) {
            return !($val === null || (is_array($val) && !$val));
        });

        foreach ($required_args as $required_arg) {
            if (is_array($required_arg)) {
                if (count($required_arg)) {
                    $recursive_arg = '';
                    array_walk_recursive($required_arg, function ($item) use (& $recursive_arg) {
                        if (!is_array($item)) {
                            $recursive_arg .= ($item . ':');
                        }
                    });
                    $md5[] = substr($recursive_arg, 0, strlen($recursive_arg) - 1); // get rid of last colon-sign
                } else {
                    $md5[] = '';
                }
            } else {
                $md5[] = $required_arg;
            }
        };

        $md5[] = $secrete_key;
        $md5_str = implode('*', $md5);
        return md5($md5_str);
    }

    /**
     * 获取EVO中的user_id
     * @return int|mixed
     */
    private function getEVOUserId()
    {
        $user_id = (int)$this->uid;
        if ($user_id === 0) {
            return 0;
        }

        $evo_user_id = $this->redis->get($this->user_evo_redis . $user_id);
        if (is_null($evo_user_id) || $evo_user_id === 0) {
            $evo_user_id = \DB::table($this->userEVOTable)->where('user_id', $user_id)->value('evo_user_id');
            $this->redis->setex($this->user_evo_redis . $user_id, 86400, $evo_user_id);
        }
        return $evo_user_id;
    }

    function getJackpot()
    {
        return 0;
    }
}

