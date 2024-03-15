<?php

namespace Logic\GameApi\Game;

use Logic\GameApi\GameApi;
use Logic\GameApi\GameToken;
use Model\FundsChild;
use Model\Orders;
use Utils\Curl;
use Model\user as UserModel;
use Logic\Define\Cache3thGameKey;
use function Aws\filter;

class HB extends \Logic\GameApi\Api
{
    protected $url;
    protected $orderTable = 'game_order_hb';
    protected $langs = [
        'th' => 'th',
        'zh-cn' => 'zh-CN',
        'en-us' => 'en',
        'vn' => 'vi',
        'es-mx' => 'pt',
    ];

    //创建游戏账号
    public function childCreateAccount(string $account, string $password)
    {
        $data = [
            'BrandId' => $this->config['des_key'],
            'APIKey' => $this->config['key'],
            'CurrencyCode' => $this->config['currency'],
            'UserAgent' => $_SERVER['HTTP_USER_AGENT'],
            'Username' => $account,
            'Password' => $password,
        ];
        $res = $this->requestParam('loginorcreateplayer', $data);
        if ($res['responseStatus'] && $res['Authenticated']) {
            return $res['Token'];
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
        // 登录
        try {
            $token = $this->childCreateAccount($account['account'], $account['password']);
            // 获取游戏brandgameid
            $brandGameId = $this->getBrandGameId($params);
            $url = $this->config['loginUrl'] . '?brandid=' . $this->config['des_key'] . '&brandgameid=' . $brandGameId . '&token=' . $token . '&mode=real&locale=' . ($this->langs[LANG] ?? $this->langs['en-us']);
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
                'url' => $url,
                'message' => 'ok'
            ];
        } catch (\Exception $e) {
            return [
                'status' => -1,
                'message' => $e->getMessage(),
                'url' => ''
            ];
        }
    }

