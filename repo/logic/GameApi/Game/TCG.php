<?php

namespace Logic\GameApi\Game;

use Logic\GameApi\GameApi;
use Logic\GameApi\GameToken;
use Model\FundsChild;

/**
 * TCG彩票
 * Class TCG
 * @package Logic\GameApi\Game
 */
class TCG extends \Logic\GameApi\Api
{
    protected $langs = [
        'th' => 'TH',
        'zh-cn' => 'CN',
        'en-us' => 'EN',
        'vn' => 'VI',
        'ko' => 'KO',
        'tw' => 'TW',
        'id' => 'ID',
        'ma' => 'MS',
        'jp' => 'JA',
        'es-mx' => 'PT',
        'km' => 'KM',
    ];
    protected $orderTable = 'game_order_tcg';

    /**
     * 无需创建账户，第三方会自动判定
     * @param string $account
     * @param string $password
     * @return bool
     * @throws \Exception
     */
    public function childCreateAccount(string $account, string $password)
    {
        $param = [
            'method' => 'cm',
            'username' => $account,
            'password' => substr($password, 0, 12),   //由 6 到 12 位数字或大小写字母组成
            'currency' => $this->config['currency'] ?? 'THB'
        ];
        $res = $this->requestParam($param);
        if ($res['responseStatus']) {
            return true;
        }
        return false;
    }

