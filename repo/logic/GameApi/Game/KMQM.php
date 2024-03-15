<?php

namespace Logic\GameApi\Game;

use Logic\GameApi\GameToken;
use Utils\Client;
use Logic\Define\Cache3thGameKey;
use Logic\GameApi\GameApi;
use Model\FundsChild;
use DB;
use Utils\Curl;

/**
 * KMQM棋牌
 * Class KMQM
 * @package Logic\GameApi\Game
 */
class KMQM extends \Logic\GameApi\Api
{
    protected $langs = [
        'th' => 'th-TH',
        'zh-cn' => 'zh-CN',
        'en-us' => 'en-US',
        'vn' => 'vi-VN',
        'id' => 'id-ID',
        'jp' => 'ja-JP',
        'ko' => 'ko-KR'
    ];
    /**
     * @var string 货币
     */
    protected $authtoken = '';
    protected $orderTable = 'game_order_kmqm';

    /**
     * 6.3 玩家身份验证
     * 玩家令牌的默认时效为1天(24小时)。
     * @param $account
     * @return mixed|string
     */
    public function authorize($account)
    {
        $this->authtoken = $this->redis->get('game_authorize_kmqm:' . $account);
        if (is_null($this->authtoken)) {
            $back_url = $this->ci->get('settings')['website']['game_back_url'] ?? $this->ci->get('settings')['website']['game_back_index'];
            $param = [
                'ipaddress' => Client::getIp(),
                'username' => $account,
                'userid' => $account,
                'lang' => $this->langs[LANG]?? $this->langs['en-us'],
                'cur' => $this->config['currency'],
                'betlimitid' => 1,
                'istestplayer' => false,
                'platformtype' => 1,
                'loginurl' => $back_url,
            ];
            $res = $this->requestParam('/api/player/authorize', $param);
            if ($res['status']) {
                $this->authtoken = $res['authtoken'];
                $this->redis->setex('game_authorize_kmqm:' . $account, 86400, $res['authtoken']);
            } else {
                $this->authtoken = '';
            }
        }
        return $this->authtoken;
    }

    /**
     * 获取游戏列表
     */
    public function getListGames()
    {
        $fields = [
            'lang' => $this->langs[LANG]?? $this->langs['en-us'],
            'platformtype' => '1',
            'iconres' => '520x520',
        ];
        return $this->requestParam('/api/games', $fields, false);
    }

