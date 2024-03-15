<?php

namespace Logic\GameApi\Game;

use Logic\GameApi\GameApi;
use Logic\GameApi\GameToken;
use Model\FundsChild;
use Utils\Curl;

/**
 * IG电子
 * Class CG
 * @package Logic\GameApi\Game
 */
class IG extends \Logic\GameApi\Api
{
    /**
     * @var string 订单表
     */
    protected $orderTable = 'game_order_ig';

    protected $playTable = 'game_order_ig_fair';

    private $trace_id;
    /**
     * @var string IG令牌
     */
    private $player_session;
    /**
     * @var string 登录动态域名
     */
    private $game_domain;

    private $langs = [
        'zh-cn' => 'en',
        'en-us' => 'en',
        'es-mx' => 'es',
        'th' => 'th',
    ];

    /**
     * 请求的唯一标识符（GUID）
     * @return string
     */
    public function guid()
    {
        //if (!$this->trace_id) {
        $str_microtime = str_replace('.', '', sprintf('%.6f', microtime(TRUE)));
        $tid = $this->ci->get('settings')['app']['tid'];
        $charid = strtoupper(md5($tid . 'o' . $this->uid . $str_microtime));
        $hyphen = chr(45); // "-"
        //chr(123)// "{"
        $this->trace_id = substr($charid, 0, 8) . $hyphen
            . substr($charid, 8, 4) . $hyphen
            . substr($charid, 12, 4) . $hyphen
            . substr($charid, 16, 4) . $hyphen
            . substr($charid, 20, 12);
        // .chr(125);// "}"
        //}
        return $this->trace_id;
    }

    /**
     * 验证session
     * @param $params
     * @return mixed
     */
    public function VerifySession($params)
    {
        global $app;
        $return = [
            'data' => null,
            'error' => null
        ];
        if (!isset($params['operator_token']) || !isset($params['secret_key']) || $params['operator_token'] != $this->config['key'] || $params['secret_key'] != $this->config['pub_key']) {
            $return['error'] = [
                'code' => 1000,
                'message' => '无效运营商'
            ];
        } else {
            $account = $this->getGameAccount();
            if (!isset($params['operator_player_session']) || $params['operator_player_session'] != md5($account['account'])) {
                $return['error'] = [
                    'code' => 703,
                    'message' => '无效玩家令牌'
                ];
            } else {

                $return['data'] = [
                    'player_name' => $account['account'],
                    'nickname' => $account['account'],
                    //'avatar' => '',
                    'currency' => $this->config['currency'],
                ];
            }

        }
        $app->getContainer()->logger->info('ig:VerifySession-return', ['params' => $params, 'return' => $return]);
        return $return;
    }

    /**
     * 创建账户
     * @param string $account
     * @param string $password
     * @return bool
     */
    public function childCreateAccount(string $account, string $password)
    {
        //账号钱包
        $res = $this->loginGame($account);
        if ($res['responseStatus'] && isset($res['data']) && isset($res['data']['player_session'])) {
            $this->player_session = $res['data']['player_session'];
            $this->game_domain = $res['data']['game_domain'];
            return true;
        }
        return false;
    }

    /**
     * 创建、登录账号
     * @param $account
     * @return array|string
     */
    public function loginGame($account)
    {
        $params = [
            'operator_player_session' => md5($account),
            'player_name' => $account,
            'currency' => $this->config['currency'],
            'nickname' => $account,
            //'avatar' => '',
            'lang' => $this->langs[LANG] ? $this->langs[LANG] : $this->langs['en-us'],
        ];
        $res = $this->requestParam('/api/cf/LoginGame', $params);
        return $res;
    }

    /**
     * 进入游戏
     * @param array $params
     * @return array
     */
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

