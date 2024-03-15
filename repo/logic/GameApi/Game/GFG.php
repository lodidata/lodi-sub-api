<?php

namespace Logic\GameApi\Game;

use Logic\GameApi\GameApi;
use Logic\GameApi\GameToken;
use Model\FundsChild;
use Utils\Client;
use Utils\Curl;

class GFG extends \Logic\GameApi\Api
{
    protected $url;
    protected $orderTable = 'game_order_gfg';
    protected $langs = [
        'zh-cn' => 'zh_cn',
        'en-us' => 'en_us',
        'ko' => 'ko_kr',
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
                'status' => 133,
                'message' => $this->lang->text(133),
                'url' => ''
            ];
        }
        $origins = ['pc' => '0', 'h5' => '1', 'ios' => '2', 'android' => '2'];
        $origin = isset($this->request->getHeaders()['HTTP_PL']) && is_array($this->request->getHeaders()['HTTP_PL']) ? current($this->request->getHeaders()['HTTP_PL']) : 'h5';

        $parmas = [
            'account' => $this->config['cagent'] . '_' . $account['account'],
            'gameId' => $params['kind_id'],
            'ip' => Client::getIp(),
            'agent' => $this->config['cagent'],
            'companyKey' => $this->config['des_key'],
            'platform' => $origins[$origin],
            'appUrl' => $this->ci->get('settings')['website']['game_back_url'],
            'exitUrl' => $this->ci->get('settings')['website']['game_back_url'],
            'theme' => $this->config['lobby'],
            'token' => md5($account['account'] . time()),
            'timestamp' => sprintf('%.3f', microtime(TRUE)),
            'languageType' => $this->langs[LANG] ?? $this->langs['en-us'],
        ];
        $res = $this->requestParam('login', $parmas);
        if (!$res['responseStatus']) {
            return [
                'status' => 886,
                'message' => $res['message'] ?? 'api error',
                'url' => ''
            ];
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

        return [
            'status' => 0,
            'url' => $res['data']['url'] ?? '',
            'message' => 'ok'
        ];
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
        $params = [
            'account' => $this->config['cagent'] . '_' . $account['account'],
            'agent' => $this->config['cagent'],
            'timestamp' => sprintf('%.3f', microtime(TRUE)),
            'companyKey' => $this->config['des_key'],
        ];
        $res = $this->requestParam('queryUserScore', $params);
        if (isset($res['responseStatus']) && $res['responseStatus'] && isset($res['data']['money'])) {
            return [bcmul($res['data']['money'], 100, 0), bcmul($res['data']['money'], 100, 0)];
        }
        $balance = FundsChild::where('pid', $this->wid)->where('game_type', $this->game_alias)->value('balance');
        return [0, $balance];
    }

    /**
     * 同步超管订单
     * @return bool
     * @throws \Exception
     */
    public function synchronousChildData()
    {
        if (!$data = $this->getSupperOrder($this->game_type)) {
            return true;
        }

        $platformTypes = [
            136 => ['id' => 136, 'game' => 'BY', 'type' => 'GFG'],
            145 => ['id' => 145, 'game' => 'GAME', 'type' => 'GFGSLOT'],
            146 => ['id' => 146, 'game' => 'QP', 'type' => 'GFGQP'],
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
                throw new \Exception('用户不存在' . $val['Username']);
            }
            $val['user_id'] = $user_id;
            unset($val['id'], $val['tid']);
            $batchData[] = $val;

            $orders = [
                'user_id' => $user_id,
                'game' => $platformTypes[$val['game_menu_id']]['game'],
                'order_number' => $val['OCode'],
                'game_type' =>  $platformTypes[$val['game_menu_id']]['type'],
                'type_name' => $this->lang->text($platformTypes[$val['game_menu_id']]['type']),
                'play_id' => $platformTypes[$val['game_menu_id']]['id'],
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
        $account = $this->getGameAccount();
        $parmas = [
            'player' => $account['account'] . $this->config['lobby'],
        ];
        $res = $this->requestParam('Logout', $parmas);
        if ($res['responseStatus']) {
            return true;
        }
        return false;
    }

    public function checkMoney($data = null)
    {
        if (is_array($data) && $data['balance']) {
            $account = $this->getGameAccount();
            $params = [
                'account' => $this->config['cagent'] . '_' . $account['account'],
                'agent' => $this->config['cagent'],
                'companyKey' => $this->config['des_key'],
                'orderId' => $data['tradeNo'],
                'timestamp' => sprintf('%.3f', microtime(TRUE)),
            ];

            // 查询玩家上下分订单
            $res = $this->requestParam('queryTransfeOrder', $params);

            //转账不存在
            if(isset($res['code']) && $res['code'] == 3){
                //转账失败 退钱
                $this->refundAction($data);
            }
            if (isset($res['responseStatus']) && $res['responseStatus']) {
                $this->updateGameMoneyError($data, $data['balance']);
            } elseif(isset($res['responseStatus']) && isset($res['data'])  && isset($res['data']['orderId']) && empty($res['data']['orderId'])) {
                //转账失败 退钱
                $this->refundAction($data);
            }
        }
    }

    /**
     * 创建订单号
     * @return string
     */
    public function generateChildOrderNumber()
    {
        $account = $this->getGameAccount();
        return $this->config['cagent'] . date("YmdHisv") . $account['account'];
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
        $params = [
            'account' => $this->config['cagent'] . '_' . $account['account'],
            'agent' => $this->config['cagent'],
            'companyKey' => $this->config['des_key'],
            'orderId' => $tradeNo,
            'money' => bcdiv($balance, 100, 2),
            'timestamp' => sprintf('%.3f', microtime(TRUE)),
        ];
        $res = $this->requestParam('doTransferWithdrawTask', $params);
        if ($res['responseStatus']) {
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
        $account = $this->getGameAccount();
        $params = [
            'account' => $this->config['cagent'] . '_' . $account['account'],
            'agent' => $this->config['cagent'],
            'companyKey' => $this->config['des_key'],
            'orderId' => $tradeNo,
            'money' => bcdiv($balance, 100, 2),
            'timestamp' => sprintf('%.3f', microtime(TRUE)),
        ];
        $res = $this->requestParam('doTransferDepositTask', $params);
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
        $params = [
            'agent' => $this->config['cagent'],
            'companyKey' => $this->config['des_key'],
            'timestamp' => sprintf('%.3f', microtime(TRUE)),
        ];
        $res = $this->requestParam('getGameList', $params);
        return $res;
    }

    /**
     * 发送请求
     * @param string $action
     * @param array $params 请求参数
     * @param bool $is_login 是否登录接口
     * @return array|string
     */
    public function requestParam(string $action, array $params, $is_login = false)
    {
        if(is_null($this->config)){
            $ret = [
                'responseStatus' => false,
                'message' => 'no api config'
            ];
            GameApi::addElkLog($ret,'GFG');
            return $ret;
        }

        $requestBodyString = json_encode($params, JSON_UNESCAPED_UNICODE);
        $authorization = md5($requestBodyString . $this->config['key']);

        //Send the Http request
        $url = $this->config['apiUrl'] . $action;
        //echo $url.PHP_EOL;
        $header = [
            "Content-Type: application/json",
            "Authorization:" . $authorization,
        ];
        //var_dump($header);die;
        $re = curl::commonPost($url, null,  $requestBodyString, $header, true );
        //var_dump($result);die;
        if ($re['status'] != 200) {
            $ret['responseStatus'] = false;
            $ret['netWorkStatus'] = $re['status'];
            $ret['message'] = $re['content'];
        } else {
            $ret = json_decode($re['content'], true);
            $ret['networkStatus'] = $re['status'];
            if (isset($ret['code']) && $ret['code'] === 0) {
                $ret['responseStatus'] = true;
            } else {
                $ret['responseStatus'] = false;
            }
        }

        GameApi::addRequestLog($url, 'GFG', ['params' => $params, 'header' => $header], json_encode($ret, JSON_UNESCAPED_UNICODE));
        return $ret;
    }


    function getJackpot()
    {
        return 0;
    }
}

