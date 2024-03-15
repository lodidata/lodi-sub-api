<?php

namespace Logic\GameApi\Game;

use Logic\Define\Cache3thGameKey;
use Logic\GameApi\GameApi;
use Logic\GameApi\GameToken;
use Model\FundsChild;
use Utils\Curl;
use Model\user as UserModel;

class STG extends \Logic\GameApi\Api
{
    protected $orderTable = 'game_order_stg';

    protected $langs = [
        'zh-cn' => 'zh_hans',
        'ko'    => 'ko',
        'en-us' => 'en',
        'th'    => 'th',
        'vi'    => 'vi',
        'jp'    => 'ja',
        'bg'    => 'bg',
        'cz'    => 'cz',
        'de'    => 'de',
        'el'    => 'el',
        'tr'    => 'tr',
        'es'    => 'es',
        'fi'    => 'fi',
        'fr'    => 'fr',
        'hu'    => 'hu',
        'it'    => 'it',
        'nl'    => 'nl',
        'no'    => 'no',
        'pl'    => 'pl',
        'pt'    => 'pt',
        'pt-br' => 'pt',
        'ro'    => 'ro',
        'ru'    => 'ru',
        'sk'    => 'sk',
        'sv'    => 'sv',
        'da'    => 'da',
        'ka'    => 'ka',
        'lv'    => 'lv',
        'uk'    => 'uk',
        'et'    => 'et',
    ];

    /**
     * 用户ID标识
     */
    private function ClientId()
    {
        $tid = $this->ci->get('settings')['app']['tid'];
        return hexdec($tid . $this->uid);
    }

    //第一步 创建游戏账号
    public function childCreateAccount(string $account, string $password)
    {
       return true;
    }

    //进入游戏 并创建玩家账户
    public function getJumpUrl(array $params = [])
    {
        $account = $this->getGameAccount();
        if (!$account) {
            return [
                'status' => 133,
                'message' => $this->lang->text(133),
                'url' => ''
            ];
        }

        $lobby = json_decode($this->config['lobby'], true);

        $back_url = $this->ci->get('settings')['website']['game_home_url'] ?? $_SERVER['HTTP_HOST'];
            return [
                'status' => 0,
                'data' => [
                    'PartnerId' => $this->config['cagent'],
                    'Token' => bin2hex($account['account']),
                    'ClientId' => $this->ClientId(),
                    'ClientName' => $account['account'],
                    'SPORTHOSTNAME' => $lobby['SPORTHOSTNAME'],
                    'timeZone' => $lobby['timeZone'],
                    'lang' => $this->langs[LANG]?? $this->langs['en-us'],
                    'domain' => $back_url,
                    'sportPartner' => $lobby['sportPartner'],
                ],
                'message' => 'ok'
            ];
    }

    /**
     * 检测玩家的上线状态与余额
     * @return array
     */
    public function getThirdWalletBalance()
    {
        $field = [
            'PartnerId' => $this->config['cagent'],
            'TimeStamp' => time(),
            "ClientId" => $this->ClientId(),
        ];
        $field['Signature'] = $this->Signature('GetBalance', $field, ['PartnerId', 'TimeStamp', 'ClientId'], $this->config['key']);
        $res = $this->requestParam('GetBalance', $field);

        if ($res['responseStatus'] && $res['ResponseCode'] == 0) {
            return [bcmul($res['UnusedBalance'], 100, 0), bcmul($res['UnusedBalance'], 100, 0)];
        }
        $balance = FundsChild::where('pid', $this->wid)->where('game_type', $this->game_alias)->value('balance');
        return [0, $balance];
    }


    /**
     * 资金从STG账户转出
     * @param int $balance
     * @param string $tradeNo
     * @return array|bool
     */
    public function rollOutChildThird(int $balance, string $tradeNo)
    {
        $field = [
            'PartnerId' => $this->config['cagent'],
            'TimeStamp' => time(),
            'TransactionId' => $tradeNo,
            'ClientId' => $this->ClientId(),
            'Amount' => bcdiv($balance, 100, 2),
        ];
        $field['Signature'] = $this->Signature('Withdrawal', $field, ['PartnerId', 'TimeStamp','TransactionId', 'ClientId'], $this->config['key']);
        $res = $this->requestParam('Withdrawal', $field);
        if ($res['responseStatus'] && $res['ResponseCode'] == 0) {
            return [true, $balance];
        }
        return [false, $balance];
    }


