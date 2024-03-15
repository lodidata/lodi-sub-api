<?php

namespace Logic\GameApi\Game;

use Logic\Define\Cache3thGameKey;
use Logic\GameApi\GameApi;
use Logic\GameApi\GameToken;
use Model\FundsChild;
use Utils\Curl;

/**
 * UG体育
 * Class FC
 * @package Logic\GameApi\Game
 */
class UG extends \Logic\GameApi\Api
{
    /**
     * @var string 订单表
     */
    protected $orderTable = 'game_order_ug';
    protected $langs = [
        'th' => 'th',
        'zh-cn' => 'zh_cn',
        'en-us' => 'en',
        'vn' => 'vi',
        'id' => 'id',
    ];

    //第一步 创建游戏账号
    public function childCreateAccount(string $account, string $password)
    {
        if (!$this->checkStatus()) {
            return false;
        }
        $data = [
            'userId' => $account,
            'loginName' => $account,
            'currencyId' => '119',//PHP
            'agentId' => '0',
            'groupCommission' => 'a',
        ];
        $res = $this->requestParam('/api/transfer/register', $data);
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
                "userId" => $account['account'],
                "returnUrl" => $back_url,
                //'oddsExpression' => '',
                "language" => $this->langs[LANG]?? $this->langs['en-us'], //语言，预设值：en
                //'webType' => 'mobile',	//入口类型，预设值：mobile pc
                //'theme' => 'style',//版面，预设值：style
                //'sportId' => '',//偏好运动类型，预设值：1 (足球)，支援设定的运动有：1 (足球), 2 (篮球), 7 (网球), 11 (板球)
            ];
            $res = $this->requestParam('/api/transfer/getLoginUrl', $data);
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
                    'url' => $res['data'],
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
            'userId' => $account['account'],
        ];
        $res = $this->requestParam('/api/transfer/getBalance', $data);
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
        $account = $this->getGameAccount();
        $fields = [
            'userId' => $account['account'],
        ];
        $res = $this->requestParam('/api/transfer/logout', $fields);
        if ($res['responseStatus']) {
            return true;
        }
        return false;
    }

    /**
     * 登出商户下所有会员
     * @return bool
     */
    public function logoutAllUser()
    {
        $fields = [];
        $res = $this->requestParam('/api/transfer/logoutAllUser', $fields);
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
                'serialNumber' => $data['tradeNo']
            ];
            $res = $this->requestParam('/api/transfer/getTransfer', $params);
            //响应成功 data.status转帐状态 (1:成功,2:失败)
            if ($res['responseStatus'] && $res['code'] == '000000') {
                if($res['data'][0]['status'] == 1){
                    $this->updateGameMoneyError($data, bcmul($res['data'][0]['amount'], 100, 0));
                } else {
                    $this->refundAction($data);
                }
                //不存在
            }elseif(isset($res['code']) && $res['code'] != '000000'){
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
        $apiPassword = $this->aes256CbcEncrypt();
        $fields = [
            'apiPassword' => $apiPassword,
            'userId' => $account['account'],
            'serialNumber' => $tradeNo,
            'amount' => bcdiv($balance, 100, 4),
        ];
        $fields['key'] = $this->transferKey($apiPassword, $fields['userId'], $fields['amount']);
        $res = $this->requestParam('/api/transfer/withdraw', $fields);
        if ($res['responseStatus'] && $res['code'] == '000000') {
            $status = true;
        }else{
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
            'apiPassword' => $this->aes256CbcEncrypt(),
            'userId' => $account['account'],
            'serialNumber' => $tradeNo,
            'amount' => bcdiv($balance, 100, 4),
        ];
        $fields['key'] = $this->transferKey($fields['apiPassword'], $fields['userId'], $fields['amount']);
        $res = $this->requestParam('/api/transfer/deposit', $fields);
        if ($res['responseStatus'] && $res['code'] == '000000') {
            return true;
        }else{
            return false;
        }
    }

    /**
     * 交易验证码
     * @param $apiPassword
     * @param $userId
     * @param $amount
     * @return false|string
     */
    private function transferKey($apiPassword, $userId, $amount){
        $apiPassword = strtolower($apiPassword);
        $userId = strtolower($userId);
        $md5Data = $apiPassword.$userId.$amount;
        $md5 = md5($md5Data);
        return substr($md5, -6);
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
            $user_id = (new GameToken())->getUserId($val['Account']);
            if (!$user_id) {
                continue;
            }
            $val['user_id'] = $user_id;
            unset($val['id'], $val['tid']);
            $batchData[] = $val;

            $orders = [
                'user_id' => $user_id,
                'game' => 'SPORT',
                'order_number' => $val['BetID'],
                'game_type' => 'UG',
                'type_name' => $this->lang->text('UG'),
                'play_id' => 95,
                'bet' => bcmul($val['DeductAmount'], 100, 0),
                'profit' => bcmul($val['Win'], 100, 0),
                'send_money' => bcmul($val['BackAmount'], 100, 0),
                'order_time' => $val['BetDate'],
                'date' => substr($val['BetDate'], 0, 10),
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
                'msg' => 'no api config'
            ];
            GameApi::addElkLog($ret,'UG');
            return $ret;
        }
        $url = rtrim($is_order ? $this->config['orderUrl'] : $this->config['apiUrl'], '/') . $action;

        $param['apiKey'] = $this->config['key'];
        $param['operatorId'] = $this->config['des_key'];

        $re = Curl::post($url, null, $param, null, true);
        if ($re['status'] != 200) {
            $ret['responseStatus'] = false;
            $ret['status'] = $re['status'];
            $ret['msg'] = $re['content'];
            GameApi::addRequestLog($url, $this->game_type, $param, json_encode($re, JSON_UNESCAPED_UNICODE));
        } else {
            $ret = json_decode($re['content'], true);
            if (isset($ret['code']) && $ret['code'] == '000000') {
                $ret['responseStatus'] = true;
            } else {
                $ret['responseStatus'] = false;
            }
            GameApi::addRequestLog($url, $this->game_type, $param, json_encode($ret, JSON_UNESCAPED_UNICODE));
        }
        return $ret;
    }

    private function aes256CbcDecrypt($data)
    {
        $apiKey = $this->config['key'];
        $operatorId = strtolower($this->config['des_key']);
        //商户代码。如果字元数不足 16，前方补零直到满足 16 个字数。反之如果字元数超过 16，取后方算起 16 个字元。使用前须转换为小写。
        $len = strlen($operatorId);
        if ($len < 16) {
            $operatorId = str_pad($operatorId, 16, '0', STR_PAD_LEFT);
        } else {
            $operatorId = substr($operatorId, -16);
        }

        return openssl_decrypt($data, "AES-256-CBC", $apiKey, 0, $operatorId);
    }

    private function aes256CbcEncrypt()
    {
        $apiKey = $this->config['key'];
        $operatorId = strtolower($this->config['des_key']);
        //商户代码。如果字元数不足 16，前方补零直到满足 16 个字数。反之如果字元数超过 16，取后方算起 16 个字元。使用前须转换为小写。
        $len = strlen($operatorId);
        if ($len < 16) {
            $operatorId = str_pad($operatorId, 16, '0', STR_PAD_LEFT);
        } else {
            $operatorId = substr($operatorId, -16);
        }
        $md5Hex = md5(strtolower($apiKey . $operatorId));
        $plainText = $md5Hex . time();

        return openssl_encrypt($plainText, "AES-256-CBC", $apiKey, 0, $operatorId);
    }

    function getJackpot()
    {
        return 0;
    }
}

