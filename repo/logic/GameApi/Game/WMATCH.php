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

class WMATCH extends \Logic\GameApi\Api
{
    protected $url;
    protected $orderTable = 'game_order_wmatch';
    protected $langs = [
        'th' => 'TH',
        'zh-cn' => 'ZH',
        'en-us' => 'EN',
        'vn' => 'VI',
        'es-mx' => 'PT',
    ];

    //创建游戏账号
    public function childCreateAccount(string $account, string $password)
    {
        $data = [
            'UserID' => $account,
            'Currency' => $this->config['currency'],
            'TestUser' => false,
            'UserName' => $account,
            'Skin' => $this->config['cagent'],
        ];
        $res = $this->requestParam('/Platform/Feed/Wallet/User/' . $this->config['key'], $data);
        if ($res['responseStatus'] && $res['UserToken']) {
            return $res['UserToken'];
        }
        return false;
    }


    //进入游戏 并创建用户
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
        $parmas = [];
        $res = $this->requestParam('/Platform/Feed/Wallet/Auth/' . $this->config['key'] . '?id=' . $account['account'], $parmas, false);
        if (!$res['responseStatus']) {
            return [
                'status' => 886,
                'message' => $res['message'] ?? 'api error',
                'url' => ''
            ];
        }
        $authKey = $res['AuthKey'];

        //余额转入第三方
        $result = $this->rollInThird();
        if (!$result['status']) {
            return [
                'status' => 886,
                'message' => $result['msg'],
                'url' => ''
            ];
        }
        $lang = $this->langs[LANG] ?? $this->langs['en-us'];
        $url = $this->config['loginUrl'] . '/games/' . $this->config['des_key'] . '/real/' . $params['alias'] . '/?authuser=' . $account['account'] . '&authkey=' . $authKey . '&authskin=' . $this->config['cagent'] . '&language='.$lang.'&display=iframe';

        return [
            'status' => 0,
            'url' => $url ?? '',
            'message' => 'ok'
        ];
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
        $params = [];
        $res = $this->requestParam('/Platform/Feed/Wallet/Balance/' . $this->config['key'] . '?id=' . $account['account'], $params, false);
        if ($res['responseStatus']) {
            if (isset($res['Balance'])) {
                return [bcmul($res['Balance'], 100, 0), bcmul($res['Balance'], 100, 0)];
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
            140 => ['game' => 'GAME', 'type' => 'WMATCH'],
            141 => ['game' => 'BY', 'type' => 'WMATCHBY'],
            142 => ['game' => 'QP', 'type' => 'WMATCHQP'],
            143 => ['game' => 'TABLE', 'type' => 'WMATCHTAB'],
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
            $user_id = (new GameToken())->getUserId($val['externalUserId']);
            if (!$user_id) {
                continue;
            }
            $val['user_id'] = $user_id;
            unset($val['id'], $val['tid']);
            if (!isset($platformTypes[$val['gameTypeId']])) {
                $val['gameTypeId'] = 140;
            }
            $batchData[] = $val;

            $orders = [
                'user_id' => $user_id,
                'game' => $platformTypes[$val['gameTypeId']]['game'],
                'order_number' => $val['roundId'],
                'game_type' => $platformTypes[$val['gameTypeId']]['type'],
                'type_name' => $this->lang->text($platformTypes[$val['gameTypeId']]['type']),
                'play_id' => $val['gameTypeId'],
                'bet' => $val['totalBetAmount'],
                'profit' => bcsub($val['totalWinAmount'], $val['totalBetAmount'], 0),
                'send_money' => $val['totalWinAmount'],
                'order_time' => $val['roundEndTime'],
                'date' => substr($val['roundEndTime'], 0, 10),
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
        if (is_array($data) && $data['balance']) {
            // 查询玩家上下分订单
            $res = $this->requestParam('/Platform/Feed/Wallet/' . ($data['transfer_type'] == 'in' ? 'Credit' : 'Debit') . '/' . $this->config['key'] . '?ref=' . $data['tradeNo'], [], false);
            if (isset($res['responseStatus']) && true === $res['responseStatus']) {
                //成功
                if ($res['Status'] == 'GRANTED') {
                    $this->updateGameMoneyError($data, abs(bcmul($res['Amount'], 100, 0)));
                }
            }
            //交易记录不存在
            if (isset($res['errorCode']) && 404 === $res['errorCode']) {
                $this->refundAction($data);
            }
        }
    }

    public function rollOutChildThird(int $balance, string $tradeNo)
    {
        $account = $this->getGameAccount();
        $data = [
            'UserID' => $account['account'],
            'Currency' => $this->config['currency'],
            'Amount' => bcdiv($balance, 100, 2),
            'Reference' => $tradeNo,
        ];
        $res = $this->requestParam('/Platform/Feed/Wallet/Debit/' . $this->config['key'], $data);
        return [$res['Status'] == 'GRANTED', $balance];
    }

    public function rollInChildThird(int $balance, string $tradeNo)
    {
        $balance = bcdiv($balance, 100, 2);  //这边金额为分，  第三方金额为元
        $account = $this->getGameAccount();
        $data = [
            'UserID' => $account['account'],
            'Currency' => $this->config['currency'],
            'Amount' => $balance,
            'Reference' => $tradeNo,
        ];
        $res = $this->requestParam('/Platform/Feed/Wallet/Credit/' . $this->config['key'], $data);
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
        GameApi::addRequestLog($url, 'WMATCH', $param, json_encode($re, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $ret = json_decode($re['content'], true);
        if ($re['status'] == 200) {
            $ret['responseStatus'] = true;
        } else {
            $ret['responseStatus'] = false;
            $ret['errorCode'] = $re['status'];
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

