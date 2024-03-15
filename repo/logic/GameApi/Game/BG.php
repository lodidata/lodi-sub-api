<?php

namespace Logic\GameApi\Game;

use Logic\GameApi\GameApi;
use Logic\GameApi\GameToken;
use Model\FundsChild;
use Utils\Client;
use Utils\Curl;

/**
 * BG视讯
 * Class BG
 * @package Logic\GameApi\Game
 */
class BG extends \Logic\GameApi\Api
{
    protected $langs = [
        'th' => 'th_TH',
        'zh-cn' => 'zh_CN',
        'en-us' => 'en_US',
        'vn' => 'vi_VN',
        'ko' => 'ko_KR',
        'tw' => 'zh_TW',
        'id' => 'id_ID',
        'my' => 'ms_MY',
        'es-mx' => 'es_ES',
    ];
    protected $orderTable = 'game_order_bg';


    /**
     * 无需创建账户，第三方会自动判定
     * @param string $account
     * @param string $password
     * @return bool
     * @throws \Exception
     */
    public function childCreateAccount(string $account, string $password)
    {
        //自建代理
        $lobby = json_decode($this->config['lobby'],true);
        $param = [
            'loginId' => $account,
            'agentLoginId' => $lobby['agent'],
            'nickname' => $account,
        ];
        $res = $this->requestParam('open.user.create', $param, true, false);
        if($res['responseStatus'] && isset($res['result']) && isset($res['result']['success']) && $res['result']['success'] === true){
            return true;
        }
        return false;
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
        $back_url = $this->ci->get('settings')['website']['game_back_url'] ?? $this->ci->get('settings')['website']['game_back_index'];
        $data = [
            'loginId' => $account['account'],
            'locale' => $this->langs[LANG]?? $this->langs['en-us'],
            'isMobileUrl' => 1,
            'isHttpsUrl' => 1,
            'returnUrl' => urlencode($back_url),
            'fromIp' => Client::getIp()
        ];
        $res = $this->requestParam('open.video.game.url', $data, true, false, ['loginId']);
        if ($res['responseStatus']) {
            //余额转入第三方
            $result = $this->rollInThird();
            if (!$result['status']) {
                return [
                    'status' => 886,
                    'message' => $result['msg'] ?? 'roll in error',
                    'url' => ''
                ];
            }
            return [
                'status' => 0,
                'url' => $res['result'],
                'message' => 'ok'
            ];
        } else {
            return [
                'status' => -1,
                'message' => isset($res['error']) && isset($res['error']['message']) && $res['error']['message'] ?? 'login error',
                'url' => ''
            ];
        }
    }

