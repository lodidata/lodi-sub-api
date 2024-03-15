<?php

namespace Logic\GameApi\Game;

use Logic\GameApi\GameApi;
use Logic\GameApi\GameToken;
use Model\FundsChild;
use Utils\Curl;
use Model\user as UserModel;

class NG extends \Logic\GameApi\Api
{
    public $game_id = 137;
    protected $langs = [
        'th' => 'th',
        'zh-cn' => 'zh',
        'en-us' => 'en',
        'id' => 'id',
        'ko' => 'ko',
        'jp' => 'ja',
        'vn' => 'vi',
    ];

    protected $gameCode = [
        'caishens-cash' => 'caishen',
        'fulong' => 'longevitydragon',
        'gates-kunlun' => 'kunlun',
        'lucky-neko' => 'luckyneko',
        'mahjong-fortune' => 'mahjong-fortune',
        'mermaid' => 'mermaid',
        'persian-gems' => 'persiangem',
        'playboy-mansion' => 'wildwest',
        'queen-aztec' => 'aztec',
        'sanguo' => 'sanguo',
        'stallion-gold' => 'stallion',
        'sweet-bonanza' => 'bonanza'
    ];

    protected $url;
    protected $orderTable = 'game_order_ng';

    /**
     * 验证session
     * @return mixed
     */
    public function VerifySession($params)
    {
        global $app;
        $logger = $app->getContainer()->logger;
        $logger->info('ng:VerifySession-params', $params);
        $return = [
            'data' => null,
            'error' => null
        ];
        $lobby = json_decode($this->config['lobby'], true);
        if (!isset($params['data']['playerToken']) || !isset($params['data']['gameCode']) || $params['data']['brandCode'] != $lobby['brandCode'] || $params['data']['groupCode'] != $lobby['groupCode']) {
            $return['error'] = [
                'code' => 1204,
                'message' => '无效运营商'
            ];
        } else {
            $account = $this->getGameAccount();
            $index = strpos($params['data']['playerToken'],"_");
            $res = substr($params['data']['playerToken'],0, $index);

            $logger->info('ng:VerifySession-account', $account);
            if (!isset($params['data']['playerToken']) || $res != $account['account']) {
                $return['error'] = [
                    'code' => 1300,
                    'message' => '无效玩家令牌'
                ];
            } else {
                $balanceData = $this->getThirdWalletBalance();
                $return['data'] = [
                    'nativeId' => $account['account'],
                    'currency' => $this->config['currency'],
                    'balance' => $balanceData[0]
                ];
            }
        }

        $logger->info('ng:VerifySession-return', $return);
        return $return;
    }

    //第一步 创建游戏账号
    public function childCreateAccount(string $account, string $password)
    {
        $lobby = json_decode($this->config['lobby'], true);
        $param = [
            'nativeId' => $account,
            'brandCode' => $lobby['brandCode'],
            'groupCode' => $lobby['groupCode'],
            'currencyCode' => $this->config['currency']
        ];

        $res = $this->requestParam('/operator/transfer/create-player', $param, true, $this->generateSignature($param));

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

        //余额转入第三方
        $res = $this->rollInThird();
        if (!$res['status']) {
            return [
                'status' => 886,
                'message' => $res['msg'],
                'url' => ''
            ];
        }

        $backUrl = $this->ci->get('settings')['website']['game_home_url'] ?? $_SERVER['HTTP_HOST'];
        $lobby = json_decode($this->config['lobby'], true);
        $param = [
            'playerToken' => $account['account'].'_'.time(),
            'brandCode' => $lobby['brandCode'],
            'groupCode' => $lobby['groupCode'],
            'gameCode' => $params['kind_id'],
            'redirectUrl' => $backUrl,
            'isFun' => 'false',
            'language' => $this->langs[LANG] ?? $this->langs['en-us']
        ];

        if ($param) {
            $url = "https://stg-".$this->gameCode[$params['kind_id']].".azureedge.net/";
            $queryString = http_build_query($param, '', '&');
            $url .= '?' . $queryString;
        }

        if ($url) {
            return ['status' => 0, 'url' => $url];
        }
        return ['status' => 1, 'message' => $this->lang->text('Failed to get login link')];
    }

