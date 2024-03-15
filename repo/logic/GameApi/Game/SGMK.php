<?php

namespace Logic\GameApi\Game;

use Logic\Define\Cache3thGameKey;
use Logic\GameApi\GameApi;
use Logic\GameApi\GameToken;
use Model\FundsChild;
use DB;
use Utils\Curl;

/**
 * SGMK新霸电子
 * Class SGMK
 * @package Logic\GameApi\Game
 */
class SGMK extends \Logic\GameApi\Api
{
    protected $langs = [
        'th' => 'th_TH',
        'zh-cn' => 'zh_CN',
        'en-us' => 'en_US',
        'id' => 'id_ID',
        'vn' => 'vi_VN',
        'ko' => 'ko_KR',
        'jp' => 'ja_JP',
        'ru' => 'ru_RU',
        'tr' => 'tr_TR',
    ];

    protected $orderTable = 'game_order_sgmk';

    private $trace_id;

    /**
     * 错误代码
     * @var array
     */
    protected $codes = [
        '0' => 'Success',
        '1' => 'System Error',
        '3' => 'Service Inaccessible',
        '100' => 'Request Timeout',
        '101' => 'Call Limited',
        '104' => 'Request Forbidden',
        '105' => 'Missing Parameters',
        '106' => 'Invalid Parameters',
        '107' => 'Duplicated Serial NO.',
        '108' => 'Merchant Key Error',
        '110' => 'Record ID Not Found',
        '10113' => 'Merchant Not Found',
        '112' => 'API Call Limited',
        '113' => 'Invalid Acct ID Acct ID',
        '118' => 'Invalid Format Parse Json Data Failed',
        '50099' => 'Acct Exist',
        '50100' => 'Acct Not Found',
        '50101' => 'Acct Inactive',
        '50102' => 'Acct Locked',
        '50103' => 'suspend Acct Suspend',
        '50104' => 'Token Validation Failed',
        '50110' => 'Insufficient Balance',
        '50111' => 'Exceed Max Amount',
        '50112' => 'Currency Invalid Deposit/withdraw',
        '50113' => 'Amount Invalid Deposit/withdraw',
        '50115' => 'Date Format Invalid',
        '10104' => 'Password Invalid',
        '30003' => 'Bet Setting Incomplete',
        '10103' => 'Acct Not Found',
        '10105' => 'Acct Status Inactived',
        '10110' => 'Acct Locked',
        '10111' => 'suspend Acct Suspend',
        '11101' => 'BET INSUFFICIENT BALANCE',
        '11102' => 'Bet Draw Stop Bet',
        '11103' => 'BET TYPE NOT OPEN',
        '11104' => 'BET INFO INCOMPLETE',
        '11105' => 'BET ACCT INFO INCOMPLETE',
        '11108' => 'BET REQUEST INVALID',
        '12001' => 'BET SETTING INCOMPLETE',
        '1110801' => 'BET REQUEST INVALID MAX',
        '1110802' => 'BET REQUEST INVALID MIN',
        '1110803' => 'BET REQUEST INVALID TOTALBET',
        '50200' => 'GAME CURRENCY NOT ACTIVE',
    ];

