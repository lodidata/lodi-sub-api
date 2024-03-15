<?php

namespace Logic\GameApi\Game;


use Logic\Define\Cache3thGameKey;
use Logic\GameApi\GameApi;
use Logic\GameApi\GameToken;
use Utils\Curl;

/**
 * CQ9电子
 * Class CQ9
 * @package Logic\GameApi\Game
 */
class CQ9 extends \Logic\GameApi\Api
{
    protected $orderTable = 'game_order_cqnine_dz';
    protected $langs = [
        'th' => 'th',
        'zh-cn' => 'zh-cn',
        'en-us' => 'en',
        'vn' => 'vn'
    ];
    protected $gameInfo = [];

    protected $platformTypes = [
        'slot' => ['id' => 74, 'game' => 'GAME', 'type' => 'CQ9', 'table' => 'game_order_cqnine_dz'],
        'fish' => ['id' => 75, 'game' => 'BY', 'type' => 'CQ9BY', 'table' => 'game_order_cqnine_by'],
        'arcade' => ['id' => 85, 'game' => 'ARCADE', 'type' => 'CQ9JJ', 'table' => 'game_order_cqnine_jj'],
        'table' => ['id' => 86, 'game' => 'TABLE', 'type' => 'CQ9TAB', 'table' => 'game_order_cqnine_table'],
    ];


    //第一步 创建游戏账号
    public function childCreateAccount(string $account, string $password)
    {

        $data = [];
        $data['account'] = $account;
        $data['password'] = $password;
        $data['nickname'] = $account;
        $res = $this->request($data, 'gameboy/player');
        if(!$res['responseStatus']){
            return false;
        }
        if ($res['status']['code'] == 0 || $res['status']['code'] == 6) {
            return true;
        }
        return false;
    }

    public function check_account_exists($account)
    {
        $action = 'gameboy/player/check/' . $account;
        $res = $this->request([], $action, false);
        if(!$res['responseStatus']){
            return false;
        }
        if (isset($res["data"]) && $res["data"] == true)
            return true;
        return false;
    }

    public function changePwd($account, $password)
    {
        $data = [];
        $data['account'] = $account;
        $data['password'] = $password;

        $res = $this->request($data, 'gameboy/player/pwd');
        if(!$res['responseStatus']){
            return false;
        }
        if ($res['status']['code'] == 0) {
            return $this->login($account, $password);
        } else {
            return false;
        }
    }

    //登录
    public function login($account, $password)
    {
        $data = [];
        $data['account'] = $account;
        $data['password'] = $password;

        $res = $this->request($data, 'gameboy/player/login');
        if(!$res['responseStatus']){
            return false;
        }
        //密码错误
        if ($res['status']['code'] == 14) {
            return $this->changePwd($account, $password);
        }
        if ($res['status']['code'] == 0 && $res['data']['usertoken']) {

            $res = $this->getLink(1, $res['data']['usertoken']);
            return $res;
        } else {
            return false;
        }
    }

    public function getLink($type = 1, $token = '')
    {

        if ($type) {
            $origin = isset($this->request->getHeaders()['HTTP_PL']) && is_array($this->request->getHeaders()['HTTP_PL']) ? current($this->request->getHeaders()['HTTP_PL']) : '';
            if (in_array($origin, ['pc', 'h5'])) {
                $is_app = false;
            } else {
                $is_app = true;
            }
            $action = 'gameboy/player/gamelink';
            $linkData = [];
            $linkData['usertoken'] = $token;
            $linkData['gamehall'] = 'CQ9';
            $linkData['gamecode'] = $this->gameInfo['kind_id'];
            $linkData['gameplat'] = isset($_SERVER['HTTP_PL']) && $_SERVER['HTTP_PL'] == 'H5' ? 'web' : 'mobile';
            $linkData['lang'] = $this->langs[LANG]?? $this->langs['en-us'];
            $linkData['app'] = $is_app ? 'Y' : 'N';
        } else {
            $action = 'gameboy/player/lobbylink';
            $linkData = [];
            $linkData['usertoken'] = $token;
            $linkData['lang'] = $this->langs[LANG]?? $this->langs['en-us'];
        }
        $res = $this->request($linkData, $action);
        return $res;
    }


