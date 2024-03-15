<?php

namespace Logic\GameApi\Game;

use Logic\Define\Cache3thGameKey;
use Logic\GameApi\GameApi;
use Logic\GameApi\GameToken;
use Model\FundsChild;
use Utils\Client;
use Utils\Curl;
use Model\user as UserModel;

/**
 * LD集成环境
 * Class LDAPI
 * @package Logic\GameApi\Game
 */
class LDAPI extends \Logic\GameApi\Api
{
    protected $langs = [
        'th'    => 'th',
        'zh-cn' => 'zh',
        'en-us' => 'en',
        'vn'    => 'vi',
    ];

    protected $orderDataTypes = [];


    //第一步 创建游戏账号
    public function childCreateAccount(string $account, string $password)
    {
        $data = [
            'userId'       => $account,
            'userPassword' => substr($password, 0, 12),
            'userName'     => $account,
            'language'     => $this->langs[LANG] ?? $this->langs['en-us']
        ];
        $res = $this->requestParam('createUser', $data);
        if ($res['responseStatus']) {
            return true;
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
                'status'  => 133,
                'message' => $this->lang->text(133),
                'url'     => ''
            ];
        }

        $back_url = $this->ci->get('settings')['website']['game_back_url'];
        $origin = isset($this->request->getHeaders()['HTTP_PL']) && is_array($this->request->getHeaders()['HTTP_PL']) ? current($this->request->getHeaders()['HTTP_PL']) : '';
        $data = [
            'userId'        => $account['account'],
            'userPassword'  => substr($account['password'], 0, 12),
            'userName'      => $account['account'],
            'gameType'      => $this->type,
            'gameCode'      => $params['kind_id'],
            'loginIp'       => Client::getIp(),
            'isMobileLogin' => !($origin == 'pc'),
            'homeURL'       => $back_url,
            'language'      => $this->langs[LANG] ?? $this->langs['en-us']
        ];
        $res = $this->requestParam('login', $data);
        if ($res['state'] == 0) {
            //余额转入第三方
            $result = $this->rollInThird();
            if (!$result['status']) {
                return [
                    'status'  => 886,
                    'message' => $result['msg'],
                    'url'     => ''
                ];
            }
            return [
                'status'  => 0,
                'url'     => $res['data']['gameUrl'],
                'message' => 'ok'
            ];
        } else {
            return [
                'status'  => -1,
                'message' => $res['message'],
                'url'     => ''
            ];
        }
    }

    public function getGameList()
    {
        $data = [
            'language' => $this->langs[LANG] ?? $this->langs['en-us'],
        ];
        $res = $this->requestParam('getGameList', $data);
        if ($res['responseStatus']) {
            return $res['data'];
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
            'userId'       => $account['account'],
            'userPassword' => substr($account['password'], 0, 12),
            'language'     => $this->langs[LANG] ?? $this->langs['en-us'],
        ];
        $res = $this->requestParam('getBalance', $data);
        if ($res['responseStatus']) {
            return [bcmul($res['data']['balance'], 100, 0), bcmul($res['data']['balance'], 100, 0)];
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
        return true;
    }

    /**
     * 检测是否有转入转出失败的记录
     * @param null $data
     */
    public function checkMoney($data = null)
    {
        //转入金额超时则在此查询
        if (is_array($data) && $data['balance']) {
            $account = $this->getGameAccount();
            $params = [
                'userId'         => $account['account'],
                'transferNumber' => $data['tradeNo']
            ];
            $res = $this->requestParam('checkTransfer', $params);
            //响应成功
            if ($res['responseStatus'] && isset($res['data']['transferStatus'])) {
                //转入成功不处理
                if ($res['data']['transferStatus'] == 1) {
                    $this->updateGameMoneyError($data, abs($data['balance']));
                } else if (in_array($res['data']['transferStatus'], [2, 4])) {
                    $this->refundAction($data);
                }
            }elseif (in_array($res['state'],[1021,1061])){
                //查无资料，转入退款
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
            'userId'         => $account['account'],
            'balance'        => bcdiv($balance, 100, 2),
            'transferNumber' => $tradeNo,
        ];
        $res = $this->requestParam('withDraw', $data);
        if ($res['responseStatus']) {
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
            'userId'         => $account['account'],
            'balance'        => bcdiv($balance, 100, 2),
            'transferNumber' => $tradeNo,
        ];
        $res = $this->requestParam('deposit', $data);
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
     * 拉单延迟1分钟，最大拉单区间30天
     * @return bool
     */
    public function synchronousChildData()
    {
        if (!$data = $this->getSupperOrder($this->config['type'])) {
            return true;
        }
        //orders列表
        $batchOrderData = [];
        //打码量配置
        $auditSetting = \Logic\Set\SystemConfig::getModuleSystemConfig('audit');
        foreach ($data as $key => $val) {
            $user_id = (new GameToken())->getUserId($val['userId']);
            //拉取订单其它后续 逻辑 处理通知
            $orders = [
                'user_id' => $user_id,
                'game' => $val['gameType'] == 'SLOT' ? 'GAME' : $val['gameType'],
                'order_number' => $val['orderNumber'],
                'game_type' => $this->orderDataTypes[$this->game_alias . '-' . $val['gameType']]['type'],
                'type_name' => $this->orderDataTypes[$this->game_alias . '-' . $val['gameType']]['type'],
                'play_id' => $this->orderDataTypes[$this->game_alias . '-' . $val['gameType']]['id'],
                'bet' => bcmul($val['betAmount'], 100, 0),
                'profit' => bcmul($val['profit'], 100, 0),
                'send_money' => bcmul($val['winAmount'], 100, 0),
                'order_time' => $val['betEndTime'],
                'date' => substr($val['betEndTime'], 0, 10),
                'created' => date('Y-m-d H:i:s')
            ];
            $gameAduitSetting = isset($auditSetting[$orders['game']]) && $auditSetting[$orders['game']] ? bcdiv($auditSetting[$orders['game']], 100, 2) : 1;//游戏类型打码量设置，如果不存在则为1
            $orders['dml'] = $orders['bet'] * $gameAduitSetting;
            $batchOrderData[] = $orders;
        }
        $this->addGameToOrdersTable($batchOrderData);

        unset($data, $val, $key, $query);
        return true;
    }

    /**
     * 获取头奖
     * 3-20、取得彩池金额
     * GRAND,MAJOR,MINI 彩池累积金额
     * @return integer
     */
    public function getJackpot()
    {
        return 0;
    }

    public function sign($data)
    {
        if (isset($data['sign'])) {
            unset($data['sign']);
        }
        ksort($data);
        $signString = urldecode(http_build_query($data, '', '&'));
        $iv = substr(strrev($this->config['key']), 0, 16);
        return openssl_encrypt($signString, "AES-256-CBC", $this->config['key'], 0, $iv);
    }

    /**
     * 发送请求
     * @param string $action 请求方法
     * @param array $param 请求参数
     * @param bool $is_order 是否为获取注单
     * @return array|string
     */
    public function requestParam(string $action, array $param, $is_order = false)
    {
        if (is_null($this->config)) {
            $ret = [
                'responseStatus' => false,
                'message'        => 'no api config'
            ];
            GameApi::addElkLog($ret, 'LDAPI');
            return $ret;
        }

        $url = rtrim($is_order ? $this->config['orderUrl'] : $this->config['apiUrl'], '/') . '/api/' . $action;

        $param['agentCode'] = $this->config['cagent'];
        $param['timestamp'] = time();
        $param['gamePlatform'] = $this->config['lobby'];
        $param['sign'] = $this->sign($param);

        $queryString = http_build_query($param, '', '&');

        $re = Curl::commonPost($url, null, $queryString, [], true);
        if ($re['status'] == 200) {
            $re['content'] = json_decode($re['content'], true);
        }
        GameApi::addRequestLog($url, $this->game_type, ['param' => $param], json_encode($re, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $ret = [];
        if ($re['status'] == 200) {
            $ret = $re['content'];
            if (isset($ret['state']) && ($ret['state'] == 0)) {
                $ret['responseStatus'] = true;
            } else {
                $ret['responseStatus'] = false;
            }
        } else {
            $ret['responseStatus'] = false;
            $ret['message'] = $re['content'];
        }
        return $ret;
    }
}

