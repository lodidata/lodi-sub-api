<?php

namespace Logic\GameApi\Game;

use Logic\Define\Cache3thGameKey;
use Logic\GameApi\GameApi;
use Logic\GameApi\GameToken;
use Model\FundsChild;
use Utils\Curl;
use Model\user as UserModel;

/**
 * 泛亚电竞
 * Class EP
 * @package Logic\GameApi\Game
 */
class AVIA extends \Logic\GameApi\Api
{
    /**
     * @var string 订单表
     */
    protected $orderTable = 'game_order_avia';
    protected $langs = [
        'th' => 'TH',
        'zh-cn' => 'CHN',
        'en-us' => 'ENG',
        'pt' => 'PT',
        'vn' => 'VN',
        'jp' => 'JP',
        'ko' => 'KR',
        'es-mx' => 'ES',
        'fr' => 'FR',
        'de' => 'DE',
        'it' => 'IT',
        'ru' => 'RU',
        'id' => 'ID'
    ];
    /**
     * @var array 错误代码
     */
    protected $message = [
        'NOUSER' => '用户不存在',
        'BADNAME' => '用户名不符合规则,用户名格式为数字+字母+下划线的组合，2~16位',
        'BADPASSWORD' => '密码不符合规则，密码长度位5~16位',
        'EXISTSUSER' => '用户名已经存在',
        'BADMONEY' => '金额错误,金额支持两位小数。',
        'NOORDER' => '订单号错误（不符合规则或者不存在）',
        'EXISTSORDER' => '订单号已经存在，转账订单号为全局唯一',
        'TRANSFER_NO_ACTION' => '未指定转账动作，转账动作必须为 IN 或者 OUT',
        'IP' => 'IP未授权',
        'USERLOCK' => '用户被锁定，禁止登录',
        'NOBALANCE' => '余额不足',
        'NOCREDIT' => '平台额度不足（适用于买分商户)',
        'Authorization' => 'API密钥错误',
        'Faild' => '发生错误',
        'DOMAIN' => '未配置域名（请与客服联系）',
        'CONTENT' => '内容错误（提交的参数不符合规则）',
        'Sign' => '签名错误（适用于单一钱包的通信错误提示）',
        'NOSUPPORT' => '不支持该操作',
        'TIMEOUT' => '超时请求',
        'STATUS' => '状态错误(商户被冻结）',
        'CONFIGERROR' => '商户信息配置错误（请联系客服处理）',
        'DATEEROOR' => '查询日期错误,日期超过了1天或者结束时间大于开始时间',
        'ORDER_NOTFOUND' => '查询使用的订单号不存在',
        'PROCCESSING' => '订单正在处理中'
    ];

    //第一步 创建游戏账号
    public function childCreateAccount(string $account, string $password)
    {
        if (!$this->checkStatus()) {
            return false;
        }
        $data = [
            'UserName' => $account,
            'Password' => substr(md5($password), 8, 16),//5~16位
            'Currency' => $this->config['currency']
        ];
        $res = $this->requestParam('/api/user/register', $data);
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
                //"CateID" => $params['kind_id'],
                "CateID" => '',//进大厅
                "UserName" => $account['account'],
                "MatchID" => '',
            ];
            $res = $this->requestParam('/api/user/login', $data);
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
                    'url' => $res['info']['Url'],
                    'message' => 'ok'
                ];
            } else {
                return [
                    'status' => -1,
                    'message' => $res['msg'],
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
            'UserName' => $account['account'],
        ];
        $res = $this->requestParam('/api/user/balance', $data);
        if ($res['responseStatus']) {
            return [bcmul($res['info']['Money'], 100, 0), bcmul($res['info']['Money'], 100, 0)];
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
            'UserName' => $account['account'],
        ];
        $res = $this->requestParam('/api/user/logout', $fields);
        if ($res['responseStatus']) {
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
        //转入金额超时则在此查询
        if (is_array($data) && $data['balance']) {
            $params = [
                'ID' => $data['tradeNo']
            ];
            $res = $this->requestParam('/api/user/transferinfo', $params);
            //响应成功
            if ($res['responseStatus']) {
                if ($res['info']['Status'] == 'Finish') {
                    $this->updateGameMoneyError($data, bcmul($res['info']['Money'], 100, 0));
                } elseif ($res['info']['Status'] == 'Faild') {
                    $this->refundAction($data);
                }
            } elseif (isset($res['success']) && $res['success'] === 0) {
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
     * @param int $balance
     * @param string $tradeNo
     * @param int $type 转账类型;0:存款到API 1:从API取款
     * @return bool
     */
    public function transfer(int $balance, string $tradeNo, $type = 0)
    {
        $account = $this->getGameAccount();
        $data = [
            'UserName' => $account['account'],
            'Money' => bcdiv($balance, 100, 2), //转账金额，最多支持2位小数，不可为负数
            'Type' => $type > 0 ? 'OUT' : 'IN', //转账类型;转入：IN / 转出：OUT
            'ID' => $tradeNo,
            "Currency" => $this->config['currency'],
        ];
        //转账
        $res = $this->requestParam('/api/user/transfer', $data);
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
            $user_id = (new GameToken())->getUserId($val['UserName']);
            if (!$user_id) {
                continue;
            }
            $val['user_id'] = $user_id;
            unset($val['id'], $val['tid']);
            $batchData[] = $val;

            $orders = [
                'user_id' => $user_id,
                'game' => 'ESPORTS',
                'order_number' => $val['OrderID'],
                'game_type' => 'AVIA',
                'type_name' => $this->lang->text('AVIA'),
                'play_id' => 96,
                'bet' => bcmul($val['BetAmount'], 100, 0),
                'profit' => bcmul($val['Money'], 100, 0),
                'send_money' => bcmul($val['BetAmount']+$val['Money'], 100, 0),
                'order_time' => $val['RewardAt'],
                'date' => substr($val['RewardAt'], 0, 10),
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
     * 发送请求
     * @param string $action 请求方法
     * @param array $param 请求参数
     * @param bool $is_order 是否为获取注单
     * @return array|string
     */
    public function requestParam(string $action, array $param, $is_order = false)
    {
        if(is_null($this->config)){
            $ret = [
                'responseStatus' => false,
                'msg' => ['message' => 'no api config']
            ];
            GameApi::addElkLog($ret,'AVIA');
            return $ret;
        }
        $url = rtrim($is_order ? $this->config['orderUrl'] : $this->config['apiUrl'], '/') . $action;
        $headers = array(
            "Authorization: " . $this->config['key'],
            "Content-Language: " . $this->langs[LANG]?? $this->langs['en-us'],
            "Content-Type: application/x-www-form-urlencoded"
        );

        $queryString = http_build_query($param, '', '&');

        $re = Curl::commonPost($url, null, $queryString, $headers, true);
        if ($re['status'] != 200) {
            $ret['responseStatus'] = false;
            $ret['status'] = $re['status'];
            $ret['msg'] = $re['content'];
            GameApi::addRequestLog($url, $this->game_type, $param, json_encode($re, JSON_UNESCAPED_UNICODE));
        } else {
            $ret = json_decode($re['content'], true);
            if (isset($ret['success']) && $ret['success'] == '1') {
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