    /**
     * 资金转入到STG账户
     * @param int $balance
     * @param string $tradeNo
     * @return bool
     */
    public function rollInChildThird(int $balance, string $tradeNo)
    {
        $account = $this->getGameAccount();
        $field = [
            'PartnerId' => $this->config['cagent'],
            'TimeStamp' => time(),
            'TransactionId' => $tradeNo,
            'ClientId' => $this->ClientId(),
            'Amount' => bcdiv($balance, 100, 2),
            'CurrencyId' => $this->config['currency'],
            'ClientName' => $account['account'],
            'UnUsedAmount' => bcdiv($balance, 100, 2),
        ];
        $field['Signature'] = $this->Signature('Deposit', $field, ['PartnerId', 'TimeStamp','TransactionId', 'ClientId'], $this->config['key']);
        $res = $this->requestParam('Deposit', $field);
        if ($res['responseStatus'] && $res['ResponseCode'] == 0) {
            return true;
        }
        return false;
    }


    /**
     * 同步订单 实时拉单，没有延迟
     * @return bool
     */
    public function synchronousChildData()
    {
        if (!$data = $this->getSupperOrder($this->game_type)) {
            return true;
        }

        //注单列表
        $batchData = [];
        //orders列表
        $batchOrderData = [];
        //打码量配置
        $auditSetting = \Logic\Set\SystemConfig::getModuleSystemConfig('audit');
        foreach ($data as $key => $val) {
            unset($val['id'], $val['tid']);
            $batchData[] = $val;

            $orders = [
                'user_id' => $val['user_id'],
                'game' => 'SPORT',
                'order_number' => $val['OrderNumber'],
                'game_type' => 'STG',
                'type_name' => $this->lang->text('STG'),
                'play_id' => 115,
                'bet' => bcmul($val['Amount'], 100, 0),
                'profit' => bcmul($val['profit'], 100 ,0),
                'send_money' => bcmul($val['WinAmount'], 100 ,0),
                'order_time' => $val['DateUpdated'],
                'date' => substr($val['DateUpdated'], 0, 10),
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
     * 强制登出玩家正在游玩的STG游戏
     * @return array|bool
     */
    public function quitChildGame()
    {
        return true;
    }

    public function checkMoney($data = null)
    {
        if (is_array($data) && $data['balance']) {
            $account = $this->getGameAccount();
            $params = [
                'PartnerId' => $this->config['cagent'],
                'TimeStamp' => time(),
                "TransactionId" => $data['tradeNo'],
            ];
            $params['Signature'] = $this->Signature('CheckTransaction', $params, ['PartnerId', 'TimeStamp','TransactionId'], $this->config['key']);
            $res = $this->requestParam('CheckTransaction', $params);
            //不存在
            if ($res['responseStatus'] && $res['ResponseCode'] == 64 ) {
                // 转入失败 退钱
                $this->refundAction($data);
            }

            if (!$res['responseStatus']) {
                return false;
            }
            if ($res['responseStatus'] && $res['ResponseCode'] == 0) {
                // 转入成功
                $this->updateGameMoneyError($data, $data['balance']);
            } else {
                // 转入失败 退钱
                $this->refundAction($data);
            }
        }
    }



    /**
     * 发送请求
     * @param $action
     * @param array $param 请求参数
     * @return array|string
     */
    public function requestParam($action, array $param)
    {
        if(is_null($this->config)){
            $ret = [
                'responseStatus' => false,
                'message' => 'no api config'
            ];
            GameApi::addElkLog($ret,'STG');
            return $ret;
        }
       $url = $this->config['apiUrl'] . $action;
        $re = Curl::post($url, null, $param, null, true);

        if (isset($re['status']) && $re['status'] == 200) {
            $ret = json_decode($re['content'], true);
            $ret['responseStatus'] = true;
            $ret['networkStatus'] = 200;
        } else {
            $ret['networkStatus'] = $re['status'];
            $ret['message'] = $re['content'];
            $ret['responseStatus'] = false;
        }
        GameApi::addRequestLog($url, $this->game_type, $param, json_encode($ret, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $ret;
    }


    /**
     * 生成sign
     * @param string $method 请求方法
     * @param array $params 参数
     * @param array $md5Keys 加密字段
     * @param string $key 密钥
     * @return string
     */
    public function Signature($method,$params, $md5Keys, $key)
    {
        $str = 'method'.$method;
        foreach ($md5Keys as $k){
            $str .= $k . $params[$k];
        }
        $sign = md5($str . $key);
        return $sign;
    }

    /**
     * 错误信息
     * @param $code
     * @return mixed|string
     */
    public function getErrorMessage($code)
    {
        $message = [
            0 => 'Success',
            20 => 'CurrencyNotExists',
            22 => 'ClientNotFound (ClientId does not exist)',
            37 => 'WrongToken',
            46 => 'TransactionAlreadyExists',
            64 => 'TransactionNotExists',
            70 => 'PartnerNotFound',
            71 => 'LowBalance',
            500 => 'InternalServerError',
            1013 => 'InvalidInputParameters',
            1016 => 'InvalidSignature',
        ];

        return $message[$code] ?? 'unknown';
    }

    function getJackpot()
    {
        return 0;
    }
}
