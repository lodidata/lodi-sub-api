<?php

namespace Logic\GameApi\Game;

use Logic\GameApi\GameApi;
use Logic\GameApi\GameToken;
use Model\FundsChild;
use Utils\Curl;
use Model\user as UserModel;

/**
 * AWC游戏聚合平台
 * Class AWC
 * @package Logic\GameApi\Game
 */
class AWC extends \Logic\GameApi\Api
{
    /**
     * @var string 订单表
     */
    protected $orderTable = '';
    /**
     * @var string 游戏平台
     */
    protected $platfrom = '';
    /**
     * @var string 游戏订单类型（平台类型与游戏类型不一致）
     */
    protected $orderType = '';
    /**
     * @var string 游戏类型 如：SLOT LIVE
     */
    protected $gameType = '';
    /**
     * @var string 游戏类型 如：GAME LIVE
     */
    protected $gameOrderType = '';
    /**
     * @var int 游戏类型ID号
     */
    protected $game_id = 0;
    /**
     * @var bool 是否限红设置
     */
    protected $betLimit = true;
    /**
     * @var array 游戏支持语言
     */
    protected $langs = [
        'th' => 'th',
        'zh-cn' => 'cn',
        'en-us' => 'en',
        'vn' => 'vn',
        'jp' => 'jp'
    ];


