<?php

namespace Logic\GameApi\Game;

use Logic\Define\Cache3thGameKey;
use Logic\GameApi\GameApi;
use Logic\GameApi\GameToken;
use Model\FundsChild;
use Utils\Curl;

/**
 * PB平博体育
 * Class FC
 * @package Logic\GameApi\Game
 */
class PB extends \Logic\GameApi\Api
{
    /**
     * @var string 订单表
     */
    protected $orderTable = 'game_order_pb';
    protected $langs = [
        'th' => 'th',
        'zh-cn' => 'zh_cn',
        'en-us' => 'en',
        'vn' => 'vi',
        'id' => 'id',
        'pt' => 'pt',
        'de' => 'de',
        'ja' => 'ja',
        'ko' => 'ko',
        'fr' => 'fr',
        'es-mx' => 'es',
        'ru' => 'ru',
        'tr' => 'tr',
        'hi' => 'hi',
        'ka' => 'ka',
        'uk' => 'uk',
        'hy' => 'hy',
    ];

    private $loginRedisKey='game_pb_login:';

    //第一步 创建游戏账号
    public function childCreateAccount(string $account, string $password)
    {
        if (!$this->checkStatus()) {
            return false;
        }
        /*$data = [
            'loginId' => $account,
        ];
        $res = $this->requestParam('/player/create', $data);*/
        $res = $this->loginV2($account);
        if ($res['responseStatus']) {
            return true;
        }
        return false;
    }