    /**
     * 无需创建账户，第三方会自动判定
     * @param string $account
     * @param string $password
     * @return bool
     */
    public function childCreateAccount(string $account, string $password)
    {
        $res = $this->authorize($account);
        return $res ? true : false;
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
        $res = $this->authorize($account['account']);
        if (!$res) {
            return [
                'status' => -1,
                'message' => 'token error',
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

        $param = [
            'gpcode' => 'KMQM',
            'gcode' => $params['kind_id'],
            'token' => $this->authtoken,
            'lang' => $this->langs[LANG]?? $this->langs['en-us'],
        ];
        $loginUrl = $this->config['loginUrl'] . '/gamelauncher';
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
            $user_id = (new GameToken())->getUserId($val['userid']);
            if (!$user_id) {
                continue;
            }
            $val['user_id'] = $user_id;
            unset($val['id'], $val['tid']);
            $batchData[] = $val;

            $orders = [
                'user_id' => $user_id,
                'game' => 'QP',
                'order_number' => $val['ugsbetid'],
                'game_type' => $this->game_type,
                'type_name' => $this->lang->text($this->game_type),
                'play_id' => 77,
                'bet' => bcmul($val['riskamt'], 100, 0),
                'profit' => bcmul($val['winloss'], 100, 0),
                'send_money' => bcmul($val['winamt'], 100, 0),
                'order_time' => $val['betupdatedon'],
                'date' => substr($val['betupdatedon'], 0, 10),
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
     */
    public function checkMoney($data = null)
    {
        if (is_array($data) && $data['balance']) {
            // 查询玩家上下分订单
            $res = $this->requestParam('/api/history/transfers/' . $data['tradeNo'], [], false, true);
            //交易不存在
            if (isset($res["err"]) && $res['err'] == 200) {
                $this->refundAction($data);
            }
            //交易存在
            if (!isset($res["err"]) && isset($res['txid'])) {
                if ($res['txid'] == $data['tradeNo']) {
                    $this->updateGameMoneyError($data, abs(bcmul($res['amt'], 100, 0)));
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
            'userid' => $account['account'],
        ];
        $res = $this->requestParam('/api/player/deauthorize', $data);
        if ($res['status'] && $res['Success'] === true) {
            $this->rollOutThird();
            return true;
        }
        return false;
    }

    /**
     * 检查转账状态
     * @param $tradeNo
     * @return bool|int
     */
    public function transferCheck($tradeNo)
    {
        $res = $this->requestParam('/api/history/transfers/' . $tradeNo, [], false);
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
            'userid' => $account['account'],
            'cur' => $this->config['currency']
        ];
        $res = $this->requestParam('/api/player/balance', $data, false);
        if ($res['status']) {
            return [bcmul($res['bal'], 100, 0), bcmul($res['bal'], 100, 0)];
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
            'userid' => $account['account'],
            'amt' => bcdiv($balance, 100, 2),
            'cur' => $this->config['currency'],
            'txid' => $tradeNo,
        ];
        $res = $this->requestParam('/api/wallet/debit', $data);
        if ($res['status']) {
            return [true, $balance];
        } else {
            return [false, $balance];
        }
    }

    /**
     * 发送请求
     * @param string $action
     * @param array $param 请求参数
     * @param bool $is_post 是否为post请求
     * @param bool $status 是否返回请求状态
     * @param bool $is_order 是否请求订单接口
     * @return array|string
     */
    public function requestParam($action, array $param, bool $is_post = true, $status = false, $is_order = false)
    {
        if(is_null($this->config)){
            $ret = [
                'status' => false,
                'msg' => 'no api config'
            ];
            GameApi::addElkLog($ret,'KMQM');
            return $ret;
        }

        $apiUrl = $is_order ? $this->config['orderUrl'] : $this->config['apiUrl'];
        $header = [
            'X-QM-Accept:json',
            'Accept:application/json',
            'X-QM-ClientId:' . $this->config['cagent'],
            'X-QM-ClientSecret:' . $this->config['key'],
        ];

        $url = rtrim($apiUrl, '/') . $action;

        if ($is_post) {
            $re = Curl::post($url, null, $param, null, null, $header);
        } else {
            if ($param) {
                $queryString = http_build_query($param, '', '&');
                $url .= '?' . $queryString;
            }
            $re = Curl::get($url, null, false, $header);
        }

        GameApi::addRequestLog($url, 'KMQM', $param, $re, isset($re['status']) ? 'status:' . $re['status']:'');
        $res = json_decode($re, true);
        if ($status) {
            return $res;
        }
        if (!is_array($res)) {
            $res['message'] = $re;
            $res['status'] = false;
        } elseif (isset($res["err"]) && $res["err"] > 0) {
            $res['status'] = false;
        } else {
            $res['status'] = true;
        }
        return $res;
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
            'userid' => $account['account'],
            'txid' => $tradeNo,
            'cur' => $this->config['currency'],
            'amt' => bcdiv($balance, 100, 2),
        ];
        $res = $this->requestParam('/api/wallet/credit', $data);
        return $res['status'];
    }

    public function hash_sha256($string)
    {
        return strtoupper(hash_hmac('sha256', utf8_encode($string), utf8_encode($this->config['key']), false));
    }

    /**
     * 获取头奖
     * 10.1 彩金资料(Jackpot Feed)
     * 彩金资料方法用以获取彩金群组相关资料，例如彩金名称、支持彩金的游戏、彩金金额、币别和汇率信息。
     * @return integer
     */
    function getJackpot()
    {
        $data = [
            'lang' => $this->langs[LANG]?? $this->langs['en-us'],
        ];
        $res = $this->requestParam('/api/jackpots', $data);
        if ($res['status'] && isset($res['jackpotgroups']) && isset($res['jackpotgroups'][0]['jackpots'])) {
            return $res['jackpotgroups'][0]['jackpots'][0]['amt'];
        }
        return 0;
    }
}