    //第一步 创建游戏账号
    public function childCreateAccount(string $account, string $password)
    {
        $data = [
            'userId' => $account,
            'currency' => $this->config['currency'],
            'betLimit' => '{"' . $this->platfrom . '":' . $this->config['lobby'] . '}',
            'language' => $this->langs[LANG]?? $this->langs['en-us'],
            'userName' => $account
        ];
        //AE电子与AE真人同一个
        if($this->platfrom == 'AWS'){
            $data['betLimit'] = '{"SEXYBCRT":' . $this->config['lobby'] . '}';
        }

        if($this->betLimit === false){
            unset($data['betLimit']);
        }

        $res = $this->requestParam('wallet/createMember', $data);
        //0000成功 1001账户已经存在
        if (isset($res['status']) && in_array($res['status'],["0000", "1001"])) {
            return true;
        }
        return false;
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

        //AE电子 SLOT/TABLE/EGAME
        if($this->platfrom=='AWS'){
            $kinds = explode('-', $params['kind_id']);
            $this->gameType = $kinds[1];
        }

        try {
            $back_url = $this->ci->get('settings')['website']['game_back_url'] ?? $_SERVER['HTTP_HOST'];
            $data = [
                "userId" => $account['account'],
                "isMobileLogin" => true,
                "externalURL" => $back_url,
                "platform" => $this->platfrom,
                "gameType" => $this->gameType,
                "gameCode" => $params['kind_id'],
                "language" => $this->langs[LANG]?? $this->langs['en-us'],
            ];

            if($this->betLimit === true){
                if($this->platfrom == 'AWS'){
                    $data['betLimit'] = '{"SEXYBCRT":' . $this->config['lobby'] . '}';
                }else{
                    $data['betLimit'] = '{"' . $this->platfrom . '":' . $this->config['lobby'] . '}';
                }
            }
            $res = $this->requestParam('wallet/doLoginAndLaunchGame', $data);
            if (isset($res['status']) && $res['status'] == "0000") {
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
                    'url' => $res['url'],
                    'message' => 'ok'
                ];
            }else{
                throw new \Exception($res['desc']);
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
            'userIds' => $account['account'],
            'isFilterBalance' => 0,
            'alluser' => 0
        ];
        $res = $this->requestParam('wallet/getBalance', $data);
        if (isset($res['status']) && $res['status'] == "0000") {
            return [bcmul($res['results'][0]['balance'], 100, 0), bcmul($res['results'][0]['balance'], 100, 0)];
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
            'userIds' => $account['account']
        ];
        $res = $this->requestParam('wallet/logout', $fields);
        if (isset($res['status']) && $res['status'] == "0000") {
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
        if (is_array($data) && $data['balance']) {
            $params = [
                'txCode' => $this->config['cagent'] . $data['tradeNo']
            ];
            $res = $this->requestParam('/wallet/checkTransferOperation', $params);
            //响应成功
            //情境1：响应状态== 0000和txStatus == 1表示存款/提款成功
            //情境2：响应状态== 0000和txStatus == 0表示存款/提款失败
            //情境3：响应状态== 1017表示交易不存在
            if (isset($res['status']) && $res['status'] == "0000") {
                //成功
                if ($res['txStatus'] == 1) {
                    $this->updateGameMoneyError($data, bcmul($res['transferAmount'], 100, 0));
                } elseif ($res['txStatus'] == 0) {
                    //失败退钱
                    $this->refundAction($data);
                }
            } elseif (isset($res['status']) && $res['status'] == 1017) {
                //订单号不存在
                $this->refundAction($data);
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
        $data = [
            'userId' => $account['account'],
            'txCode' => $this->config['cagent'] . $tradeNo,
            'withdrawType' => '1',
            'transferAmount' => bcdiv($balance, 100, 2)
        ];
        $res = $this->requestParam('wallet/withdraw', $data);
        if (isset($res['status']) && $res['status'] == "0000") {
            return [true, $balance];
        } else {
            return [false, $balance];
        }

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
        $data = [
            'userId' => $account['account'],
            'transferAmount' => bcdiv($balance, 100, 2),
            'txCode' => $this->config['cagent'] . $tradeNo
        ];
        $res = $this->requestParam('wallet/deposit', $data);
        if (isset($res['status']) && $res['status'] == "0000") {
            return true;
        } else {
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
            'txCode' => $this->config['cagent'] . $tradeNo
        ];
        $res = $this->requestParam('wallet/checkTransferOperation', $data);
        if (isset($res['status']) && $res['status'] == "0000") {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 3.1.10 查询游戏清单
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
        /*$fields = [
            'platform' => $this->platfrom,
        ];
        $res = $this->requestParam('wallet/checkStatus', $fields);
        if (isset($res['status']) && $res['status'] == "0000") {
            return true;
        }
        return false;*/
        return true;
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
            $user_id = (new GameToken())->getUserId($val['userId']);
            if (!$user_id) {
                continue;
            }

            //AWS桌面游戏
            $gameType = $this->gameOrderType ?: $this->gameType;
            $orderType = $this->orderType;
            $game_id = $this->game_id;
            if($this->platfrom == 'AWS' && in_array($val['gameType'], ['TABLE', 'EGAME']) ){
                $gameType = 'TABLE';
                $orderType = 'AWSTAB';
                $game_id = 107;
            }elseif($this->platfrom == 'YESBINGO' && $val['gameType'] == 'SLOT' ){
                $gameType = 'GAME';
                $orderType = 'YESBINGOSLOT';
                $game_id = 149;
            }elseif($this->platfrom == 'YESBINGO' && $val['gameType'] == 'FH' ){
                $gameType = 'BY';
                $orderType = 'YESBINGOBY';
                $game_id = 148;
            }

            $val['user_id'] = $user_id;
            unset($val['id'], $val['tid']);
            $batchData[] = $val;

            $orders = [
                'user_id' => $user_id,
                'game' => $gameType,
                'order_number' => $val['platformTxId'],
                'game_type' => $orderType,
                'type_name' => $this->lang->text($orderType),
                'play_id' => $game_id,
                'bet' => bcmul($val['betAmount'], 100, 0),
                'profit' => bcmul($val['winAmount'] - $val['betAmount'], 100, 0),
                'send_money' =>  bcmul($val['winAmount'], 100, 0),
                'order_time' => $val['betTime'],
                'date' => substr($val['betTime'], 0, 10),
                'created' => date('Y-m-d H:i:s')
            ];
            $gameAduitSetting = isset($auditSetting[$orders['game']]) && $auditSetting[$orders['game']] ? bcdiv($auditSetting[$orders['game']], 100, 2) : 1;//游戏类型打码量设置，如果不存在则为1
            $orders['dml'] = $orders['bet'] * $gameAduitSetting;
            $batchOrderData[] = $orders;

        }
        $this->addGameOrders($this->orderType, $this->orderTable, $batchData);
        $this->addGameToOrdersTable($batchOrderData);

        unset($data, $val, $key, $query);
        return true;
    }

    /**
     * 发送请求
     * @param string $action 请求方法
     * @param array $param 请求参数
     * @param bool $is_post 是否为post请求
     * @param bool $status 是否返回请求状态
     * @return array|string
     */
    public function requestParam(string $action, array $param, bool $is_post = true, $status = false)
    {
        if(is_null($this->config)){
            $ret = [
                'status' => 99999,
                'message' => 'no api config'
            ];
            GameApi::addElkLog($ret, $this->game_alias);
            return $ret;
        }

        $param['cert'] = $this->config['key'];
        $param['agentId'] = $this->config['cagent'];

        $querystring = http_build_query($param, '', '&');
        //echo $querystring.PHP_EOL;
        $url = $this->config['apiUrl'] . '/' . $action;
        //echo $url.PHP_EOL;
        $headers = array(
            "content-type: application/x-www-form-urlencoded"
        );
        $re = Curl::commonPost($url, null, $querystring, $headers);
        is_array($re) && $re = json_encode($re, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        GameApi::addRequestLog($url,$this->game_alias, $param, $re);
        return json_decode($re, true);
    }


    /**
     * 获取用户钱包余额
     * @param $userId
     * @return array
     * @throws \Exception
     */
    public function getBalance($userId)
    {
        $balance = UserModel::getBalance($userId);
        return $balance;
    }

    function getJackpot()
    {
        return 0;
    }
}

