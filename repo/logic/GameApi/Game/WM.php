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
 * WM
 * Class WM
 * @package Logic\GameApi\Game
 */
class WM extends \Logic\GameApi\Api {

    /**
     * 游戏类型,LIVE,GAME
     * @var string
     */
    protected $game_type = '';

    //    使用语言 0或空值 为简体中文
    //1为英文
    //2为泰文
    //3为越文
    //4为日文
    //5为韩文
    //6为印度文
    //7为马来西亚文
    //8为印尼文
    //9为繁体中文
    //10为西文
    protected $langs = [
        'zh-cn' => '0',
        'en-us' => '1',
        'th'    => '2',
        'vi'     => '3',
        'ja'     => '4',
        'ko'     => '5',
        'hi'     => '6',
        'ma'     => '7',
        'in'     => '8',
        'zh-tn'     => '9',
        'es'    => '10',
    ];

    /**
     *  语音包
     * @var string[]
     */
    protected $voice = [
        'zh-cn' => 'cn',
        'tw'    => 'tw',
        'en-us' => 'en',
        'th'    => 'th',
        'vi'    => 'vi',
        'ja'    => 'ja',
        'ko'    => 'ko',
        'hi'    => 'hi',
        'in'    => 'in',
        'ms'    => 'ms',
        'es'    => 'es',
    ];

    /**
     * 游戏类别
     * @var string[]
     */
    protected $modes = [
        '101' => 'onlybac',
        '102' => 'onlydgtg',
        '103' => 'onlyrou',
        '104' => 'onlysicbo',
        '105' => 'onlyniuniu',
        '106' => 'onlysamgong',
        '107' => 'onlyfantan',
        '108' => 'onlysedie',
        '110' => 'onlyfishshrimpcrab',
        '111' => 'onlygoldenflower',
        '112' => 'onlypaigow',
        '113' => 'onlythisbar',
        '128' => 'onlyandarbahar',
    ];
    protected $orderTable = 'game_order_wm';

    /**
     * 无需创建账户，第三方会自动判定
     * @param string $account
     * @param string $password
     * @return bool
     */
    public function childCreateAccount(string $account, string $password) {
        $param = [
            'cmd'       => 'MemberRegister',
            'user'      => $account,
            'password'  => $password,
            'username'  => $account,
            'timestamp' => time()
        ];
        $res   = $this->requestParam($param);
        return $res['responseStatus'];
    }

    //进入游戏
    public function getJumpUrl(array $params = []) {
        //检测并创建账号
        $account = $this->getGameAccount();
        if(!$account) {
            return [
                'status'  => 133,
                'message' => $this->lang->text(133),
                'url'     => ''
            ];
        }
        $back_url = $this->ci->get('settings')['website']['game_home_url'] ?? $_SERVER['HTTP_HOST'];
        $lang     = $this->langs[LANG] ?? $this->langs['en-us'];
        $voice    = $this->voice[LANG] ?? $this->voice['en-us'];

        $params = [
            'cmd'       => 'SigninGame',
            'user'      => $account['account'],
            'password'  => $account['password'],
            'lang'      => $lang,
            'voice'     => $voice,
            'returnurl' => $back_url,
            'timestamp' => time()
        ];
        if($this->game_type == 'LIVE') {
            $params['mode'] = $this->modes[$params['kind_id']];
        } elseif($this->game_type == 'GAME') {
            $type = substr($params['kind_id'], 0, 4);
            if(strpos($type, 'slot') !== false) {
                $code = 5009;
            } elseif(strpos($type, 'cm') !== false) {
                $code = 5011;
            } else {
                $code = '';
            }
            $params['slotCode']   = $code;
            $params['slotGameId'] = $params['kind_id'];
        }
        $res = $this->requestParam($params);
        if($res['responseStatus']) {
            //余额转入第三方
            $result = $this->rollInThird();
            if(!$result['status']) {
                return [
                    'status'  => 886,
                    'message' => $result['msg'] ?? 'roll in error',
                    'url'     => ''
                ];
            }
            return [
                'status'  => 0,
                'url'     => $res['result'],
                'message' => 'ok'
            ];
        } else {
            return [
                'status'  => -1,
                'message' => $res['ErrorMsg'] ?? 'login error',
                'url'     => ''
            ];
        }
    }

