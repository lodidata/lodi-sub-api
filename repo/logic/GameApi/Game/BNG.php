<?php

namespace Logic\GameApi\Game;

use Logic\Define\Cache3thGameKey;
use Logic\GameApi\GameApi;
use Logic\GameApi\GameToken;
use Model\FundsChild;
use Utils\Curl;
use Model\user as UserModel;

class BNG extends \Logic\GameApi\Api
{
    protected $orderTable = 'game_order_bng';
    protected $langs = [
        'th' => 'th',
        'zh-cn' => 'cn',
        'en-us' => 'en',
        'vn' => 'en',
    ];

    public function lobby($key)
    {
        $lobby = json_decode($this->config['lobby'], true);
        return $lobby[$key];
    }

    //第一步 创建游戏账号
    public function childCreateAccount(string $account, string $password)
    {
        $data = [
            "is_test" => false, // boolean, 预设为false，在此指玩家是否为测试或正式玩家，请注意，所有 'is_test=>false' 的玩家都会纳
            "player_id" => $account,
            "currency" => $this->config['currency'],
            "brand" => $this->lobby('brand'),
            'mode' => $this->lobby('mode'),
        ];
        $res = $this->requestParam('create_player', $data);
        if (!$res['responseStatus']) {
            return false;
        }
        if (isset($res['player_id']) && $res['player_id']) {
            return true;
        }
        return false;
    }

    /**
     * 处理url
     * @param string $token
     * @param array $data
     * @return string
     */
    public function getLink($token, $data)
    {
        $origin = isset($this->request->getHeaders()['HTTP_PL']) && is_array($this->request->getHeaders()['HTTP_PL']) ? current($this->request->getHeaders()['HTTP_PL']) : '';
        $param = [
            'ts' => time(),
            'platform' => $origin == 'pc' ? 'desktop' : 'mobile',
            'token' => $token,
            'wl' => 'transfer',
            'demo' => $data['is_freespin'],
            'game' => $data['kind_id'],
            'lang' => $this->langs[LANG]?? $this->langs['en-us'],
            'sound' => 1,
            'header' => 0, //1 为显示header，0 为隐藏商标
            'search_bar' => 1, //1 - 显示搜索栏, 0 - 隐藏搜索栏
        ];
        $queryString = http_build_query($param, '', '&');
        return $this->config['loginUrl'] . $this->config['cagent'] . '/game.html?' . $queryString;
    }

    //进入游戏 并创建玩家账户
    public function getJumpUrl(array $params = [])
    {
        $account = $this->getGameAccount();
        if (!$account) {
            return [
                'status' => 133,
                'message' => $this->lang->text(133),
                'url' => ''
            ];
        }
        //检测并创建账号
        $account_exists = $this->getThirdWalletBalance();
        if (!$account_exists) {
            $user_create = $this->childCreateAccount($account['account'], $account['password']);
            if (!$user_create) {
                return ['status' => 1, 'message' => $this->lang->text('Account creation failed') . ' 2'];
            }
        }
        //余额转入第三方
        $result = $this->rollInThird();
        if (!$result['status']) {
            return [
                'status' => 886,
                'message' => $result['msg'],
                'url' => ''
            ];
        }
        //余额转入之后获取token
        list($status, $token) = $this->get_player_token($account['account'], $account['password']);
        if (!$status) {
            return [
                'status' => 886,
                'message' => $token,
                'url' => ''
            ];
        }

        $url = $this->getLink($token, $params);
        if (!empty($token) && !empty($url)) {
            return [
                'status' => 0,
                'url' => $url,
                'message' => 'ok'
            ];
        } else {
            return ['status' => 1, 'message' => $this->lang->text('Login failed')];
        }
    }

    /**
     * 检测玩家的上线状态与余额
     * @return array
     */
    public function getThirdWalletBalance()
    {
        $account = $this->getGameAccount();
        $field = [
            "player_id" => $account['account'],
            "currency" => $this->config['currency'],
            "brand" => $this->lobby('brand'),
            'mode' => $this->lobby('mode'),
        ];
        $res = $this->requestParam('get_player', $field);

        if ($res['responseStatus']) {
            return [bcmul($res['balance'], 100, 0), bcmul($res['balance'], 100, 0)];
        }
        $balance = FundsChild::where('pid', $this->wid)->where('game_type', $this->game_alias)->value('balance');
        return [0, $balance];
    }


    /**
     * 资金从BNG账户转出
     * @param int $balance
     * @param string $tradeNo
     * @return array|bool
     */
    public function rollOutChildThird(int $balance, string $tradeNo)
    {
        $account = $this->getGameAccount();
        $data = [
            "player_id" => $account['account'],
            "currency" => $this->config['currency'],
            "brand" => $this->lobby('brand'),
            'mode' => $this->lobby('mode'),
            "uid" => $tradeNo,
            'amount' => bcdiv($balance, 100, 2),
            "type" => 'DEBIT_ALL',
        ];
        $res = $this->requestParam('transfer_balance', $data);
        if ($res['responseStatus']) {
            return [true, $balance];
        }
        return [false, $balance];
    }


