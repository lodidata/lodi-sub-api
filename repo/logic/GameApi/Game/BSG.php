<?php

namespace Logic\GameApi\Game;

use Logic\Define\Cache3thGameKey;
use Logic\GameApi\GameApi;
use Logic\GameApi\GameToken;
use Model\FundsChild;
use Utils\Curl;
use Model\user as UserModel;

/**
 *
 * @package Logic\GameApi\Game
 */
class BSG extends \Logic\GameApi\Api
{
    /**
     * @var string 订单表
     */
    protected $orderTable = 'game_order_bsg';
    protected $langs = [
        'zh-cn' => 'zh-cn',
        'en-us' => 'en',
        'th' => 'th',
        'vi' => 'vi',
        'jp' => 'jp',
        'cz' => 'cz',
        'fi' => 'fi',
        'it' => 'it',
        'no' => 'no',
        'es-mx' => 'es',
    ];

    /**
     * 验证session
     * @param $params
     * @return mixed
     */
    public function VerifySession($params)
    {
        global $app;
        if (!isset($params['token']) || !isset($params['hash'])) {
            $return = [
                'RESULT' => 'ERROR',
                'CODE' => 399
            ];
        } else {
            $account = $this->getGameAccount();
            if (!isset($params['token']) || $params['hash'] != $this->sign($params)) {
                $return = [
                    'RESULT' => 'Invalid hash',
                    'CODE' => 500
                ];
            } else {

                $return = [
                    'RESULT' => 'OK',
                    'USERID' => $account['account'],
                    'USERNAME' => $account['account'],
                    'CURRENCY' => $this->config['currency']
                ];
            }
        }
        $app->getContainer()->logger->info('bsg:VerifySession-return', ['params' => $params, 'return' => $return]);
        return $return;
    }

    /**
     *  bsg游戏验证用户信息
     * @param $params
     * @return array|array[]
     */
    public function getaccountinfo($params)
    {
        global $app;

        if (!isset($params['userId']) || !isset($params['hash'])) {
            $return = [
                'RESULT' => 'Internal Error',
                'CODE' => 399
            ];
        } else {
            $account = $this->getGameAccount();
            if (!isset($params['userId']) || $params['hash'] != $this->sign($params)) {
                $return = [
                    'RESULT' => 'Invalid hash',
                    'CODE' => 500
                ];
            } else {

                $return = [
                    'RESULT' => 'OK',
                    'USERNAME' => $account['account'],
                    'CURRENCY' => $this->config['currency']
                ];
            }
        }
        $app->getContainer()->logger->info('bsg:getaccountinfo-return', ['params' => $params, 'return' => $return]);
        return $return;
    }