    // 进入游戏时通过KeyName获取游戏的brandGameId
    public function getBrandGameId($params)
    {
        $cacheKey = 'hb_brand_game_ids';
        $brandGameIds = $this->redis->get($cacheKey);
        if (empty($brandGameIds)) {
            $brandGameIds = $this->requestParam('GetGames', ['BrandId' => $this->config['des_key'], 'APIKey' => $this->config['key']]);
            $this->redis->setex($cacheKey, 3600 * 24, json_encode($brandGameIds));
        } else {
            $brandGameIds = json_decode($brandGameIds, true);
        }
        $brandGame = array_filter($brandGameIds['Games'], function ($v) use ($params) {
            return $v['KeyName'] == $params['kind_id'];
        });
        sort($brandGame);
        $brandGameId = $brandGame[0]['BrandGameId'];
        return $brandGameId;
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
        $params = [
            'BrandId' => $this->config['des_key'],
            'APIKey' => $this->config['key'],
            'Username' => $account['account'],
            'Password' => $account['password']
        ];
        $res = $this->requestParam('QueryPlayer', $params);
        if ($res['responseStatus']) {
            if (isset($res['RealBalance'])) {
                return [bcmul($res['RealBalance'], 100, 0), bcmul($res['RealBalance'], 100, 0)];
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
            11 => ['id' => 128, 'game' => 'GAME', 'type' => 'HB'],
            8 => ['id' => 129, 'game' => 'TABLE', 'type' => 'HBTAB'],
            6 => ['id' => 130, 'game' => 'QP', 'type' => 'HBQP'],
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
            $user_id = (new GameToken())->getUserId($val['Username']);
            if (!$user_id) {
                continue;
            }
            $val['user_id'] = $user_id;
            unset($val['id'], $val['tid']);
            if (!isset($platformTypes[$val['GameTypeId']])) {
                $val['GameTypeId'] = 11;
            }

            $batchData[] = $val;

            $orders = [
                'user_id' => $user_id,
                'game' => $platformTypes[$val['GameTypeId']]['game'],
                'order_number' => $val['GameInstanceId'],
                'game_type' => $platformTypes[$val['GameTypeId']]['type'],
                'type_name' => $this->lang->text($platformTypes[$val['GameTypeId']]['type']),
                'play_id' => $platformTypes[$val['GameTypeId']]['id'],
                'bet' => $val['Stake'],
                'profit' => bcsub($val['Payout'], $val['Stake'], 0),
                'send_money' => $val['Payout'],
                'order_time' => $val['DtCompleted'],
                'date' => substr($val['DtCompleted'], 0, 10),
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
        $param = [
            'BrandId' => $this->config['des_key'],
            'APIKey' => $this->config['key']
        ];

        if (is_array($data) && $data['balance']) {
            $account = $this->getGameAccount();
            // 查询玩家上下分订单
            $param['RequestId'] = ($data['transfer_type'] == 'in' ? 'deposit_' : 'withdraw_') . $data['tradeNo'];
            $param['Username'] = $account['account'];
            $res = $this->requestParam('QueryTransfer', $param);
            if (isset($res['responseStatus']) && true === $res['responseStatus']) {
                //成功
                if ($res['Success']) {
                    $this->updateGameMoneyError($data, abs(bcmul($res['Amount'], 100, 0)));
                } else {
                    $this->refundAction($data);
                }
            }
        }
    }

    public function rollOutChildThird(int $balance, string $tradeNo)
    {
        $account = $this->getGameAccount();

        $data = [
            'BrandId' => $this->config['des_key'],
            'APIKey' => $this->config['key'],
            'Username' => $account['account'],
            'Password' => $account['password'],
            'CurrencyCode' => $this->config['currency'],
            'Amount' => -bcdiv($balance, 100, 2), // 提现金额为负数
            'WithdrawAll' => true,
            'RequestId' => 'withdraw_' . $tradeNo,
        ];
        $res = $this->requestParam('WithdrawPlayerMoney', $data);
        return [$res['Success'], $balance];
    }

    public function rollInChildThird(int $balance, string $tradeNo)
    {
        $balance = bcdiv($balance, 100, 2);  //这边金额为分，  第三方金额为元
        $account = $this->getGameAccount();

        $data = [
            'BrandId' => $this->config['des_key'],
            'APIKey' => $this->config['key'],
            'Username' => $account['account'],
            'Password' => $account['password'],
            'CurrencyCode' => $this->config['currency'],
            'Amount' => $balance,
            'RequestId' => 'deposit_' . $tradeNo,
        ];
        $res = $this->requestParam('DepositPlayerMoney', $data);
        if ($res['responseStatus']) {
            return true;
        }
        return false;
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
     * @param bool $status 是否返回请求状态
     * @param bool $is_header 是否带头部信息
     * @param bool $is_order 是否为获取注单
     * @return array|string
     */
    public function requestParam(string $action, array $param, bool $is_post = true, $status = true, $is_header = true, $is_login = false)
    {
        if(is_null($this->config)){
            $ret = [
                'responseStatus' => false,
                'msg' => 'no api config'
            ];
            GameApi::addElkLog($ret,'HB');
            return $ret;
        }

        $url = rtrim($this->config['apiUrl']) . $action;
        $headers = array(
            'Content-Type: application/json',
        );
        if ($is_post) {
            $re = Curl::commonPost($url, null, json_encode($param), $headers, $status);
        } else {
            $queryString = http_build_query($param, '', '&');
            if ($queryString) {
                $url .= '?' . $queryString;
            }
            $re = Curl::get($url, null, $status, $headers);
        }
        GameApi::addRequestLog($url, $this->game_type, $param, json_encode($re, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $ret = json_decode($re['content'], true);
        if ($re['status'] == 200) {
            $ret['responseStatus'] = true;
        } else {
            $ret['responseStatus'] = false;
            $ret['msg'] = isset($ret['content']) ?? 'api error';
        }
        return $ret;
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