    /**
     * 延期2分钟
     * @throws \Exception
     */
    public function synchronousChildData()
    {
        if (!$data = $this->getSupperOrder($this->config['type'])) {
            return true;
        }

        $platformTypes = [
            'LIVE' => ['id' => 112, 'game' => 'LIVE', 'type' => 'BG'],
            'BY' => ['id' => 139, 'game' => 'BY', 'type' => 'BGBY']
        ];


        //注单列表
        $batchData = [];
        //orders列表
        $batchOrderData = [];

        //打码量配置
        $auditSetting = \Logic\Set\SystemConfig::getModuleSystemConfig('audit');

        foreach ($data as $key => $val) {
            $user_id= (new GameToken())->getUserId($val['loginId']);
            if(!$user_id) continue;
            $val['user_id'] = $user_id;
            unset($val['id'], $val['tid']);
            $batchData[] = $val;

            $orders = [
                'user_id' => $user_id,
                'game' => $platformTypes[$val['gameCategory']]['game'] ?? 'LIVE',
                'order_number' => $val['orderId'],
                'game_type' => $platformTypes[$val['gameCategory']]['type'] ?? 'BG',
                'type_name' => $platformTypes[$val['gameCategory']]['type'] ?? 'BG',
                'play_id' => $platformTypes[$val['gameCategory']]['id'] ?? 112,
                'bet' => bcmul($val['bAmount'], 100, 0),
                'profit' => bcmul($val['payment'], 100, 0),
                'send_money' =>  bcmul($val['aAmount'], 100, 0),
                'date' => $val['orderTime'],
                'order_time' => $val['orderTime'],
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
     * 退出游戏
     * @return bool
     * @throws \Exception
     */
    public function quitChildGame()
    {
        $account = $this->getGameAccount();
        $data = [
            'loginId' => $account['account'],
        ];
        $res = $this->requestParam('open.user.logout', $data,  true, false,  ['loginId']);
        if ($res['responseStatus'] && isset($res['result']) && $res['result'] == 1) {
            return true;
        }
        return false;
    }

    /**
     * 检测金额
     * @param null $data
     * @throws \Exception
     */
    public function checkMoney($data = null)
    {
        $lobby = json_decode($this->config['lobby'],true);
        if (is_array($data) && $data['balance']) {
            $account = $this->getGameAccount();

            $params = [
                'loginId' => $account['account'],
                'agentLoginId' => $lobby['agent'],
                'bizId' => $data['tradeNo'],
            ];
            $res = $this->requestParam('open.balance.transfer.query', $params, false, true);
            if (isset($res['result']) && isset($res['result']['total']) && $res['result']['total'] == 1) {
                    $this->updateGameMoneyError($data, bcmul(abs($res['result']['stats']['expenditureAmount']), 100, 0));
            }else{
                //订单不存在及失败
                $this->refundAction($data);
            }
        }
    }


    /**
     * 获取第三方钱包信息 (分为单位)
     * @return array[可下分金额，总金额]
     * @throws \Exception
     */
    public function getThirdWalletBalance()
    {
        $account = $this->getGameAccount();
        $data = [
                'loginId' => $account['account'],
        ];
        $res = $this->requestParam('open.balance.get', $data, true, false, ['loginId']);
        if($res['responseStatus'] && isset($res['result'])){
            //该会员的余额，現存結余, 不包括已经下注的金额. 精确到分
            return [bcmul($res['result'], 100, 0), bcmul($res['result'], 100, 0)];
        }

        $balance = FundsChild::where('pid', $this->wid)->where('game_type', $this->game_alias)->value('balance');
        return [0, $balance];
    }

    /***
     * 退出第三方,并回收至钱包
     * @param int $balance
     * @param string $tradeNo
     * @return array(是否成功,转出后第三方剩余金额)
     * @throws \Exception
     */
    public function rollOutChildThird(int $balance, string $tradeNo)
    {
        $account = $this->getGameAccount();
        $data = [
            'bizId' => $tradeNo,
            'loginId' => $account['account'],
            'amount' => bcdiv(-1*$balance, 100, 2),
            'checkBizId' => 1, //是否检查转账业务ID的唯一性. 1: 检查; 0: 不检查(默认)
        ];
        $res = $this->requestParam('open.balance.transfer', $data, true, false, ['loginId', 'amount']);
        if($res['responseStatus'] && isset($res['id']) && !empty($res['id'])){
            return [true, $balance];
        }else{
            return [false, $balance];
        }
    }

    /**
     * 进入第三方，并转入钱包
     * @param int $balance
     * @param string $tradeNo
     * @return bool|int
     * @throws \Exception
     */
    function rollInChildThird(int $balance, string $tradeNo)
    {
        $account = $this->getGameAccount();
        $data = [
            'bizId' => $tradeNo,
            'loginId' => $account['account'],
            'amount' => bcdiv($balance, 100, 2),
            'checkBizId' => 1, //是否检查转账业务ID的唯一性. 1: 检查; 0: 不检查(默认)
        ];
        $res = $this->requestParam('open.balance.transfer', $data,true, false, ['loginId', 'amount']);
        if($res['responseStatus'] && isset($res['result']) && bccomp($res['result'], $data['amount'], 2) == 0){
                return true;
        }else{
            return false;
        }
    }


    /**
     * 发送请求
     * @param string $method
     * @param array $params 请求参数
     * @param bool $secret_code 代理商密钥
     * @param bool $secret_key secretKey
     * @param array $md5Params 加密字段
     * @return array|string
     */
    public function requestParam(string $method, array $params, $secret_code = true, $secret_key = false, $md5Params = [])
    {
        if(is_null($this->config)){
            $ret = [
                'responseStatus' => false,
                'error' => ['message' => 'no api config']
            ];
            GameApi::addElkLog($ret,'BG');
            return $ret;
        }

        //厅代码
        $sn = $this->config['cagent'];
        //自建代理
        $lobby = json_decode($this->config['lobby'],true);
        $uuid = uniqid();
        //生成随机字符串
        $random = str_replace('.','', sprintf('%.6f', microtime(TRUE)));
        //代理商密钥 password为代理账号的密码
        $secretCode=base64_encode(sha1($lobby['agentpwd'], true));

        $md5str = $random . $sn;

        //登录ID加密
        if(!empty($md5Params)){
            foreach($md5Params as $field){
                $md5str .= $params[$field];
            }
        }
        //代理商密钥
        if($secret_code){
            $md5str .= $secretCode;
        }
        //密钥（secretKey）
        if($secret_key){
            $md5str .= $this->config['key'];
        }

        $digest = md5($md5str);

        $params['random'] = $random;
        $params['sn'] = $sn;
        if(in_array($method, ['open.balance.transfer.query', 'open.game.bg.url'])){
            $params['sign'] = $digest;
        }else{
            $params['digest'] = $digest;
        }


        $url = rtrim($this->config['apiUrl'], '/') . '/' . $method;

        $postData = [
            'id' => $uuid,
            'method' => $method,
            'params' => $params,
            'jsonrpc' => '2.0',
        ];

        $re = Curl::post($url, null, $postData, null, true);
        if ($re['status'] != 200) {
            $ret['responseStatus'] = false;
            $ret['networkStatus'] = $re['status'];
            $ret['error']['message'] = $re['content'];
            GameApi::addRequestLog($url, 'BG', $postData, json_encode($re, JSON_UNESCAPED_UNICODE));
        } else {
            $ret = json_decode($re['content'], true);
            $ret['networkStatus'] = $re['status'];
            if (isset($ret['error']) && is_array($ret['error'])) {
                $ret['responseStatus'] = false;
            } else {
                $ret['responseStatus'] = true;
            }
            GameApi::addRequestLog($url, 'BG', $postData, json_encode($ret, JSON_UNESCAPED_UNICODE));
        }
        return $ret;
    }

    function getJackpot()
    {
        return 0;
    }
}
