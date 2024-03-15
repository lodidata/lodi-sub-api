<?php

namespace Logic\GameApi\Game;

use Logic\GameApi\GameApi;
use Logic\GameApi\GameToken;
use Model\FundsChild;
use Model\Orders;
use Utils\Curl;
use Model\user as UserModel;
use Logic\Define\Cache3thGameKey;

class RSG extends \Logic\GameApi\Api
{
    protected $url;
    protected $orderTable = 'game_order_rsg';
    protected $langs = [
        'th' => 'th-TH',
        'zh-cn' => 'zh-CN',
        'en-us' => 'en-US',
        'vn' => 'vi-VN',
    ];

    //第一步 创建游戏账号
    public function childCreateAccount(string $account, string $password)
    {
        $lobby = json_decode($this->config['lobby'], true);
        $data = [
            'SystemCode' => $lobby['SystemCode'],
            'UserId' => $account,
            'Currency' => $this->config['currency'],
            'WebId' => $lobby['WebId'],
        ];
        $res = $this->requestParam('/Player/CreatePlayer', $data);
        //0成功 101账户已经存在
        if (isset($res['ErrorCode']) && $res['ErrorCode'] == 0) {
            return true;
        }
        return false;
    }


    //进入游戏
    //2.1.1 登入
    //2.1.2 登入返還位置
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