    protected $codescn = [
        '0' => '成功',
        '1' => '系统错误',
        '3' => '服务暂时不可用',
        '100' => '请求超时',
        '101' => '用户调用次数超限',
        '104' => '请求被禁止',
        '105' => '缺少必要的参数',
        '106' => '非法的参数',
        '107' => '批次号重复',
        '108' => 'Apk版本及PC版本商家登录Key错误',
        '110' => '批次号不存在',
        '10113' => 'Merchant不存在',
        '112' => 'API调用次数超限',
        '113' => 'AcctID不正确',
        '118' => '格式不正确',
        '50099' => '账号已存在',
        '50100' => '账号不存在',
        '50101' => '帐号未激活',
        '50102' => '帐号已锁',
        '50103' => '帐号',
        '50104' => 'Token验证失败',
        '50110' => '帐号余额不足',
        '50111' => '超过帐号交易限制',
        '50112' => '币种不支持',
        '50113' => '金额不合法',
        '50115' => '时间不正确',
        '10104' => '会员密码不正确',
        '30003' => '设置不完整',
        '10103' => '会员不存在',
        '10105' => '账号为激活',
        '10110' => '账号已锁',
        '10111' => '帐号',
        '11101' => '余额不足',
        '11102' => '投注失效',
        '11103' => '玩法为开放',
        '11104' => '下注信息不完整',
        '11105' => '帐号信息异常',
        '11108' => '下注请求不合法',
        '12001' => '设置不完整',
        '1110801' => '下注最大值超过上限',
        '1110802' => '下注最小值超下线',
        '1110803' => '下注金额错误',
        '50200' => '游戏在该货币未激活',
    ];

    /**
     * 获取游戏列表
     * { "games": [{
     * "gameCode": "SS-DG02",
     * "gameName": "DerbyNight",
     * "jackpot": true,
     * "thumbnail": "http://xxxx.com/aa.jpg",
     * "screenshot": "http://xxxx.com/aa.jpg",
     * "mthumbnail ": http://xxxx.com/aa.jpg,
     * "jackpotCode ": "Holy",
     * "jackpotName ": "Holy Jackpot"
     * }],
     * "merchantCode": "SPADE",
     * "code": 0,
     * "msg": "success",
     * "serialNo": "20130502191551906534"
     * }
     */
    public function getListGames()
    {
        $param = [
            'serialNo' => $this->serialNo(),
            'currency' => $this->config['currency'],
        ];
        $res = $this->requestParam('getGames', $param);
        if ($res['status']) {
            return $res['games'];
        }
        return $res['msg'];
    }

    /**
     * 生成随机号
     * @return mixed
     */
    public function serialNo()
    {
        return str_replace('.', '', sprintf('%.6f', microtime(TRUE)));
    }

    /**
     * 用户授权
     * @return mixed
     */
    public function authorize($params)
    {
        global $app;
        $logger = $app->getContainer()->logger;
        $logger->info('SGMK:authorize-params', $params);
        $return = [
            'code' => 1,
        ];
        if (!isset($params['acctId']) || !isset($params['token']) || empty($params['merchantCode'])) {
            $return = [
                'code' => 106,
            ];
        } elseif ($params['merchantCode'] != $this->config['cagent']) {
            $return = [
                'code' => 10113,
            ];
        } else {
            $account = $this->getGameAccount();
            if ($account['account'] != $params['acctId'] || $params['token'] != md5($account['account'])) {
                $return = [
                    'code' => 50100,
                ];
            } else {
                $tid = $this->ci->get('settings')['app']['tid'];
                $balance = FundsChild::where('id', $this->wid)->where('game_type', $this->game_alias)->value('balance');
                $return = [
                    'code' => 0,
                    'acctInfo' => [
                        'acctId' => $account['account'],
                        'balance' => $balance,
                        'userName' => $account['account'],
                        'currency' => $this->config['currency'],
                        'siteId' => 'SITE_' . $tid,
                    ]
                ];
                //余额转入第三方
                //$this->rollInThird();
            }
        }
        $return['msg'] = $this->codescn[$return['code']];
        $return['merchantCode'] = $this->config['cagent'];
        $return['serialNo'] = $this->serialNo();
        $logger->info('SGMK:authorize-return', $return);
        return $return;
    }

    /**
     * 创建 Token
     */
    public function createToken()
    {
        $account = $this->getGameAccount();
        $data = [
            'acctId' => $account['account'],
            'serialNo' => $this->serialNo(),
            'action' => 'ticketLog', //客户行径
        ];
        $res = $this->requestParam('createToken', $data);
        if ($res['status']) {
            return $res['token'];
        }
        return false;
    }