    /**
     * 获取余额
     * 3.1.4 查询会员状态
     * API 查询会员账号当前状态、现有额度等信息
     * @return array
     */
    public function getThirdWalletBalance()
    {
        $lobby = json_decode($this->config['lobby'], true);
        $account = $this->getGameAccount();
        $param = [
            'nativeId' => $account['account'],
            'brandCode' => $lobby['brandCode'],
            'groupCode' => $lobby['groupCode']
        ];

        $res = $this->requestParam('/operator/transfer/wallet-balance', $param, false, $this->generateSignature($param));

        if (isset($res['status']) && $res['status'] === true) {
            if (isset($res['balance'])) {
                return [bcmul($res['balance'], 100, 0), bcmul($res['balance'], 100, 0)];
            }
        }

        $balance = FundsChild::where('pid', $this->wid)->where('game_type', $this->game_alias)->value('balance');
        return [0, $balance];
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
            $user_id = (new GameToken())->getUserId($val['playerNativeId']);

            $val['user_id'] = $user_id ?: 0;
            unset($val['tid']);

            $batchData[] = $val;

            $orders = [
                'user_id' => $user_id,
                'game' => 'GAME',
                'order_number' => $val['roundId'],
                'game_type' => 'NG',
                'type_name' => $this->lang->text('NG'),
                'play_id' => $this->game_id,
                'bet' => bcmul($val['amount'], 100, 0),
                'profit' => bcsub(bcmul($val['earn'], 100, 0), bcmul($val['amount'], 100, 0), 0),
                'send_money' => bcmul($val['earn'], 100, 0),
                'order_time' => $val['created'],
                'date' => substr($val['created'], 0, 10),
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
     * 添加游戏订单错误
     * @param $game_type
     * @param $insertData
     * @param $code
     * @param $msg
     */
    public function addGameOrderError($game_type, $insertData, $code, $msg)
    {
        $tmp_err = [
            'game_type' => $game_type,
            'json' => json_encode($insertData, JSON_UNESCAPED_UNICODE),
            'error' => $msg,
        ];
        \DB::table('game_order_error')->insert($tmp_err);
        GameApi::addElkLog(['code' => $code, 'message' => $msg], $game_type);
    }

    /**
     * 登出
     * 3.1.2 注销游戏
     * @return array|bool
     * @throws \Exception
     */
    public function quitChildGame()
    {
        return true;
    }

    public function checkMoney($data = null)
    {
        //确认转账
        $lobby = json_decode($this->config['lobby'], true);
        $param = [
            'brandCode' => $lobby['brandCode'],
            'groupCode' => $lobby['groupCode']
        ];

        if (is_array($data) && $data['balance']) {
            $res = $this->requestParam('/operator/transfer/transactions/'.$data['tradeNo'], $param,false, $this->generateSignature($param));
            if (isset($res['status']) && true === $res['status']) {
                //成功
                $this->updateGameMoneyError($data, abs(bcmul($res['amount'], 100, 0)));
            } else{
                $this->refundAction($data);
            }
        }
    }

    public function rollOutChildThird(int $balance, string $tradeNo)
    {
        $res = $this->transfer($balance, $tradeNo, 'OUT');
        return [$res, $balance];
    }

    public function rollInChildThird(int $balance, string $tradeNo)
    {
        return $this->transfer($balance, $tradeNo, 'IN');
    }

    /**
     * 转账
     * @param $balance
     * @param $tradeNo
     * @param string $type
     * @return bool|int
     */
    public function transfer($balance, $tradeNo, $type = 'IN')
    {
        $balance = bcdiv($balance, 100, 0);  //这边金额为分，第三方金额为元
        $account = $this->getGameAccount();

        $lobby = json_decode($this->config['lobby'], true);
        $param = [
            'nativeId' => $account['account'],
            'brandCode' => $lobby['brandCode'],
            'groupCode' => $lobby['groupCode'],
            'currencyCode' => $this->config['currency'],
            'amount' => floor($balance),       //amount参数只能为int型，向下取整
            'nativeTransactionId' => $tradeNo
        ];

        if ($type == 'IN') {
            $action = '/operator/transfer/deposit';
        } else {
            $action = '/operator/transfer/withdraw';
        }
        $res = $this->requestParam($action, $param, true, $this->generateSignature($param));

        if($res['status']) {
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
        $res = $this->requestParam('GetGameList', []);
        return $res;
    }

    /**
     * 发送请求
     * @param string $url 请求地址
     * @param array $param 请求参数
     * @param bool $is_post 是否为post请求
     * @param array $header 请求头参数
     * @param array $param2 请求参数 不加密码
     * @param string $method 请求方法
     * @return array|string
     */
    public function requestParam($action, array $param, bool $is_post = true, $header, $status = false, $is_order = false, $method = null)
    {
        if (!empty($action)) {
            $url = $this->config['apiUrl'] . $action;
            $header = [
                'accept: application/json',
                'content-type: application/json',
                'x-signature: ' . $header
            ];
        }

        if ($is_post) {
            $re = Curl::post($url, null, $param, $method, null, $header);
        } else {
            if ($param) {
                $queryString = http_build_query($param, '', '&');
                $url .= '?' . $queryString;
            }
            $re = Curl::get($url, null, false, $header);
        }

        GameApi::addRequestLog($url, 'NG', $param, $re, isset($re['status']) ? 'status:' . $re['status']:'');
        $res = json_decode($re, true);

        if ($status) {
            return $res;
        }
        if (!is_array($res)) {
            $res['message'] = $re;
            $res['status'] = false;
        } elseif (isset($res["code"])) {
            $res['status'] = false;
        } else {
            $res['status'] = true;
        }
        return $res;
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

    /**
     * 生成signature
     * @param array $data
     * @throws \Exception
     */
    public function generateSignature($data)
    {
        return hash_hmac('sha256', utf8_encode(json_encode($data)), utf8_encode($this->config['key']), false);
    }

    function getJackpot()
    {
        return 0;
    }
}