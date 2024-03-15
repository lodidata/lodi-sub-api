<?php

namespace Logic\GameApi\Game;

use Logic\Define\Cache3thGameKey;
use Logic\GameApi\GameApi;
use Logic\GameApi\GameToken;
use Model\FundsChild;
use Utils\Curl;
use Model\user as UserModel;

/**
 * FC电子
 * Class FC
 * @package Logic\GameApi\Game
 */
class FC extends \Logic\GameApi\Api
{
    /**
     * @var string 订单表
     */
    protected $orderTable = 'game_order_fc';
    protected $langs = [
        'th' => '4',
        'zh-cn' => '2',
        'en-us' => '1',
        'vn' => '3',
    ];

    //第一步 创建游戏账号
    public function childCreateAccount(string $account, string $password)
    {
        if (!$this->checkStatus()) {
            return false;
        }
        $data = [
            'MemberAccount' => $account,
        ];
        $res = $this->requestParam('AddMember', $data);
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
                "GameID" => $params['kind_id'],
                "MemberAccount" => $account['account'],
                "LanguageID" => $this->langs[LANG]?? $this->langs['en-us'],
                "HomeUrl" => $back_url,
            ];
            $global = \Logic\Set\SystemConfig::getModuleSystemConfig('activity');
            //增加是否开启彩金
            if(isset($global['open_fc_jackpot']) && $global['open_fc_jackpot'] === true){
                $lobby = $this->config['lobby'];
                if(!empty($lobby)){
                    $lobby = json_decode($lobby, true);
                    if(isset($lobby['JackpotStatus'])){
                        $data['JackpotStatus'] = $lobby['JackpotStatus'];
                    }
                }
            }

            $res = $this->requestParam('Login', $data);
            if ($res['responseStatus']) {
                //余额转入第三方
                $result = $this->rollInThird();
                if (!$result['status']) {
                    return [
                        'status' => 886,
                        'message' => 'roll in third error',
                        'url' => ''
                    ];
                }
                return [
                    'status' => 0,
                    'url' => $res['Url'],
                    'message' => 'ok'
                ];
            } else {
                return [
                    'status' => -1,
                    'message' => 'login error',
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
        $res = $this->requestParam('SearchMember', $data);
        if ($res['responseStatus']) {
            return [bcmul($res['Points'], 100, 0), bcmul($res['Points'], 100, 0)];
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
        ];
        $res = $this->requestParam('KickOut', $fields);
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
                'TrsID' => $data['tradeNo']
            ];
            $res = $this->requestParam('GetSingleBill', $params);
            //响应成功
            if ($res['responseStatus']) {
                //status仅回传 1：成功
                if ($res['status'] == 1) {
                    $this->updateGameMoneyError($data, abs(bcmul($res['points'], 100, 0)));
                }
            } elseif (isset($res['Result']) && $res['Result'] == 709) {
                //订单号不存在
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
            'MemberAccount' => $account['account'],
            'Points' => bcdiv($balance, -100, 2), //分为单位提款或存款点数（请以正数至小数后两位） 正数: 存款 负数: 提款
            'TrsID' => $tradeNo,
            'AllOut' => 1
        ];
        $res = $this->requestParam('SetPoints', $data);
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
            'MemberAccount' => $account['account'],
            'Points' => bcdiv($balance, 100, 2), //分为单位提款或存款点数（请以正数至小数后两位） 正数: 存款 负数: 提款
            'TrsID' => $tradeNo,
            'AllOut' => 0
        ];
        $res = $this->requestParam('SetPoints', $data);
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
        $platformTypes = [
            2 => ['id' => 93, 'game' => 'GAME', 'type' => 'FC'],
            1 => ['id' => 94, 'game' => 'BY', 'type' => 'FCBY'],
            7 => ['id' => 93, 'game' => 'GAME', 'type' => 'FC'],
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
            $user_id = (new GameToken())->getUserId($val['account']);
            $val['user_id'] = $user_id ?: 0;
            unset($val['id'], $val['tid']);

            if(!isset($platformTypes[$val['gametype']])){
                $val['gametype'] = 2;
            }

            $batchData[] = $val;

                //拉取订单其它后续 逻辑 处理通知
            $orders = [
                'user_id' => $val['user_id'],
                'game' => $platformTypes[$val['gametype']]['game'],
                'order_number' => $val['recordID'],
                'game_type' => $platformTypes[$val['gametype']]['type'],
                'type_name' => $this->lang->text($platformTypes[$val['gametype']]['type']),
                'play_id' => $platformTypes[$val['gametype']]['id'],
                'bet' => bcmul($val['bet'], 100, 0),
                'profit' => bcmul($val['prize']-$val['bet']+$val['jppoints'], 100, 0),
                'send_money' => bcmul($val['prize']+$val['jppoints'], 100, 0),
                'order_time' => $val['bdate'],
                'date' => substr($val['bdate'], 0, 10),
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
     * 获取头奖
     * 3-20、取得彩池金额
     * GRAND,MAJOR,MINI 彩池累积金额
     * @return integer
     */
    public function getJackpot()
    {
        $res = $this->requestParam('GetJackpotPool', []);
        if ($res['responseStatus'] && $res['Result'] == 0) {
            return $res['DATA'][0]['GRAND'];
        }
        return 0;
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
                'message' => 'no api config'
            ];
            GameApi::addElkLog($ret,'FC');
            return $ret;
        }

        $url = rtrim($is_order ? $this->config['orderUrl'] : $this->config['apiUrl'], '/') . '/' . $action;
        $headers = array(
            "Content-Type: application/x-www-form-urlencoded"
        );

        if(empty($param)){
            $json_param = '{}';
        }else{
            $json_param = json_encode($param, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $params = [
            'AgentCode' => $this->config['cagent'],
            'Currency' => $this->config['currency'],
            'Params' => $this->AESencode($json_param),
            'Sign' => md5($json_param)
        ];
        $queryString = http_build_query($params, '', '&');

        $re = Curl::commonPost($url, null, $queryString, $headers, true);
        if ($re['status'] == 200) {
            $re['content'] = json_decode($re['content'], true);
        }
        GameApi::addRequestLog($url, $this->game_type, ['param' => $param, 'params' => $params], json_encode($re, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $ret = [];
        if ($re['status'] == 200) {
            $ret = $re['content'];
            if (isset($ret['Result']) && ($ret['Result'] == 0 || $ret['Result'] == 502)) {
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

    //AES 加密 ECB 模式
    public function AESencode($_values)
    {
        Try {
            $data = openssl_encrypt($_values, 'AES-128-ECB', $this->config['key'], OPENSSL_RAW_DATA);
            $data = base64_encode($data);
        } Catch (\Exception $e) {
        }
        return $data;
    }

    //AES 解密 ECB 模式
    public function AESdecode($_values)
    {
        $data = null;
        Try {
            $data = openssl_decrypt(base64_decode($_values), 'AES-128-ECB', $this->config['key'], OPENSSL_RAW_DATA);
        } Catch (\Exception $e) {
        }
        return $data;
    }
}