    //第一步 创建游戏账号
    public function childCreateAccount(string $account, string $password)
    {
        return true;
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

        $back_url = $this->ci->get('settings')['website']['game_back_url'] ?? $_SERVER['HTTP_HOST'];
        $tid = $this->ci->get('settings')['app']['tid'];
        $data = [
            'token' => bin2hex($tid . '-' . $account['account']),
            'bankId' => $this->config['cagent'],
            'gameId' => $params['kind_id'],
            'mode' => 'real',
            'lang' => $this->langs[LANG] ?? $this->langs['en-us'],
            'homeUrl' => $back_url
        ];
        $loginUlr = $this->config['loginUrl'];
        return [
            'status' => 0,
            'url' => $loginUlr . "?" . http_build_query($data, '', '&'),
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
        $data = [
            'userId' => $account['account'],
            'bankId' => $this->config['cagent']
        ];
        $data['hash'] = $this->sign($data);
        $res = $this->requestParam('getBalance.do', $data);
        if ($res['responseStatus']) {
            return [(int)$res['RESPONSE']['BALANCE'], (int)$res['RESPONSE']['BALANCE']];
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
        return false;
    }

    /**
     * TODO 转出失败不退钱，接口问题
     * 检测是否有转入转出失败的记录
     * @param null $data
     */
    public function checkMoney($data = null)
    {
        //转入金额超时则在此查询
        if (is_array($data) && $data['balance']) {
            $account = $this->getGameAccount();
            $params = [
                'userId' => $account['account'],
                'transactionId' => $data['tradeNo']
            ];
            $params['hash'] = $this->sign($params);
            $res = $this->requestParam('transferStatus.do', $params);

            //响应成功
            if ($res['responseStatus']) {
                $this->updateGameMoneyError($data);
            } elseif (isset($res['RESPONSE']) && isset($res['RESPONSE']['CODE']) && $res['RESPONSE']['CODE'] == 302) {
                //订单不存在
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
        $res = $this->transfer(bcmul(-1, $balance, 0), $tradeNo);
        return [$res, $balance];
    }

    /**
     * 转入第三方
     * @param int $balance
     * @param string $tradeNo
     * @return array|bool
     */
    public function rollInChildThird(int $balance, string $tradeNo)
    {
        return $this->transfer($balance, $tradeNo);
    }

    /**
     * 转账
     * @param int $balance 正负 转入 转出
     * @param string $tradeNo
     * @return bool
     */
    public function transfer(int $balance, string $tradeNo)
    {
        $account = $this->getGameAccount();
        $data = [
            'userId' => $account['account'],
            'transactionId' => $tradeNo,
            'amount' => $balance,
        ];
        $data['hash'] = $this->sign($data);
        //转账
        $res = $this->requestParam('transfer.do', $data);

        if ($res['responseStatus']) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 查询游戏清单
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
        return true;
    }

    /**
     * 同步第三方游戏订单
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
            $user_id = (new GameToken())->getUserId($val['username']);
            if (!$user_id) {
                continue;
            }
            $val['user_id'] = $user_id;
            unset($val['id'], $val['tid']);
            $batchData[] = $val;

            $orders = [
                'user_id' => $user_id,
                'game' => 'GAME',
                'order_number' => $val['order_number'],
                'game_type' => 'BSG',
                'type_name' => $this->lang->text('BSG'),
                'play_id' => 124,
                'bet' => $val['bet_amount'],
                'profit' => $val['income'],
                'send_money' => $val['win_amount'],
                'order_time' => $val['bettime'],
                'date' => substr($val['bettime'], 0, 10),
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

    public function sign($data)
    {
        unset($data['hash']);
        $str = '';
        foreach ($data as $k => $v) {
            if (is_null($v) || $v === '')
                continue;
            $str .= $v;
        }
        $signStr = $str . $this->config['key'];

        return md5($signStr);
    }

    /**
     * 发送请求
     * @param string $action 请求方法
     * @param array $param 请求参数
     * @return array|string
     */
    public function requestParam(string $action, array $param)
    {
        if(is_null($this->config)){
            $ret = [
                'responseStatus' => false,
                'msg' => 'no api config'
            ];
            GameApi::addElkLog($ret,'BSG');
            return $ret;
        }
        $url = $this->config['apiUrl'] . $action;
        if (!isset($param['bankId'])) {
            $param['bankId'] = $this->config['cagent'];
        }

        $url = $url . '?' . urldecode(http_build_query($param));
        $re = Curl::get($url, null, true);

        if ($re['status'] != 200) {
            $ret['responseStatus'] = false;
            $ret['status'] = $re['status'];
            $ret['msg'] = $re['content'];
            GameApi::addRequestLog($url, 'BSG', $param, json_encode($re, JSON_UNESCAPED_UNICODE));
        } else {
            $ret = $this->parseXML($re['content']);
            if (isset($ret['RESPONSE']) && $ret['RESPONSE']['RESULT'] == 'OK') {
                $ret['responseStatus'] = true;
            } else {
                $ret['responseStatus'] = false;
            }
            GameApi::addRequestLog($url, $this->game_type, $param, json_encode($ret, JSON_UNESCAPED_UNICODE));
        }
        return $ret;
    }

    function getJackpot()
    {
        return 0;
    }
}

