<?php

namespace Logic\GameApi\Game;

use Logic\Define\Cache3thGameKey;
use Logic\GameApi\GameApi;
use Logic\GameApi\GameToken;
use Model\FundsChild;
use Utils\Curl;
use Model\user as UserModel;

/**
 * UG体育
 * Class FC
 * @package Logic\GameApi\Game
 */
class UGold extends \Logic\GameApi\Api
{
    /**
     * @var string 订单表
     */
    protected $orderTable = 'game_order_ug';
    protected $langs = [
        'th' => 'TH',
        'zh-cn' => 'CH',
        'en-us' => 'EN',
    ];

    //第一步 创建游戏账号
    public function childCreateAccount(string $account, string $password)
    {
        if (!$this->checkStatus()) {
            return false;
        }
        $data = [
            'MemberAccount' => $account,
            'NickName' => $account,
            'Currency' => $this->config['currency']
        ];
        $res = $this->requestParam('Register', $data);
        if ($res['responseStatus']) {
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

        try {
            $back_url = $this->ci->get('settings')['website']['game_back_url'] ?? $_SERVER['HTTP_HOST'];
            $data = [
                "GameID" => 1,//进大厅
                "MemberAccount" => $account['account'],
                "Language" => $this->langs[LANG]?? $this->langs['en-us'],
                "HostUrl" => '',
                'WebType' => 'Smart',
                'PageStyle' => 'SP1',
                'LoginIP' => '',
            ];
            $res = $this->requestParam('Login', $data);
            if ($res['responseStatus']) {
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
                    'url' => $res['result'],
                    'message' => 'ok'
                ];
            } else {
                return [
                    'status' => -1,
                    'message' => $res['errtext'],
                    'url' => ''
                ];
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
            'MemberAccount' => $account['account'],
        ];
        $res = $this->requestParam('GetBalance', $data);
        if ($res['responseStatus']) {
            return [bcmul($res['result']['Balance'], 100, 0), bcmul($res['result']['Balance'], 100, 0)];
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
            'MemberAccount' => $account['account'],
            'GameID' => 1
        ];
        $res = $this->requestParam('Logout', $fields);
        if ($res['responseStatus']) {
            return true;
        }
        return false;
    }

    /**
     * 检测是否有转入转出失败的记录
     */
    public function checkMoney($data = null)
    {
        //转入金额超时则在此查询
        if (is_array($data) && $data['balance']) {
            $params = [
                'SerialNumber' => $data['tradeNo']
            ];
            $res = $this->requestParam('CheckTransfer', $params);
            //响应成功
            if ($res['responseStatus']) {
                if ($res['errcode'] == '000000') {
                    $this->updateGameMoneyError($data);
                } else {
                    $this->refundAction($data);
                }
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
        $res = $this->transfer($balance, $tradeNo, 1);
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
        return $this->transfer($balance, $tradeNo, 0);
    }

    /**
     * 转账
     * 注:收到返回结果, 不论成功或者失败结果, 请检查转账状态.
     * 注意:Amount必须是4位小数
     * @param int $balance
     * @param string $tradeNo
     * @param int $type 转账类型;0:存款到API 1:从API取款
     * @return bool
     */
    public function transfer(int $balance, string $tradeNo, $type = 0)
    {
        $account = $this->getGameAccount();
        $data = [
            'MemberAccount' => $account['account'],
            'Amount' => bcdiv($balance, 100, 4), //分为单位提款或存款点数（请以正数至小数后四位） 正数: 存款 负数: 提款
            'TransferType' => $type, //转账类型;0:存款到API 1:从API取款
            'SerialNumber' => $tradeNo,
        ];
        //未加密 MD5 前 必须全部小字母, 取后6位
        $data['key'] = substr(md5(strtolower($this->config['key'] . $data['MemberAccount'] . $data['Amount'])), -6);
        //转账
        $res = $this->requestParam('Transfer', $data);
        //检查状态
        $res2 = $this->requestParam('CheckTransfer', ['SerialNumber' => $tradeNo]);
        if ($res2['responseStatus'] && $res2['errcode'] == '000000') {
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
        foreach ($data as $key => $val) {
            $query = \DB::table($this->orderTable);
            try {
                $user_id = (new GameToken())->getUserId($val['Account']);
                if (!$user_id) {
                    throw new \Exception('用户不存在' . $val['Account']);
                }
                $val['user_id'] = $user_id;
                unset($val['id'], $val['tid']);

                $is_insert = $query->insert($val);
                if ($is_insert) {
                    //拉取订单其它后续 逻辑 处理通知
                    \Utils\MQServer::send(GameApi::$synchronousOrderCallback, [
                        'user_id' => $val['user_id'],
                        'game' => 'SPORT',
                        'order_number' => $val['BetID'],
                        'game_type' => 'UG',
                        'type_name' => $this->lang->text('UG'),
                        'game_id' => 95,
                        'server_id' => 0,
                        'account' => $val['Account'],
                        'bet' => bcmul($val['BetAmount'], 100, 0),
                        'profit' => bcmul($val['Win'], 100, 0),
                        'date' => $val['BetDate'],
                    ]);
                }
            } catch (\Exception $e) {
                if ($query->where('BetID', $val['BetID'])->count()) {
                    continue;
                }
                $tmp_err = [
                    'game_type' => $this->config['type'],
                    'order_number' => $val['BetID'],
                    'json' => json_encode($val, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'error' => $e->getMessage(),
                ];
                \DB::table('game_order_error')->insert($tmp_err);
                GameApi::addElkLog(['code' => $e->getCode(), 'message' => $e->getMessage()], $this->config['type']);
            }
        }
        unset($data, $key, $val, $query);
        return true;
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
        $url = rtrim($is_order ? $this->config['orderUrl'] : $this->config['apiUrl'], '/') . '/ThirdAPI.asmx/' . $action;
        $headers = array(
            "Content-Type: application/x-www-form-urlencoded"
        );

        $param['APIPassword'] = $this->config['key'];

        $queryString = http_build_query($param, '', '&');

        $re = Curl::commonPost($url, null, $queryString, $headers, true);
        if ($re['status'] != 200) {
            $ret['responseStatus'] = false;
            $ret['status'] = $re['status'];
            $ret['errtext'] = $re['content'];
            GameApi::addRequestLog($url, $this->game_type, $param, json_encode($re, JSON_UNESCAPED_UNICODE));
        } else {
            $ret = $this->parseXML($re['content']);
            if (isset($ret['errcode']) && $ret['errcode'] == '000000') {
                $ret['responseStatus'] = true;
            } else {
                $ret['responseStatus'] = false;
            }
            GameApi::addRequestLog($url, $this->game_type, $param, json_encode($ret, JSON_UNESCAPED_UNICODE));
        }
        return $ret;
    }

}

