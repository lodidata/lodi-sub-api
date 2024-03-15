<?php

namespace Logic\GameApi\Game;

use Logic\Define\Cache3thGameKey;
use Logic\GameApi\GameApi;
use Logic\GameApi\GameToken;
use Model\FundsChild;
use Utils\Client;
use DB;
use Utils\Curl;

/**
 * DG视讯
 * Class DG
 * @package Logic\GameApi\Game
 */
class DG extends \Logic\GameApi\Api
{
    protected $langs = [
        'th' => 'th',
        'zh-cn' => 'cn',
        'en-us' => 'en',
        'vn' => 'vi',
        'ko' => 'kr',
        'tw' => 'tw',
    ];
    protected $orderTable = 'game_order_dg';


    /**
     * 无需创建账户，第三方会自动判定
     * @param string $account
     * @param string $password
     * @return bool
     */
    public function childCreateAccount(string $account, string $password)
    {
        $lobby = json_decode($this->config['lobby'], true);
        $param = [
            "data" => $lobby['limitGroup'], //会员注册默认分配限红组A,选择为F
            "member" => [
                'username' => $account,
                'password' => $password,
                'currencyName' => $this->config['currency'],
                'winLimit' => $lobby['winLimit'] //WinLimit 为会员单日可赢取金额,0 表示无限制
            ],

        ];
        $res = $this->requestParam('/user/signup', $param);
        return $res['responseStatus'];
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
        $data = [
            'lang' => $this->langs[LANG]?? $this->langs['en-us'],
            'domains' => '1',
            "member" => [
                'username' => $account['account'],
                'password' => $account['password'],
            ],
        ];
        $res = $this->requestParam('/user/login', $data);
        if($res['responseStatus'] && ($res['codeId'] == 114 || $res['codeId'] == 102)){
            $this->childCreateAccount($account['account'], $account['password']);
        }
        if ($res['responseStatus'] && $res['codeId'] == 0) {
            //余额转入第三方
            $result = $this->rollInThird();
            if (!$result['status']) {
                return [
                    'status' => 886,
                    'message' => $result['msg'] ?? 'roll in error',
                    'url' => ''
                ];
            }

            $origins = ['pc' => 1, 'h5' => 1, 'ios' => 1, 'android' => 1];
            $origin = isset($this->request->getHeaders()['HTTP_PL']) && is_array($this->request->getHeaders()['HTTP_PL']) ? current($this->request->getHeaders()['HTTP_PL']) : 'h5';

            $lang = $this->langs[LANG]?? $this->langs['en-us'];
            //手机浏览器进入游戏: list[1] + token + &language=lang
            $url = $res['list'][$origins[$origin]].$res['token'].'&language='.$lang;
            return [
                'status' => 0,
                'url' => $url,
                'message' => 'ok'
            ];
        } else {
            return [
                'status' => -1,
                'message' => $res['ErrorMsg'] ?? 'login error',
                'url' => ''
            ];
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
        $batchData = [];
        //orders列表
        $batchOrderData = [];
        //打码量配置
        $auditSetting = \Logic\Set\SystemConfig::getModuleSystemConfig('audit');
        foreach ($data as $key => $val) {
            $user_id = (new GameToken())->getUserId($val['userName']);
            if (!$user_id) {
                continue;
            }
            $val['user_id'] = $user_id;
            unset($val['id'], $val['tid']);
            $batchData[] = $val;

            $orders = [
                'user_id' => $user_id,
                'game' => 'LIVE',
                'order_number' => $val['order_number'],
                'game_type' => $this->game_type,
                'type_name' => $this->lang->text($this->game_type),
                'play_id' => 103,
                'bet' => bcmul($val['betPoints'], 100, 0),
                'profit' => bcmul($val['profit'], 100, 0),
                'send_money' => bcmul($val['winOrLoss'], 100, 0),
                'order_time' => $val['betTime'],
                'date' => substr($val['betTime'], 0, 10),
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
        return false;
    }

    /**
     * 检测金额
     * @param null $data
     * @throws \Exception
     */
    public function checkMoney($data = null)
    {
        if (is_array($data) && $data['balance']) {
            $params = [
                'data' => $data['tradeNo']
            ];
            $res = $this->requestParam('/account/checkTransfer', $params);
            if ($res['networkStatus'] == 200 && isset($res['codeId'])) {
                //codeId=0表示成功, codeId=324表示失败
                if($res['codeId']=== 0){
                    $this->updateGameMoneyError($data, $data['balance']);
                }else{
                    //订单不存在及失败
                    $this->refundAction($data);
                }
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
            "member" => [
                'username' => $account['account'],
            ]
        ];
        $res = $this->requestParam('/user/getBalance', $data);
        if ($res['responseStatus'] && isset($res['codeId']) && $res['codeId']=== 0) {
            //该会员的余额，現存結余, 不包括已经下注的金额. 精确到分
            return [bcmul($res['member']['balance'], 100, 0), bcmul($res['member']['balance'], 100, 0)];
        }
        if($res['responseStatus'] && $res['codeId'] == 114){
            $this->childCreateAccount($account['account'], $account['password']);
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
            'data' => $tradeNo,
            "member" => [
                'username' => $account['account'],
                'amount' => bcdiv(-1*$balance, 100, 2),
            ]
        ];
        $res = $this->requestParam('/account/transfer', $data);
        if ($res['networkStatus'] == 200) {
            if(isset($res['codeId'])  && $res['codeId']=== 0){
                return [true, $balance];
            }else{
                $res2 = $this->transferCheck($tradeNo);
                return [$res2, $balance];
            }
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
            'data' => $tradeNo,
            "member" => [
                'username' => $account['account'],
                'amount' => bcdiv($balance, 100, 2),
            ]
        ];
        $res = $this->requestParam('/account/transfer', $data);
        if ($res['networkStatus'] == 200) {
            if(isset($res['codeId'])  && $res['codeId']=== 0){
                return true;
            }else{
                return $this->transferCheck($tradeNo);
            }
        }else{
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
        $data = [
            'data' => $tradeNo
        ];
        $res = $this->requestParam('/account/checkTransfer', $data);
        //codeId=0表示成功, codeId=324表示失败
        if ($res['networkStatus'] == 200 && isset($res['codeId'])  && $res['codeId']=== 0) {
            return true;
        }else{
            return false;
        }
    }

    /**
     * 发送请求
     * @param string $action
     * @param array $params 请求参数
     * @return array|string
     * @throws \Exception
     */
    public function requestParam(string $action, array $params)
    {
        if(is_null($this->config)){
            $ret = [
                'responseStatus' => false,
                'msg' => 'no api config'
            ];
            GameApi::addElkLog($ret,'DG');
            return $ret;
        }

        $agentName = $this->config['cagent'];
        //生成token的随机字符串
        $random = str_replace('.','', sprintf('%.6f', microtime(TRUE)));
        $token = md5($agentName . $this->config['key'] . $random);
        $params['token'] = $token;
        $params['random'] = $random;

        $url = $this->config['apiUrl'] . $action . '/' . $agentName;

        $re = Curl::post($url, null, $params, null, true);
        if ($re['status'] != 200) {
            $ret['responseStatus'] = false;
            $ret['networkStatus'] = $re['status'];
            $ret['msg'] = $re['content'];
            GameApi::addRequestLog($url, 'DG', $params, json_encode($re, JSON_UNESCAPED_UNICODE));
        } else {
            $ret = json_decode($re['content'], true);
            $ret['networkStatus'] = $re['status'];
            if (isset($ret['codeId'])) {
                $ret['responseStatus'] = true;
            } else {
                $ret['responseStatus'] = false;
            }
            GameApi::addRequestLog($url, 'DG', $params, json_encode($ret, JSON_UNESCAPED_UNICODE));
        }
        return $ret;
    }

    function getJackpot()
    {
        return 0;
    }
}