    /**
     * 3.3. FA003 – LoginV2 登陆V2
     * 这项服务是用以创建新用户并通过产生一个URL允许用户无需登录也能访问网站。
     * 这个服务跟FA001不同的是如果用户在系统里不存在，将会创建新用户
     * @param string $account 游戏账号
     * @return array|mixed|string
     */
    private function loginV2(string $account)
    {
        $res = $this->redis->get($this->loginRedisKey.$this->uid);
        if(is_null($res)){
            $data = [
                "loginId" => $account,
                "locale" => $this->langs[LANG]?? $this->langs['en-us'], //语言，预设值：en
            ];
            $res = $this->requestParam('/player/loginV2', $data);
            if ($res['responseStatus'] && isset($res['loginUrl'])) {
                $this->redis->setex($this->loginRedisKey.$this->uid, 15*60, json_encode($res));
            }
        }else{
            $res = json_decode($res, true);
        }
        return $res;
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
            $res = $this->loginV2($account['account']);
            if ($res['responseStatus'] && isset($res['loginUrl'])) {
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
                    'url' => isset($res['loginUrl']) ? $res['loginUrl'] : '',
                    'message' => 'ok'
                ];
            } else {
                return [
                    'status' => -1,
                    'message' => $res['message'],
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
            'userCode' => $account['account'],
        ];
        $res = $this->requestParam('/player/info', $data, false);
        //用户账号已激活
        if ($res['responseStatus'] && $res['status'] == 'ACTIVE') {
            return [bcmul($res['availableBalance'], 100, 0), bcmul($res['availableBalance'], 100, 0)];
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
            'userId' => $account['account'],
        ];
        $res = $this->requestParam('/api/transfer/logout', $fields);
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
                'transactionId' => $data['tradeNo']
            ];
            $res = $this->requestParam('/player/depositwithdraw/status', $params);
            //响应成功 status转帐状态 (SUCCESS:成功,FAILED:失败,NOT_EXISTS:不存在)
            if ($res['responseStatus']) {
                if ($res['status'] == "SUCCESS") {
                    $this->updateGameMoneyError($data, bcmul($res['amount'], 100, 0));
                } elseif(in_array($res['status'], ["FAILED", "NOT_EXISTS"]) ) {
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
        $fields = [
            'userCode' => $account['account'],
            'transactionId' => $tradeNo,
            'amount' => bcdiv($balance, 100, 2),
        ];
        $res = $this->requestParam('/player/withdraw', $fields);
        if ($res['responseStatus']) {
            $status = true;
        }else{
            $status = false;
        }
        return [$status, $balance];

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
        $fields = [
            'userCode' => $account['account'],
            'transactionId' => $tradeNo,
            'amount' => bcdiv($balance, 100, 2),
        ];
        $res = $this->requestParam('/player/deposit', $fields);
        if ($res['responseStatus']) {
            return true;
        }else{
            return false;
        }
    }

    /**
     * 查询游戏清单
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
     * 同步第三方游戏订单
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
            $user_id = (new GameToken())->getUserId($val['loginId']);
            if (!$user_id) {
                continue;
            }
            $val['user_id'] = $user_id;
            unset($val['id'], $val['tid']);
            $batchData[] = $val;

            $orders = [
                'user_id' => $user_id,
                'game' => 'SPORT',
                'order_number' => $val['wagerId'],
                'game_type' => 'PB',
                'type_name' => $this->lang->text('PB'),
                'play_id' => 114,
                'bet' => bcmul($val['stake'], 100, 0),
                'profit' => bcmul($val['winLoss'], 100, 0),
                'send_money' => bcmul($val['stake']+$val['winLoss'], 100, 0),
                'order_time' => $val['settleDateFm'],
                'date' => substr($val['settleDateFm'], 0, 10),
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
     * @param bool $is_post 是否为POST
     * @return array|string
     */
    public function requestParam(string $action, array $param, $is_post = true)
    {
        $proxy = $this->ci->get('settings')['PBProxy'];
        if(is_null($proxy)){
            $ret = [
                'responseStatus' => false,
                'message'        => 'no config PBPROXY'
            ];
            GameApi::addElkLog($ret,'PB');
            return $ret;
        }
        if(is_null($this->config)){
            $ret = [
                'responseStatus' => false,
                'message' => 'no api config'
            ];
            GameApi::addElkLog($ret,'PB');
            return $ret;
        }
        $url = rtrim($this->config['apiUrl'], '/') . $action;

        //2.4. Generate Token 产生令牌
        $header=[
            'userCode: ' . $this->config['cagent'],
            'token: ' . $this->generateToken($this->config['cagent'], $this->config['key'], $this->config['pub_key'])
        ];
        if($is_post){
            $re = Curl::post($url, null, $param, null, true, $header, $proxy);
        }else{
            $url = $url.'?'.urldecode(http_build_query($param));
            $re = Curl::get($url, null, true, $header, $proxy);
        }
        if ($re['status'] != 200) {
            $ret['responseStatus'] = false;
            $ret['networkStatus'] = $re['status'];
            $ret['message'] = $re['content'];
            GameApi::addRequestLog($url, $this->game_type, $param, json_encode($re, JSON_UNESCAPED_UNICODE));
        } else {
            $ret = json_decode($re['content'], true);
            $ret['networkStatus'] = 200;
            if(isset($ret['trace']) && !empty($ret['trace'])){
                $ret['responseStatus'] = false;
            }else{
                $ret['responseStatus'] = true;
            }
            GameApi::addRequestLog($url, $this->game_type, $param, json_encode($ret, JSON_UNESCAPED_UNICODE));
        }
        return $ret;
    }

    /**
     * 2.4. Generate Token 产生令牌
     * @param $agentCode
     * @param $agentKey
     * @param $secretKey
     * @return string
     */
    public function generateToken($agentCode, $agentKey, $secretKey)
    {
        $timestamp = time()*1000;
        $hashToken = md5($agentCode. $timestamp . $agentKey);
        $tokenPayLoad = $agentCode . '|' . $timestamp . '|' . $hashToken;
        $token = $this->encryptAES($secretKey, $tokenPayLoad);

        return $token;
    }
    private function encryptAES($secretKey, $tokenPayLoad)
    {
        $iv = "RandomInitVector";
        $encrypt = openssl_encrypt($tokenPayLoad, "AES-128-CBC", $secretKey, OPENSSL_RAW_DATA, $iv);
        return base64_encode($encrypt);
    }

    function getJackpot()
    {
        return 0;
    }
}