        // 登录
        $origin = isset($this->request->getHeaders()['HTTP_PL']) && is_array($this->request->getHeaders()['HTTP_PL']) ? current($this->request->getHeaders()['HTTP_PL']) : '';
        if (in_array($origin, ['pc', 'h5'])) {
            $is_app = false;
        } else {
            $is_app = true;
        }
        $back_url = $this->ci->get('settings')['website']['game_home_url'] ?? $_SERVER['HTTP_HOST'];
        try {
            $lobby = json_decode($this->config['lobby'], true);
            $data = [
                'SystemCode' => $lobby['SystemCode'],
                'WebId' => $lobby['WebId'],
                'UserId' => $account['account'],
                'UserName' => $account['account'],
                'GameId' => (int) $params['kind_id'],
                'Currency' => $this->config['currency'],
                'Language' => $this->langs[LANG] ?? $this->langs['en-us'],
                'ExitAction' => $back_url,
            ];
            //进入游戏
            //$res = $this->requestParam('Login', $data, false);
            //返回游戏位置
            $res = $this->requestParam('/Player/GetURLToken', $data);
            if ($res['ErrorCode'] == 0) {
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
                    'url' => $res['Data']['URL'],
                    'message' => 'ok'
                ];

            } elseif ($res['ErrorCode'] == 3008) {
                //用户不存在，要重新注册
                $reg_res = $this->childCreateAccount($account['account'], $account['password']);
                if ($reg_res) {
                    $this->getJumpUrl($params);
                } else {
                    throw new \Exception('system error');
                }
            } else {
                throw new \Exception($res['Message']);
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
        $lobby = json_decode($this->config['lobby'], true);
        $data = [
            'SystemCode' => $lobby['SystemCode'],
            'WebId' => $lobby['WebId'],
            'UserId' => $account['account'],
            'Currency' => $this->config['currency'],
        ];
        $res = $this->requestParam('/Player/GetBalance', $data);
        if (isset($res['ErrorCode']) && $res['ErrorCode'] == 0) {
            if (isset($res['Data']['CurrentPlayerBalance'])) {
                return [bcmul($res['Data']['CurrentPlayerBalance'], 100, 0), bcmul($res['Data']['CurrentPlayerBalance'], 100, 0)];
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
        $platformTypes = [
            150 => ['game' => 'GAME', 'type' => 'RSG'],
            151 => ['game' => 'BY', 'type' => 'RSGBY'],
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

            $user_id = (new GameToken())->getUserId($val['UserId']);
            if (!$user_id)
                continue;

            $val['user_id'] = $user_id;
            unset($val['id'], $val['tid']);

            //不存在分类默认为电子
            if (!isset($platformTypes[$val['game_menu_id']])) {
                $val['game_menu_id'] = 150;
            }
            $batchData[] = $val;

            $orders = [
                'user_id' => $user_id,
                'game' => $platformTypes[$val['game_menu_id']]['game'],
                'order_number' => $val['SequenNumber'],
                'game_type' => $platformTypes[$val['game_menu_id']]['type'],
                'type_name' => $this->lang->text($platformTypes[$val['game_menu_id']]['type']),
                'play_id' => $val['game_menu_id'],
                'bet' => $val['BetAmt'],
                'profit' => $val['WinAmt'] - $val['BetAmt'],
                'send_money' => $val['WinAmt'],
                'order_time' => $val['PlayTime'],
                'date' => substr($val['PlayTime'], 0, 10),
                'created' => date('Y-m-d H:i:s')
            ];
            $gameAduitSetting = isset($auditSetting[$orders['game']]) && $auditSetting[$orders['game']] ? bcdiv($auditSetting[$orders['game']], 100, 2) : 1; //游戏类型打码量设置，如果不存在则为1
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
        $account = $this->getGameAccount();
        $lobby = json_decode($this->config['lobby'], true);
        $data = [
            'KickType' => 4,
            'SystemCode' => $lobby['SystemCode'],
            'WebId' => $lobby['WebId'],
            'UserId' => $account['account'],
            'GameId' => 0,
        ];

        $result = $this->requestParam('/Player/Kickout', $data);
        return $result['ErrorCode'] ? false : true;
    }

    public function checkMoney($data = null)
    {
        //确认转账
        $param = [];

        if (is_array($data) && $data['balance']) {
            // 查询玩家上下分订单
            $lobby = json_decode($this->config['lobby'], true);
            $param = [
                'SystemCode' => $lobby['SystemCode'],
                'TransactionID' => $data['tradeNo'],
            ];
            $res = $this->requestParam('/Player/GetTransactionResult', $param);
            if ($res['responseStatus'] === true && isset($res['ErrorCode']) && 0 == $res['ErrorCode']) {
                //成功
                $this->updateGameMoneyError($data, abs(bcmul($res['Data']['Balance'], 100, 0)));

            }
            //交易记录不存在
            if ($res['responseStatus'] === true && isset($res['ErrorCode']) && 3006 == $res['ErrorCode']) {
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
        //本地显示150.2  线上显示 150.199999999999 以下操作变成150.19  
        $balance = bcdiv($balance, 100, 2); //这边金额为分，  第三方金额为元
        $account = $this->getGameAccount();
        $lobby = json_decode($this->config['lobby'], true);
        $data = [
            'SystemCode' => $lobby['SystemCode'],
            'WebId' => $lobby['WebId'],
            'UserId' => $account['account'],
            'TransactionID' => $tradeNo,
            'Currency' => $this->config['currency'],
            'Balance' => floatval($balance),
        ];
        GameApi::addElkLog($data, 'RSG');
        $res = $this->requestParam($type == 'IN' ? '/Player/Deposit' : '/Player/Withdraw', $data);
        if (!isset($res['ErrorCode'])) {
            return false;
        }

        if ($res['ErrorCode'] == 0) {
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
     * @param string $action 请求方法
     * @param array $param 请求参数
     * @param bool $is_post 是否为post请求
     * @return array|string
     */
    public function requestParam(string $action, array $param, bool $is_post = true)
    {
        if (is_null($this->config)) {
            $ret = [
                'responseStatus' => false,
                'ErrorCode' => '886',
                'Message' => 'no api config'
            ];
            GameApi::addElkLog($ret, 'RSG');
            return $ret;
        }

        // des加密后拼接字符串md5加密
        $current_timestamp = time();
        $encryptText = $this->encryptText($param);
        $sign_data = md5($this->config['cagent'] . $this->config['key'] . $current_timestamp . $encryptText);
        $requestBodyString = 'Msg=' . $encryptText;

        $headers = [
            "Content-Type: application/x-www-form-urlencoded",
            "X-API-ClientID:" . $this->config['cagent'],
            "X-API-Signature:" . $sign_data,
            "X-API-Timestamp:" . $current_timestamp
        ];

        $url = $this->config['apiUrl'] . $action;
        if ($is_post) {
            $re = Curl::commonPost($url, null, $requestBodyString, $headers, true);
        } else {
            $re = Curl::get($url, null, true);
        }
        if ($re['status'] == 200) {
            $re['content'] = openssl_decrypt(base64_decode($re['content']), 'DES-CBC', $this->config['des_key'], OPENSSL_RAW_DATA, $this->config['pub_key']);
            $re['content'] = json_decode($re['content'], true);
        }
        GameApi::addRequestLog($url, 'RSG', $param, json_encode($re, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $ret = [];
        if ($re['status'] == 200) {
            $ret = $re['content'];
            $ret['responseStatus'] = true;
        } elseif ($re['status'] == 0) {
            $ret['ErrorCode'] = '886';
            $ret['responseStatus'] = false;
            $ret['Message'] = 'api error';
        } else {
            $ret['ErrorCode'] = $ret['ErrorCode'] ?? '886';
            $ret['responseStatus'] = false;
            $ret['Message'] = $ret['ErrorMessage'] ?? 'api error';
        }
        return $ret;
    }

    public function encryptText(array $param)
    {
        ini_set('serialize_precision', -1);
        $encrypt_data = openssl_encrypt(json_encode($param), 'DES-CBC', $this->config['des_key'], OPENSSL_RAW_DATA, $this->config['pub_key']);
        $req_base64 = base64_encode($encrypt_data);
        return $req_base64;
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