<?php

namespace Logic\GameApi\Game;

use Logic\Define\Cache3thGameKey;
use Logic\Define\Lang;
use Logic\GameApi\GameApi;
use Logic\GameApi\GameToken;
use Model\FundsChild;
use Utils\Curl;

/**
 * BTI体育
 * Class FC
 * @package Logic\GameApi\Game
 */
class BTI extends \Logic\GameApi\Api
{
    /**
     * @var string 订单表
     */
    protected $orderTable = 'game_order_bti';
    protected $langs = [
        'es-mx' => 'es',
        'en-us' => 'en',
    ];

    protected $country = [
        'zh-cn' => 'CN',
        'en-us' => 'US',
        'vn' => 'VN',
        'th' => 'TH',
        'es-mx' => 'MXN',
    ];


    //第一步 创建游戏账号
    public function childCreateAccount(string $account, string $password)
    {
        $data = [
            "AgentUserName" => $this->config['cagent'],
            "AgentPassword" => $this->config['key'],
            "MerchantCustomerCode" => $account,
            "LoginName" => $account,
            "CurrencyCode" => $this->config['currency'],
            "CountryCode" => $this->country[LANG] ?? $this->country['en-us'],
            "City" => ($this->country[LANG] ?? $this->country['en-us']) . ' city',
            "FirstName" => substr($account, 0, 6),
            "LastName" => substr($account, 6),
            "Group1ID" => 0,
            "CustomerMoreInfo" => '',
            "CustomerDefaultLanguage" => $this->langs[LANG] ?? $this->langs['en-us'],
            "DomainID" => '',
            "DateOfBirth" => '',
        ];
        $res = $this->requestParam('/CreateUserNew', $data);
        if ($res['responseStatus']) {
            return true;
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
        $data = [
            'AgentUserName' => $this->config['cagent'],
            'AgentPassword' => $this->config['key'],
            'MerchantCustomerCode' => $account['account'],
        ];
        $res = $this->requestParam('/GetCustomerAuthToken', $data);
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
                'url' => $this->config['loginUrl'] . '/' . ($this->langs[LANG] ?? $this->langs['en-us']) . '/sports?operatorToken=' . $res['AuthToken'],
                'message' => 'ok'
            ];
        } else {
            return [
                'status' => -1,
                'message' => $res['ErrorMsg'] ?? 'login error',
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
            'AgentUserName' => $this->config['cagent'],
            'AgentPassword' => $this->config['key'],
            'MerchantCustomerCode' => $account['account'],
        ];
        $res = $this->requestParam('/GetCustomerByMerchantCode', $data);
        if ($res['responseStatus'] && $res['ErrorCode'] == 'NoError') {
            return [bcmul($res['Balance'], 100, 0), bcmul($res['Balance'], 100, 0)];
        }

        $balance = FundsChild::where('pid', $this->wid)->where('game_type', $this->game_alias)->value('balance');
        return [0, $balance];
    }

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
            $params = [
                'AgentUserName' => $this->config['cagent'],
                'AgentPassword' => $this->config['password'],
                'RefTransactionCode' => $data['tradeNo']
            ];
            $res = $this->requestParam('/CheckTransaction', $params);
            //响应成功 status转帐状态 (NoError:成功,TransactionCodeNotFound:不存在)
            if (isset($res['responseStatus']) && $res['responseStatus']) {
                if ($res['ErrorCode'] == "NoError") {
                    $this->updateGameMoneyError($data, bcmul($res['Balance'], 100, 0));
                } elseif ($res['ErrorCode'] == "TransactionCodeNotFound") {
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
        $account = $this->getGameAccount();
        $fields = [
            'AgentUserName' => $this->config['cagent'],
            'AgentPassword' => $this->config['key'],
            'MerchantCustomerCode' => $account['account'],
            'RefTransactionCode' => $tradeNo,
            'Amount' => bcdiv($balance, 100, 2),
        ];
        $res = $this->requestParam('/TransferFromWHL', $fields);
        if ($res['responseStatus']) {
            $status = true;
        } else {
            $status = false;
        }
        return [$status, $balance];

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
        $fields = [
            'AgentUserName' => $this->config['cagent'],
            'AgentPassword' => $this->config['key'],
            'MerchantCustomerCode' => $account['account'],
            'RefTransactionCode' => $tradeNo,
            'Amount' => bcdiv($balance, 100, 2),
            'BonusCode' => ''
        ];
        $res = $this->requestParam('/TransferToWHL', $fields);
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
            $user_id = (new GameToken())->getUserId($val['MerchantCustomerID']);
            if (!$user_id) {
                continue;
            }
            $val['user_id'] = $user_id;
            unset($val['id'], $val['tid']);
            $batchData[] = $val;

            $orders = [
                'user_id' => $user_id,
                'game' => 'SPORT',
                'order_number' => $val['PurchaseID'],
                'game_type' => 'BTI',
                'type_name' => $this->lang->text('BTI'),
                'play_id' => 135,
                'bet' => $val['TotalStake'],
                'profit' => $val['PL'],
                'send_money' => $val['ReturnAmount'],
                'order_time' => $val['CreationDate'],
                'date' => substr($val['CreationDate'], 0, 10),
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
     * @param bool $is_post 是否为POST
     * @return array|string
     */
    public function requestParam(string $action, array $param, $is_post = true)
    {
        if(is_null($this->config)){
            $ret = [
                'responseStatus' => false,
                'ErrorMsg' => 'no api config'
            ];
            GameApi::addElkLog($ret,'BTI');
            return $ret;
        }
        $url = rtrim($this->config['apiUrl'], '/') . $action;

        $headers = array(
            'Content-Type: application/json'
        );
        if ($is_post) {
            $re = Curl::commonPost($url, null, json_encode($param), $headers, true);
        } else {
            $queryString = http_build_query($param, '', '&');
            if ($queryString) {
                $url .= '?' . $queryString;
            }
            $re = Curl::get($url);
        }
        if ($re['status'] != 200) {
            $ret['responseStatus'] = false;
            $ret['networkStatus'] = $re['status'];
            $ret['ErrorMsg'] = json_encode($this->parseXML($re['content']));
            GameApi::addRequestLog($url, $this->game_type, $param, json_encode($re, JSON_UNESCAPED_UNICODE));
        } else {
            $ret = $this->parseXML($re['content']);
            $ret['networkStatus'] = 200;
            $ret['responseStatus'] = true;
            GameApi::addRequestLog($url, $this->game_type, $param, json_encode($ret, JSON_UNESCAPED_UNICODE));
        }

        return $ret;
    }

    function getJackpot()
    {
        return 0;
    }
}