    /**
     * 同步超管订单
     * @return bool
     */
    public function synchronousChildData() {
        if(!$data = $this->getSupperOrder($this->config['type'])) {
            return true;
        }
        $platformTypes = [
            '1' => ['id' => 104, 'game' => 'LIVE', 'type' => 'WM'],
            '2' => ['id' => 105, 'game' => 'GAME', 'type' => 'WMSLOT'],
        ];

        //注单列表
        $batchData = [];
        //orders列表
        $batchOrderData = [];
        //打码量配置
        $auditSetting = \Logic\Set\SystemConfig::getModuleSystemConfig('audit');
        foreach ($data as $key => $val) {
            $user_id = (new GameToken())->getUserId($val['user']);
            if (!$user_id) {
                continue;
            }
            $val['user_id'] = $user_id;
            unset($val['id'], $val['tid']);
            $batchData[] = $val;

            $orders = [
                'user_id'       => $user_id,
                'game'         => $platformTypes[$val['GameCategoryId']]['game'],
                'order_number' => $val['order_number'],
                'game_type'    => $platformTypes[$val['GameCategoryId']]['type'],
                'type_name'    => $this->lang->text($platformTypes[$val['GameCategoryId']]['type']),
                'play_id'      => $platformTypes[$val['GameCategoryId']]['id'],
                'bet'          => bcmul($val['bet'], 100, 0),
                'profit'       => bcmul($val['winLoss'], 100, 0),
                'send_money'    => bcmul($val['prize_amount'], 100, 0),
                'order_time'    => $val['betTime'],
                'date'          => substr($val['betTime'], 0, 10),
                'created'       => date('Y-m-d H:i:s')
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
    public function quitChildGame() {
        $account = $this->getGameAccount();
        $param   = [
            'cmd'       => 'LogoutGame',
            'user'      => $account['account'],
            'timestamp' => time()
        ];
        $res     = $this->requestParam($param);
        if($res['responseStatus']) {
            return true;
        }
        return false;
    }

    /**
     * 检测金额
     */
    public function checkMoney($data = null)
    {
        return true;
    }

    /**
     * 获取第三方钱包信息 (分为单位)
     * @return array[可下分金额，总金额]
     * @throws \Exception
     */
    public function getThirdWalletBalance() {
        $account = $this->getGameAccount();
        $param   = [
            'cmd'  => 'GetBalance',
            'user' => $account['account'],
            'time' => time()
        ];
        $res     = $this->requestParam($param);
        if($res['responseStatus']) {
            //该会员的余额，現存結余, 不包括已经下注的金额. 精确到分
            return [bcmul($res['result'], 100, 0), bcmul($res['result'], 100, 0)];
        }

        $balance = FundsChild::where('pid', $this->wid)->where('game_type', $this->game_alias)->value('balance');
        return [0, $balance];
    }

    /***
     * 退出第三方,并回收至钱包
     * @param int    $balance
     * @param string $tradeNo
     * @return array(是否成功,转出后第三方剩余金额)
     */
    public function rollOutChildThird(int $balance, string $tradeNo) {
        $account = $this->getGameAccount();
        $param   = [
            'cmd'       => 'ChangeBalance',
            'user'      => $account['account'],
            'money'     => bcdiv(-1 * $balance, 100, 2),
            'order'     => $tradeNo,
            'timestamp' => time(),
        ];
        $res     = $this->requestParam($param);
        if($res['responseStatus']) {
            if(!empty($res['result']['orderId'])) {
                return [true, $balance];
            }
        } else {
            return [false, $balance];
        }
    }

    /**
     * 进入第三方，并转入钱包
     * @param int    $balance
     * @param string $tradeNo
     * @return bool|int
     */
    function rollInChildThird(int $balance, string $tradeNo) {
        $account = $this->getGameAccount();
        $param   = [
            'cmd'       => 'ChangeBalance',
            'user'      => $account['account'],
            'money'     => bcdiv( $balance, 100, 2),
            'order'     => $tradeNo,
            'timestamp' => time(),
        ];
        $res     = $this->requestParam($param);
        if($res['responseStatus']) {
            if(!empty($res['result']['orderId'])) {
                return true;
            }
        } else {
            return false;
        }
    }

    /**
     * 检查转账状态
     * @return bool|int
     */
    public function transferCheck() {

    }

    /**
     * 发送请求
     * @param array $params 请求参数
     * @return array|string
     * @throws \Exception
     */
    public function requestParam(array $params) {
        if(is_null($this->config)){
            $ret = [
                'responseStatus' => false,
                'msg' => 'no api config'
            ];
            GameApi::addElkLog($ret,'WM');
            return $ret;
        }
        $params['vendorId']  = $this->config['cagent'];
        $params['signature'] = $this->config['key'];

        $url = $this->config['apiUrl'];
        $headers = array(
            "Content-Type: application/x-www-form-urlencoded"
        );

        $queryString=http_build_query($params);

        $re = Curl::commonPost($url, null, $queryString, $headers, true);

        if($re['status'] != 200) {
            $ret['responseStatus'] = false;
            $ret['networkStatus']  = $re['status'];
            $ret['msg']            = $re['content'];
            GameApi::addRequestLog($url, 'WM', $params, json_encode($re, JSON_UNESCAPED_UNICODE));
        } else {
            $ret                  = json_decode($re['content'], true);
            $ret['networkStatus'] = $re['status'];
            if(isset($ret['errorCode']) && $ret['errorCode'] === 0) {
                $ret['responseStatus'] = true;
            } else {
                $ret['responseStatus'] = false;
            }
            GameApi::addRequestLog($url, 'WM', $params, json_encode($ret, JSON_UNESCAPED_UNICODE));
        }
        return $ret;
    }

    function getJackpot()
    {
        return 0;
    }
}