    /**
     * 资金转入到BNG账户
     * @param int $balance
     * @param string $tradeNo
     * @return bool
     */
    public function rollInChildThird(int $balance, string $tradeNo)
    {
        $account = $this->getGameAccount();
        $data = [
            "player_id" => $account['account'],
            "currency" => $this->config['currency'],
            "brand" => $this->lobby('brand'),
            'mode' => $this->lobby('mode'),
            "uid" => $tradeNo,
            'amount' => bcdiv($balance, 100, 2),
            "type" => 'CREDIT',
        ];
        $res = $this->requestParam("transfer_balance", $data);
        if ($res['responseStatus']) {
            return true;
        }
        return false;
    }


    /**
     * 同步订单 实时拉单，没有延迟
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
            $user_id = (new GameToken())->getUserId($val['username']);
            if (!$user_id) {
                continue;
            }
            $val['user_id'] = $user_id;
            unset($val['id'], $val['tid']);
            $batchData[] = $val;

            $orders = [
                'user_id' => $user_id,
                'game' => 'GAME',
                'order_number' => $val['round'],
                'game_type' => 'BNG',
                'type_name' => $this->lang->text('BNG'),
                'play_id' => 99,
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
     * 强制登出玩家正在游玩的BNG游戏
     * @return array|bool
     */
    public function quitChildGame()
    {
        $account = $this->getGameAccount();
        $field = [
            "player_id" => $account['account'],
            "currency" => $this->config['currency'],
            "brand" => $this->lobby('brand'),
            'mode' => $this->lobby('mode'),
        ];
        $res = $this->requestParam('logout_player', $field);
        if (!$res['responseStatus']) {
            return false;
        }
        if (isset($res['is_online']) && $res['is_online'] == false) {
            return true;
        }
        return false;
    }

    public function checkMoney($data = null)
    {
        if (is_array($data) && $data['balance']) {
            $account = $this->getGameAccount();
            $params = [
                "player_id" => $account['account'],
                "brand" => $this->lobby('brand'),
                "uid" => $data['tradeNo'],
            ];
            $res = $this->requestParam('get_receipt', $params);
            //该 uid 的转移处理失败了。
            if ($res['status'] == 400 && $res['error'] == 'RECEIPT_NOT_FOUND') {
                // 转入失败 退钱
                $this->refundAction($data);
            }

            if (!$res['responseStatus']) {
                return false;
            }
            if (isset($res['amount'])) {
                // 转入成功
                $this->updateGameMoneyError($data, bcmul($res['amount'], 100, 0));
            } else {
                // 转入失败 退钱
                $this->refundAction($data);
            }
        }
    }

    /**
     * 生成玩家的金钥
     * @param $account
     * @param $password
     * @return array|bool
     */
    public function get_player_token($account, $password)
    {
        $data = [
            'player_id' => $account,
            "currency" => $this->config['currency'],
            "brand" => $this->lobby('brand'),
            'mode' => $this->lobby('mode'),
            "tag" => $password,
        ];
        $res = $this->requestParam('get_player_token', $data);
        if ($res['responseStatus'] && isset($res['player_token'])) {
            return [true, $res['player_token']];
        } else {
            return [false, $res['error']];
        }
    }

    /**
     * 发送请求
     * @param $action
     * @param array $param 请求参数
     * @param bool $is_order
     * @return array|string
     */
    public function requestParam($action, array $param, $is_order = false)
    {
        if(is_null($this->config)){
            $ret = [
                'responseStatus' => false,
                'error' => 'no api config'
            ];
            GameApi::addElkLog($ret,'BNG');
            return $ret;
        }
        $param['api_token'] = $this->config['key'];
        if ($is_order) {
            $url = $this->config['orderUrl'] . $this->config['cagent'] . $action;
        } else {
            $url = $this->config['apiUrl'] . $this->config['cagent'] . '/wallet/' . $this->lobby('WL') . '/' . $action;
        }
        $re = Curl::post($url, null, $param, null, true);

        if (isset($re['status']) && $re['status'] == 200) {
            $re['content'] = json_decode($re['content'], true);
        }

        GameApi::addRequestLog($url, 'BNG', $param, json_encode($re, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        if ($re['status'] == 200) {
            $ret = $re['content'];
            $ret['responseStatus'] = true;
        } else {
            if (isset($re['content']) && $this->is_json($re['content'])) {
                $re['content'] = json_decode($re['content'], true);
                $ret['error'] = $re['content']['error'];
            } else {
                $ret['error'] = $re['content'];
            }
            $ret['responseStatus'] = false;
        }
        $ret['status'] = $re['status'];
        return $ret;
    }


    private function is_json($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    function getJackpot()
    {
        return 0;
    }
}
