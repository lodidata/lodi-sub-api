<?php

namespace Logic\GameApi\Game;
use Logic\GameApi\GameApi;
use Logic\GameApi\GameToken;
use Utils\Curl;

/**
 * YESBINGO
 * Class YESBINGO
 * @package Logic\GameApi\Game
 */
class YESBINGO extends \Logic\GameApi\Api
{
    protected $orderTable = 'game_order_yesbingo';

    /**
     * @var int 游戏类型 1slot 2BY 3BINGO
     */
    protected $gType = 3;

    protected $langs = [
        'zh-cn' => 'zh',
        'en-us' => 'en',
    ];


    /**
     * 无需创建账户，第三方会自动判定
     * @param string $account
     * @param string $password
     * @return bool
     */
    public function childCreateAccount(string $account, string $password)
    {

        $param = [
            'action' => 12,
            'ts' => (int)(microtime(true) * 1000),
            'parent' => $this->config['cagent'],
            'uid' => $account,
            'name' => $account,
            'credit_allocated' => 0
        ];

        $res = $this->requestParam($param);
        //接口状态错误
        if(!$res['responseStatus']){
            return false;
        }
        if ($res['status'] == '0000') {
            return true;
        }
        return false;
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
            $user_id = (new GameToken())->getUserId($val['Username']);
            if (!$user_id) {
                throw new \Exception('用户不存在' . $val['Username']);
            }

            $val['user_id'] = $user_id;
            unset($val['id'], $val['tid']);
            switch ($val['game_menu_id']) {
                case 147:
                    $parentType = 'BINGO';
                    $gameType = 'YESBINGO';
                    break;
                case 148:
                    $parentType = 'BY';
                    $gameType = 'YESBINGOBY';
                    break;
                case 149:
                default:
                    $parentType = 'GAME';
                    $gameType = 'YESBINGOSLOT';
                    break;
            }

            $orders = [
                'user_id' => $user_id,
                'game' => $parentType,
                'order_number' => $val['OCode'],
                'game_type' => $gameType,
                'type_name' => $this->lang->text($gameType),
                'play_id' => $val['game_menu_id'],
                'bet' => abs($val['betAmount']),
                'profit' => $val['income'],
                'send_money' => $val['winAmount'],
                'order_time' => $val['gameDate'],
                'date' => substr($val['gameDate'], 0, 10),
                'created' => date('Y-m-d H:i:s')
            ];
            $gameAduitSetting = isset($auditSetting[$orders['game']]) && $auditSetting[$orders['game']] ? bcdiv($auditSetting[$orders['game']], 100, 2) : 1;//游戏类型打码量设置，如果不存在则为1
            $orders['dml'] = $orders['bet'] * $gameAduitSetting;
            $batchOrderData[] = $orders;
            $batchData[] = $val;

        }
        //$this->addGameOrders($this->game_type, $this->orderTable, $batchData);
        $this->addGameToOrdersTable($batchOrderData);

        unset($data, $val, $key, $query);