    //进入游戏
    public function getJumpUrl(array $array = [])
    {

        $this->gameInfo = $array;
        //检测并创建账号
        $account = $this->getGameAccount();
        if (!$account) {
            return [
                'status' => 133,
                'message' => $this->lang->text(133),
                'url' => ''
            ];
        }

        $account_exists = $this->check_account_exists($account['account']);//检查是否已经注册
        if (!$account_exists) {
            $account_create = $this->childCreateAccount($account['account'], $account['password']);//没有则创建
            if (!$account_create) {
                return ['status' => 1, 'message' => $this->lang->text('Account creation failed') . ' 2'];
            }
        }

        $res = $this->login($account['account'], $account['password']);//登录
        if (!$res)
            return ['status' => 1, 'message' => $this->lang->text('Login failed')];
        if ($res['status']['code'] == 0 && $res['data']['url']) {
            //余额转入第三方
            $result = $this->rollInThird();
            if (!$result['status']) {
                return [
                    'status' => 886,
                    'message' => $result['msg'] ?? 'failed',
                    'url' => ''
                ];
            }
            return ['status' => 0, 'url' => $res['data']['url']];
        }
        return ['status' => 1, 'message' => $this->lang->text('Failed to get login link')];

    }

    //获取余额
    public function getThirdWalletBalance()
    {
        $account = $this->getGameAccount();
        $action = 'gameboy/player/balance/' . $account['account'];
        $res = $this->request([], $action, false);

        if ($res['responseStatus'] && isset($res['status']) && isset($res['status']['code']) && !$res['status']['code']) {
            return [bcmul($res['data']['balance'], 100, 0), bcmul($res['data']['balance'], 100, 0)];
        } else {
            $balance = \Model\FundsChild::where('pid', $this->wid)->where('game_type', $this->game_alias)->value('balance');
            return [0, $balance];
        }
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
        $batchDataSlot = [];
        $batchDataFish = [];
        $batchDataArcade = [];
        $batchDataTable = [];
        //orders列表
        $batchOrderData = [];
        //打码量配置
        $auditSetting = \Logic\Set\SystemConfig::getModuleSystemConfig('audit');
        foreach ($data as $key => $val) {
            if(!isset($this->platformTypes[$val['gametype']])){
                $this->logger->error('CQ9 gametype数据格式错误', $val);
                GameApi::addElkLog($val, $this->config['type']);
                continue;
            }

            //游戏不存在默认为电子
            if(!isset($this->platformTypes[$val['gametype']])){
                $val['gametype'] = 'slot';
            }

            $user_id = (new GameToken())->getUserId($val['account']);
            if (!$user_id) {
                continue;
            }
            $val['user_id'] = $user_id;
            unset($val['id'], $val['tid']);

            //分表
            if($val['gametype'] == 'fish'){
                $batchDataFish[] = $val;
            }elseif ($val['gametype'] == 'arcade'){
                $batchDataArcade[] = $val;
            }elseif ($val['gametype'] == 'table'){
                $batchDataTable[] = $val;
            }else{
                $batchDataSlot[] = $val;
            }

            $orders = [
                'user_id' => $user_id,
                'game' => $this->platformTypes[$val['gametype']]['game'],
                'order_number' => $val['round'],
                'game_type' => $this->platformTypes[$val['gametype']]['type'],
                'type_name' => $this->lang->text($this->platformTypes[$val['gametype']]['type']),
                'play_id' => $this->platformTypes[$val['gametype']]['id'],
                'bet' => $val['bet'],
                'profit' => $val['win'] - $val['bet'],
                'send_money' => $val['win'],
                'order_time' => $val['createtime'],
                'date' => substr($val['createtime'], 0, 10),
                'created' => date('Y-m-d H:i:s')
            ];
            $gameAduitSetting = isset($auditSetting[$orders['game']]) && $auditSetting[$orders['game']] ? bcdiv($auditSetting[$orders['game']], 100, 2) : 1;//游戏类型打码量设置，如果不存在则为1
            $orders['dml'] = $orders['bet'] * $gameAduitSetting;
            $batchOrderData[] = $orders;
        }
        $batchDataSlot && $this->addGameOrders($this->game_type, $this->platformTypes['slot']['table'], $batchDataSlot);
        $batchDataFish && $this->addGameOrders($this->game_type, $this->platformTypes['fish']['table'], $batchDataFish);
        $batchDataArcade && $this->addGameOrders($this->game_type, $this->platformTypes['arcade']['table'], $batchDataArcade);
        $batchDataTable && $this->addGameOrders($this->game_type, $this->platformTypes['table']['table'], $batchDataTable);
        $this->addGameToOrdersTable($batchOrderData);

        unset($data, $val, $key, $query);

        return true;
    }