    /**
     * 无需创建账户，第三方会自动判定
     * @param string $account
     * @param string $password
     * @return bool
     */
    public function childCreateAccount(string $account, string $password)
    {
        return true;
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

        //余额转入第三方
        $res = $this->rollInThird();
        if (!$res['status']) {
            return [
                'status' => 886,
                'message' => $res['msg'],
                'url' => ''
            ];
        }

        $back_url = $this->ci->get('settings')['website']['game_back_url'] ?? $_SERVER['HTTP_HOST'];
        $origin = isset($this->request->getHeaders()['HTTP_PL']) && is_array($this->request->getHeaders()['HTTP_PL']) ? current($this->request->getHeaders()['HTTP_PL']) : 'h5';
        $param = [
            'acctId' => $account['account'],
            'token' => md5($account['account']),
            'language' => $this->langs[LANG]?? $this->langs['en-us'],
            'game' => $params['kind_id'],
            'fun' => false,
            'mobile' => $origin == 'pc' ? false : true,
            'menumode' => false,
            'exitUrl' => $back_url,
            'fullScreen' => false
        ];
        $loginUrl = $this->config['loginUrl'] . '/' . $this->config['cagent'] . '/auth/';
        return [
            'status' => 0,
            'url' => $loginUrl . "?" . http_build_query($param, '', '&'),
            'message' => 'ok'
        ];

    }