        return true;
    }

    /**
     * 退出游戏
     * @return bool
     */
    public function quitChildGame()
    {
        $account = $this->getGameAccount();
        $param = [
            'action' => 17,
            'ts' => (int)(microtime(true) * 1000),
            'parent' => $this->config['cagent'],
            'uid' => $account['account'],
        ];
        $res = $this->requestParam($param);
        //接口状态错误
        if(!$res['responseStatus']){
            return false;
        }
        $this->rollOutThird();
        if (isset($res['status']) && $res['status'] == '0000') {
            return true;
        }
        return false;
    }

    /**
     * 拼接用户跳转链接
     * @param array $data
     * @return array
     */
    public function getJumpUrl(array $data = [])
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

        $back_url = $this->ci->get('settings')['website']['game_back_url'] ?: $this->ci->get('settings')['website']['game_home_url'];
        $param = [
            'action' => 11,
            'ts' => (int)(microtime(true) * 1000),
            'uid' => $account['account'],
            'lang' => $this->langs[LANG]?? $this->langs['en-us'],
            'gType' => $this->gType,
            'mType' => $data['kind_id'],
            'lobbyURL' => $back_url,
            'moreGame' => 1,
            'mute' => 0,
        ];
        $res = $this->requestParam($param);
        //接口状态错误
        if(!$res['responseStatus']){
            return ['status' => 886, 'message' => $res['message']];
        }
        if ($res['status'] == '0000') {
            //余额转入第三方
            $result = $this->rollInThird();
            if (!$result['status']) {
                return [
                    'status' => 886,
                    'message' => $result['msg'],
                    'url' => ''
                ];
            }
            return ['status' => 0, 'url' => $res['path']];
        }
        return ['status' => 886, 'message' => $res['status'] . '-' . $res['err_text'] ?? $this->lang->text('Domain name request failed')];
    }


    /**
     * 检测金额
     * @param null $data
     * @return bool
     */
    public function checkMoney($data = null)
    {
        $param = [
            'action' => 55,
            'ts' => (int)(microtime(true) * 1000),
            'parent' => $this->config['cagent'],
        ];
        //转入金额超时则在此查询
        if (is_array($data) && $data['balance']) {
            $param['serialNo'] = $data['tradeNo'];
            // 查询玩家上下分订单
            $res = $this->requestParam($param);
            //接口状态错误
            if(!$res['responseStatus']){
                return false;
            }
            if (isset($res['status'])) {
                if ($res['status'] == '0000') {
                    $this->updateGameMoneyError($data, bcmul($res['data'][0]['amount'], 100, 0));
                }
                if ($res['status'] == '9015') {
                    $this->refundAction($data);
                }
            }
        }
    }

    /**
     * 获取第三方钱包信息 (分为单位)
     * @return array[可下分金额，总金额]
     */
    public function getThirdWalletBalance()
    {
        $account = $this->getGameAccount();
        $param = [
            'action' => 15,
            'ts' => (int)(microtime(true) * 1000),
            'parent' => $this->config['cagent'],
            'uid' => $account['account']
        ];
        // 用来查询玩家的可下分余额
        $res = $this->requestParam($param);

        if ($res['responseStatus'] && isset($res['status']) && $res['status'] == '0000') {
            $balance = bcmul($res['data'][0]['balance'], 100, 0);
            return [$balance, $balance];
        }
        $balance = \Model\FundsChild::where('pid', $this->wid)->where('game_type', $this->game_alias)->value('balance');
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
        $res = $this->transfer(bcmul(-1, $balance, 0), $tradeNo);
        if ($res['responseStatus'] && isset($res['status']) && $res['status'] == '0000') {
            return array(true, $balance);
        }
        return array(false, $balance);
    }

    /**
     * 进入第三方，并转入钱包
     * @param int $balance
     * @param string $tradeNo
     * @return bool|int
     */
    function rollInChildThird(int $balance, string $tradeNo)
    {
        // 上分
        $res = $this->transfer($balance, $tradeNo);
        if(!$res['responseStatus']){
            return false;
        }
        if ($res['status'] == '0000') {
            return true;
        }
        return false;
    }

    //确认转账
    public function transfer($balance, $tradeNo)
    {
        $account = $this->getGameAccount();
        $data = [
            'action' => 19,
            'ts' => (int)(microtime(true) * 1000),
            'parent' => $this->config['cagent'],
            'uid' => $account['account'],
            'serialNo' => $tradeNo,
            'amount' => bcdiv($balance, 100, 2),  //金额为元
            'remark' => time(),
        ];
        if ($balance < 0) {
            $data['allCashOutFlag'] = 1;
        }

        $res = $this->requestParam($data);

        return $res;
    }

    public function requestParam(array $data)
    {
        if(is_null($this->config)){
            $ret = [
                'responseStatus' => false,
                'message' => 'no api config'
            ];
            GameApi::addElkLog($ret,'YESBINGO');
            return $ret;
        }
        $encryptData = $this->encrypt(json_encode($data));
        $param = [
            'dc' => $this->config['lobby'],
            'x' => $encryptData
        ];
        $url = $this->config['apiUrl'] . '?dc=' . $param['dc'] . '&x=' . $param['x'];
        $re = Curl::commonPost($this->config['apiUrl'], null, http_build_query($param), null, true);
        if ($re['status'] == 200) {
            $re['content'] = json_decode($re['content'], true);
        }
        GameApi::addRequestLog($url, 'YESBINGO', $data, json_encode($re, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
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

    public function encrypt($str)
    {
        $key = $this->config['key'];
        $iv = $this->config['des_key'];
        $str = $this->padString($str);
        $encrypted = openssl_encrypt($str, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
        $data = base64_encode($encrypted);
        $data = str_replace(array('+', '/', '='), array('-', '_', ''), $data);
        return $data;
    }

    public function decrypt($code)
    {
        $code = str_replace(array('-', '_'), array('+', '/'), $code);
        $code = base64_decode($code);
        $key = $this->config['key'];
        $iv = $this->config['des_key'];
        $decrypted = openssl_decrypt($code, 'AES-128-CBC', $key, OPENSSL_NO_PADDING, $iv);
        return utf8_encode(trim($decrypted));
    }

    private function padString($source)
    {
        $paddingChar = ' ';
        $size = 16;
        $x = strlen($source) % $size;
        $padLength = $size - $x;
        for ($i = 0; $i < $padLength; $i++) {
            $source .= $paddingChar;
        }
        return $source;
    }

    /**
     * 获取头奖
     * 2.11. Action 45：查询 Jackpot 信息 grand巨奖,major大奖,minor小奖
     * @return integer
     */
    function getJackpot()
    {
        $param = [
            'action' => 45,
            'ts' => (int)(microtime(true) * 1000),
            'parent' => $this->config['cagent'],
        ];

        $res = $this->requestParam($param);
        //接口状态错误
        if($res['responseStatus'] && $res['status'] == '0000'){
            return $res['grand']['val'];
        }
        return 0;
    }
}