    //进入游戏
    public function getJumpUrl(array $params = [])
    {
        $lobby = json_decode($this->config['lobby'], true);

        //检测并创建账号
        $account = $this->getGameAccount();
        if (!$account) {
            return [
                'status' => 133,
                'message' => $this->lang->text(133),
                'url' => ''
            ];
        }

        $back_url = $this->ci->get('settings')['website']['game_back_url'] ?? $_SERVER['HTTP_HOST'];
        $series = [
            [
                "game_group_code" => "MAS",
                "prize_mode_id" => 1
            ], [
                "game_group_code" => "VNC",
                "prize_mode_id" => 1
            ], [
                "game_group_code" => "THAI",
                "prize_mode_id" => 1
            ], [
                "game_group_code" => "SGC",
                "prize_mode_id" => 1
            ], [
                "game_group_code" => "LAO",
                "prize_mode_id" => 1
            ], [
                "game_group_code" => "STOCK",
                "prize_mode_id" => 1
            ], [
                "game_group_code" => "TWC",
                "prize_mode_id" => 1
            ]
        ];

        $data = [
            'method' => 'lg',
            'username' => $account['account'],
            'product_type' => $lobby['product_type'],
            'platform' => 'html5',
            'game_mode' => $lobby['game_mode'],
            'game_code' => $params['kind_id'],
            'language' => $this->langs[LANG] ?? $this->langs['en-us'],
            'back_url' => urlencode($back_url),
            'view' => $lobby['view'],
            'lottery_bet_mode' => $lobby['lottery_bet_mode'],
            'series' => $series
        ];

        $res = $this->requestParam($data);

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
                'url' => $this->config['loginUrl'].'/'.$res['game_url'],
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

        //注单列表
        $batchData = [];
        //orders列表
        $batchOrderData = [];

        //打码量配置
        $auditSetting = \Logic\Set\SystemConfig::getModuleSystemConfig('audit');

        foreach ($data as $key => $val) {
            $user_id = (new GameToken())->getUserId($val['username']);
            if (!$user_id) continue;
            $val['user_id'] = $user_id;
            unset($val['id'], $val['tid']);
            $batchData[] = $val;

            $orders = [
                'user_id' => $user_id,
                'game' => 'TCP',
                'order_number' => $val['orderNum'],
                'game_type' => 'TCG',
                'type_name' => 'TCG',
                'play_id' => 144,
                'bet' => bcmul($val['betAmount'], 100, 0),
                'profit' => bcmul($val['netPNL'], 100, 0),
                'send_money' => bcmul($val['winAmount'], 100, 0),
                'date' => $val['settlementTime'],
                'order_time' => $val['settlementTime'],
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
        $params = [
            'method' => 'kom',
            'username' => $account['account']
        ];
        $res = $this->requestParam($params);
        if ($res['responseStatus']) {
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
        if (is_array($data) && $data['balance']) {
            $lobby = json_decode($this->config['lobby'], true);
            $params = [
                'method' => 'cs',
                'product_type' => $lobby['product_type'],
                'ref_no' => $data['tradeNo']
            ];
            $res = $this->requestParam($params);
            //PENDING = 延迟，SUCCESS = 成功，FAILED = 失败，UNKNOWN = 未知，NOT FOUND = 未找到
            if (isset($res['transaction_status']) && $res['transaction_status'] == 'SUCCESS') {
                $this->updateGameMoneyError($data, bcmul(abs($res['transaction_details']['amount']), 100, 0));
            } else {
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
        $lobby = json_decode($this->config['lobby'], true);
        $account = $this->getGameAccount();
        $param = [
            'method' => 'gb',
            'username' => $account['account'],
            'product_type' => $lobby['product_type']
        ];
        $res = $this->requestParam($param);
        if (isset($res['status']) && $res['status'] == 0) {
            //该会员的余额，現存結余, 不包括已经下注的金额. 精确到分
            return [bcmul($res['balance'], 100, 0), bcmul($res['balance'], 100, 0)];
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
        $res = $this->transfer($balance, $tradeNo, 'OUT');
        return [$res, $balance];
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
        return $this->transfer($balance, $tradeNo, 'IN');
    }

    /**
     * 确认转账
     * 3.1.13 额度转移
     * TransferType转账类型
     * 1: 从 游戏商 转移额度到 平台商 (不看 amount 值，全部转出)
     * 2: 从 平台商 转移额度到 游戏商
     * 3: 从 游戏商 转移额度到 平台商
     * @param $balance
     * @param $tradeNo
     * @param string $type
     * @return bool|int
     */
    public function transfer($balance, $tradeNo, $type = 'IN')
    {
        $account = $this->getGameAccount();
        $lobby = json_decode($this->config['lobby'], true);
        $param = [
            'method' => 'ft',
            'username' => $account['account'],
            'product_type' => $lobby['product_type'],
            'fund_type' => $type == 'OUT' ? 2 : 1,
            'amount' => bcdiv($balance, 100, 2),
            'reference_no' => $tradeNo
        ];

        $res = $this->requestParam($param);
        if ($res['transaction_status'] == 'SUCCESS') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 发送请求
     * @param array $param 请求参数
     * @return array|string
     */
    public function requestParam(array $params)
    {
        if(is_null($this->config)){
            $ret = [
                'responseStatus' => false,
                'error' => ['message' => 'no api config']
            ];
            GameApi::addElkLog($ret,'TCG');
            return $ret;
        }
        $param = $this->encryptText(json_encode($params), $this->config['des_key']);
        $sign = hash('sha256', $param . $this->config['key']);
        $data = ['merchant_code' => $this->config['cagent'], 'params' => $param, 'sign' => $sign];

        $options = array(
            'http' => array(
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            )
        );
        $context = stream_context_create($options);
        $re = json_decode(file_get_contents($this->config['apiUrl'], false, $context), true);

        $paramLog = [
            'param' => $params,
            'encryptData' => $data
        ];

        if ($re['status'] != 0) {
            $ret['responseStatus'] = false;
            $ret['networkStatus'] = $re['status'];
            $ret['error']['message'] = $re['error_desc'];
            GameApi::addRequestLog($this->config['apiUrl'], 'TCG', $paramLog, json_encode($re, JSON_UNESCAPED_UNICODE));
        } else {
            $ret = $re;
            $ret['networkStatus'] = $re['status'];
            $ret['responseStatus'] = true;

            GameApi::addRequestLog($this->config['apiUrl'], 'TCG', $paramLog, json_encode($ret, JSON_UNESCAPED_UNICODE));
        }
        return $ret;
    }

    /**
     * 组建 Params 加密参數
     * @param $plainText
     * @param $key
     * @return string
     */
    function encryptText($plainText, $key)
    {
        $padded = $this->pkcs5_pad($plainText, 8);
        $encText = openssl_encrypt($padded, 'des-ecb', $key, OPENSSL_RAW_DATA, '');

        return base64_encode($encText);
    }

    function pkcs5_pad($text, $blocksize)
    {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }

    function getJackpot()
    {
        return 0;
    }
}