    /**
     * 同步超管订单
     * @return bool
     */
    public function synchronousChildData()
    {
        //游戏类型
        $types = [
            'SM' => ['game' => 'GAME', 'type' => 'SGMK', 'id' => 81],
            'AD' => ['game' => 'ARCADE', 'type' => 'SGMKJJ', 'id' => 82],
            'BC' => ['game' => 'TABLE', 'type' => 'SGMKTAB', 'id' => 83],
            'FH' => ['game' => 'BY', 'type' => 'SGMKBY', 'id' => 84],
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
            $user_id = (new GameToken())->getUserId($val['acctId']);
            if (!$user_id) {
                continue;
            }
            $val['user_id'] = $user_id;
            unset($val['id'], $val['tid']);
            $batchData[] = $val;

            $orders = [
                'user_id' => $user_id,
                'game' => $types[$val['categoryId']]['game'],
                'order_number' => $val['ticketId'],
                'game_type' => $types[$val['categoryId']]['type'],
                'type_name' => $this->lang->text($types[$val['categoryId']]['type']),
                'play_id' => $types[$val['categoryId']]['id'],
                'bet' => bcmul($val['betAmount'], 100, 0),
                'profit' => bcmul($val['winLoss'], 100, 0),
                'send_money' => bcmul($val['betAmount'] + $val['winLoss'], 100, 0),
                'order_time' => $val['ticketTime'],
                'date' => substr($val['ticketTime'], 0, 10),
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
     * 检测金额
     * @param null $data
     */
    public function checkMoney($data = null)
    {
        if (is_array($data) && $data['balance']) {
            $params = [
                'serialNo' => $this->serialNo(),
                'lastSerialNo' => $data['tradeNo'],
            ];
            $res = $this->requestParam('checkStatus', $params, true, false, false, true);
            // 查询玩家上下分订单
            if (isset($res['code']) && $res['code'] == 0) {
                if ($res['status'] == 0) {
                    $this->updateGameMoneyError($data, $data['balance']);
                }

                if ($res['status'] == 110) {
                    //订单失败 或 不存在
                    $this->refundAction($data);
                }
            }

        }
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
            'acctId' => $account['account'],
            'serialNo' => $this->serialNo(),
        ];
        $res = $this->requestParam('kickAcct', $data);
        if ($res['status']) {
            $this->rollOutThird();
            return true;
        }
        return false;
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
     * 检查转账状态
     * @param $tradeNo
     * @return bool|int
     */
    public function transferCheck($tradeNo)
    {
        $data = [
            'serialNo' => $this->serialNo(),
            'lastSerialNo' => $tradeNo,
        ];
        $res = $this->requestParam('checkStatus', $data);
        return $res['status'];
    }

    /**
     * 获取第三方钱包信息 (分为单位)
     * @return array[可下分金额，总金额]
     */
    public function getThirdWalletBalance()
    {
        $account = $this->getGameAccount();
        $data = [
            'acctId' => $account['account'],
            'pageIndex' => 1,
            'serialNo' => $this->serialNo(),
        ];
        $res = $this->requestParam('getAcctInfo', $data);
        if ($res['status'] && $res['resultCount'] == 1) {
            return [bcmul($res['list'][0]['balance'], 100, 0), bcmul($res['list'][0]['balance'], 100, 0)];
        }

        $balance = FundsChild::where('pid', $this->wid)->where('game_type', $this->game_alias)->value('balance');
        return [0, $balance];
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
            'acctId' => $account['account'],
            'serialNo' => $tradeNo,
            'amount' => bcdiv($balance, 100, 2),
            'currency' => $this->config['currency']
        ];
        $res = $this->requestParam('withdraw', $data);
        if ($res['status']) {
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
            'acctId' => $account['account'],
            'serialNo' => $tradeNo,
            'amount' => bcdiv($balance, 100, 2),
            'currency' => $this->config['currency']
        ];
        $res = $this->requestParam('deposit', $data);
        return $res['status'];
    }

    /**
     * 查询 Jackpot 彩池
     * {
     * "list": [{
     * "code": "Grand",
     * "name": "Grand Jackpot",
     * "amount": 3844.08，
     * "currency": USD
     * }],
     * "merchantCode": "SPADE",
     * "msg": "success",
     * "code": 0,
     * "serialNo": "20120802152140143938"
     * }
     */
    function jackpotPool()
    {
        $params = [
            'currency' => $this->config['currency'],
            'serialNo' => $this->serialNo()
        ];
        $res = $this->requestParam('deposit', $params);
        if ($res['status']) {
            return $res['list'];
        } else {
            return $res;
        }
    }

    /**
     * 发送请求
     * @param string $action
     * @param array $param 请求参数
     * @param bool $is_post 是否为post请求
     * @param bool $status 是否返回请求状态
     * @param bool $is_order 是否请求订单接口
     * @param bool $getResult
     * @return array|string
     */
    public function requestParam($action, array $param, bool $is_post = true, $status = false, $is_order = false, $getResult = false)
    {
        if(is_null($this->config)){
            $ret = [
                'status' => false,
                'message' => 'no api config'
            ];
            GameApi::addElkLog($ret,'SGMK');
            return $ret;
        }
        $apiUrl = $is_order ? $this->config['orderUrl'] : $this->config['apiUrl'];
        $header = [
            'API:' . $action,
            'DataType:JSON',
            'Accept-Encoding:'.$this->langs[LANG]?? $this->langs['en-us'],
        ];
        $param['merchantCode'] = $this->config['cagent'];
        $re = Curl::post($apiUrl, null, $param, null, $status, $header);
        //var_dump($re);die;
        $param['API'] = $action;
        $remark = '';
        if(is_array($re)){
            $remark = isset($re['status']) ? 'status:' . $re['status'] : '';
            $re = json_encode($re, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        GameApi::addRequestLog($apiUrl, $this->config['type'], $param, $re, $remark);
        $res = json_decode($re, true);
        if ($getResult) {
            return $res;
        }
        if (isset($res) && (isset($res['code']) && $res['code'] == 0)) {
            $res['status'] = true;
        } else {
            $res['status'] = false;
        }
        return $res;
    }

    /**
     * 获取头奖
     * 4.7 查询 Jackpot 彩池
     * @return integer
     */
    function getJackpot()
    {
        $param = [
            'serialNo' => $this->serialNo(),
            'currency' => $this->config['currency'],
        ];
        $res = $this->requestParam('jackpotPool', $param);
        if ($res['status'] && isset($res['list'])) {
            foreach( $res['list'] as $val){
                if($val['code'] == 'Grand'){
                    return $val['amount'];
                }
            }
        }
        return 0;
    }
}