        //登录失败
        if (is_null($this->player_session) || is_null($this->game_domain)) {
            $res = $this->loginGame($account['account']);
            if ($res['responseStatus'] && isset($res['data']) && isset($res['data']['player_session'])) {
                $this->player_session = $res['data']['player_session'];
                $this->game_domain = $res['data']['game_domain'];
            } else {
                return [
                    'status' => 886,
                    'message' => $res['error']['message'],
                    'url' => ''
                ];
            }
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
        $tid = $this->ci->get('settings')['app']['tid'];
        $origin = isset($this->request->getHeaders()['HTTP_PL']) && is_array($this->request->getHeaders()['HTTP_PL']) ? current($this->request->getHeaders()['HTTP_PL']) : '';
        $param = [
            'player_session' => $this->player_session,
            'operator_player_param' => bin2hex($tid . '-' . $this->uid),
            'game_id' => $params['kind_id'],
            'broswer' => $origin == 'pc' ? 'pc' : 'h5',
        ];
        return [
            'status' => 0,
            'url' => $this->game_domain . "/web-lobby?" . http_build_query($param, '', '&'),
            'message' => 'ok'
        ];

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
        $playTypes = [
            102 => ['game' => 'QP', 'type' => $this->game_alias],
            155 => ['game' => 'SABONG', 'type' => 'IGSABONG'],
            156 => ['game' => 'SMALL', 'type' => 'IGSMALL'],
            157 => ['game' => 'GAME', 'type' => 'IGSLOT'],
            158 => ['game' => 'LIVE', 'type' => 'IGLIVE'],
        ];
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
                'game' => $playTypes[$val['game_menu_id']]['game'] ?? 'QP',
                'order_number' => $val['OCode'],
                'game_type' => $playTypes[$val['game_menu_id']]['type'] ?? 'IG3',
                'type_name' => $this->lang->text('IG'),
                'play_id' => $val['game_menu_id'],
                'bet' => $val['betAmount'],
                'profit' => $val['income'],
                'send_money' => $val['winAmount'],
                'order_time' => $val['gameDate'],
                'date' => substr($val['gameDate'], 0, 10),
                'created' => date('Y-m-d H:i:s')
            ];
            $gameAduitSetting = isset($auditSetting[$orders['game']]) && $auditSetting[$orders['game']] ? bcdiv($auditSetting[$orders['game']], 100, 2) : 1; //游戏类型打码量设置，如果不存在则为1
            $orders['dml'] = $orders['bet'] * $gameAduitSetting;
            $batchOrderData[] = $orders;

        }
        $this->addGameOrders($this->game_type, $this->orderTable, $batchData);
        $this->addGameToOrdersTable($batchOrderData);

        unset($data, $val, $key, $query);

        return true;
    }

    /**
     * 同步超管对局详情
     * @return bool
     */
    public function synchronousChildPlayDetail()
    {
        if (!$data = $this->getSupperPlayDetail($this->config['type'])) {
            return true;
        }

        //注单列表
        $batchData = [];
        foreach ($data as $key => $val) {
            $user_id = (new GameToken())->getUserId($val['Username']);
            if (!$user_id) {
                continue;
            }
            //$val['user_id'] = $user_id;
            //unset($val['id'], $val['tid']);
            $batchData[] = [
                'user_id' => $user_id,
                'game_id' => 156,
                'kind_id' => $val['gameCode'],
                'detail' => json_decode($val['detail'], true)
            ];

        }
        $taskObj = new \Logic\GameApi\gameTask($this->ci);
        $taskObj->addGameTaskLog($batchData);
        //$this->addGameOrders($this->game_type, $this->playTable, $batchData);

        unset($data, $val, $key, $query);

        return true;
    }


    /**
     * 检测金额
     * @param null $data
     */
    public function checkMoney($data = null)
    {
        return true;
    }

    /**
     * 退出游戏
     * @return bool
     */
    public function quitChildGame()
    {
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
            'player_name' => $account['account'],
        ];
        $res = $this->requestParam('/api/cf/GetPlayerWallet', $data);
        if ($res['responseStatus']) {
            return [$res['data']['totalBalance'], $res['data']['totalBalance']];
        }

        $balance = FundsChild::where('pid', $this->wid)->where('game_type', $this->game_alias)->value('balance');
        return [$balance, $balance];
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
            'player_name' => $account['account'],
            'transfer_reference' => $tradeNo,
            'amount' => $balance,
            'currency' => $this->config['currency']
        ];
        $res = $this->requestParam('/api/cf/TransferOut', $data);
        if ($res['responseStatus']) {
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
            'player_name' => $account['account'],
            'transfer_reference' => $tradeNo,
            'amount' => $balance,
            'currency' => $this->config['currency']
        ];
        $res = $this->requestParam('/api/cf/TransferIn', $data);
        return $res['responseStatus'];
    }

    /**
     * 发送请求
     * @param string $action
     * @param array $param 请求参数
     * @param bool $is_post 是否为post请求
     * @return array|string
     */
    public function requestParam($action, array $param, bool $is_post = true)
    {
        if (is_null($this->config)) {
            $ret = [
                'responseStatus' => false,
                'error' => ['message' => 'no api config']
            ];
            GameApi::addElkLog($ret, 'IG');
            return $ret;
        }

        $option = [
            'operator_token' => $this->config['key'],
            'secret_key' => $this->config['pub_key']
        ];
        $params = array_merge($option, $param);

        $url = rtrim($this->config['apiUrl'], '/') . $action;
        $trace_id = $this->guid();
        $url .= '?trace_id=' . $trace_id;

        if ($is_post) {
            $re = Curl::post($url, null, $params, null, true);
        } else {
            $queryString = http_build_query($params, '', '&');
            if ($queryString) {
                $url .= '&' . $queryString;
            }
            $re = Curl::get($url, null, true);
        }

        if ($re['status'] == 200) {
            $re['content'] = json_decode($re['content'], true);
        }
        GameApi::addRequestLog($url, $this->game_type, $params, json_encode($re, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $ret = [];
        if ($re['status'] == 200) {
            $ret = $re['content'];
            if (is_null($ret['error'])) {
                $ret['responseStatus'] = true;
            } else {
                $ret['responseStatus'] = false;
            }
        } else {
            $ret['responseStatus'] = false;
            $ret['error']['message'] = $re['content'];
        }
        return $ret;
    }

    function getJackpot()
    {
        return 0;
    }
}