    public function quitChildGame()
    {
        $account = $this->getGameAccount();
        $data = [];
        $data['account'] = $account['account'];

        $result = $this->request($data, 'gameboy/player/logout');
        if(!$result['responseStatus']){
            return false;
        }
        if ($result['status']['code'] == 0) {
            $this->rollOutThird();
            return true;
        } else {
            return false;
        }
    }

    public function checkMoney($data = null)
    {
        //转入金额超时则在此查询
        if (is_array($data) && $data['balance']) {
            // 查询玩家上下分订单
            $action = 'gameboy/transaction/record/' . $data['tradeNo'];
            $res = $this->request([], $action, false);
            if(!$res['responseStatus']){
                return false;
            }
            if ($res && isset($res['status'])) {
                //转入成功
                if ($res['data']['status'] == 'success') {
                    $this->updateGameMoneyError($data, bcmul($res['data']['amount'], 100, 0));
                }
                if ($res['status']['code'] == 8) {
                    //转入失败 退钱
                    $this->refundAction($data);
                }

            }
        }
    }

    public function rollOutChildThird(int $balance, string $tradeNo)
    {

        return [$this->transfer($balance, $tradeNo, 'withdraw'), $balance];
    }

    public function rollInChildThird(int $balance, string $tradeNo)
    {

        return $this->transfer($balance, $tradeNo, 'deposit');
    }

    //确认转账
    public function transfer($balance, $tradeNo, $type = '')
    {

        if (!$balance)
            return false;

        $action = 'gameboy/player/' . $type;
        $amount = bcdiv($balance, 100, 2);  //这边金额为分，  第三方金额为元
        $account = $this->getGameAccount();
        //转账
        $data = [];
        $data['account'] = $account['account'];
        $data['mtcode'] = $tradeNo;
        $data['amount'] = $amount;
        $res = $this->request($data, $action);
        if(!$res['responseStatus']){
            return false;
        }
        if ($res['status']['code'] == 0) {
            return true;
        }
        return false;
    }

    public function request(array $param, $action = '', $is_post = true)
    {
        if(is_null($this->config)){
            $ret = [
                'responseStatus' => false,
                'message' => 'no api config'
            ];
            GameApi::addElkLog($ret,'CQ9');
            return $ret;
        }

        $headers = [
            "Authorization: " . $this->config['key'],
        ];
        if ($is_post) {
            $headers[] = 'Content-type: application/x-www-form-urlencoded;charset=UTF-8';
        }

        $url = $this->config['apiUrl'];
        if ($action) {
            $url .= '/' . $action;
        }

        $queryString = urldecode(http_build_query($param, '', '&'));

        if ($is_post) {
            $re = Curl::commonPost($url, null, $queryString, $headers, true);
        } else {
            if($queryString){
                $url .= '?' . $queryString;
            }
            $re = Curl::get($url, null, true, $headers);
        }
        if ($re['status'] == 200) {
            $re['content'] = json_decode($re['content'], true);
        }
        GameApi::addRequestLog($url, 'CQ9', $param, json_encode($re, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $ret = [];
        if ($re['status'] == 200) {
            $ret = $re['content'];
            $ret['responseStatus'] = true;
        } else {
            $ret['responseStatus'] = false;
            $ret['message'] = $re['content'];
        }
        return $ret;
    }

    /**
     * 获取头奖
     * 游戏彩池金额查询
     * 各层级分别的总和，由大至小
     * @return integer
     */
    function getJackpot()
    {
        $res = $this->request([], 'gameboy/game/jackpot', false);
        if($res['responseStatus'] && $res['status']['code'] == 0){
            return $res['data'][0];
        }
        return 0;
    }
}
