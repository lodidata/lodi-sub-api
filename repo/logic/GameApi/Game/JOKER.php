<?php

namespace Logic\GameApi\Game;

use Logic\Define\Cache3thGameKey;
use Logic\GameApi\GameApi;
use Logic\GameApi\GameToken;
use Model\FundsChild;
use Utils\Curl;
use Model\user as UserModel;

class JOKER extends \Logic\GameApi\Api
{
    protected $url;
    protected $orderTable = 'game_order_joker';
    protected $langs = [
        'th' => 'th',
        'zh-cn' => 'zh',
        'en-us' => 'en',
        'es-mx' => 'pt',
        'vn' => 'vn',
    ];

    //第一步 创建游戏账号
    public function childCreateAccount(string $account, string $password)
    {
        $data = [
            'Method' => 'CU',
            'Username' => $account,
        ];
        $res = $this->requestParam($data);
        if (isset($res['Status']) && $res['Status'] == 'OK') {
            //设置密码
            $data = [
                'Method' => 'SP',
                'Username' => $account,
                'Password' => $password
            ];
            $res2 = $this->requestParam($data);
            if (isset($res2['Status']) && $res2['Status'] == 'OK') {
                return true;
            }
        }
        return false;
    }


    //进入游戏
    //5.2.玩游戏
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

        try {
            $data = [
                'Method' => 'PLAY',
                'Username' => $account['account'],
            ];

            $res2 = $this->requestParam($data);

            if (isset($res2['Token'])) {
                $back_url = $this->ci->get('settings')['website']['game_back_url'] ?? $_SERVER['HTTP_HOST'];
                $lang = $this->langs[LANG]?? $this->langs['en-us'];
                $game_url = $this->config['loginUrl'] . "?token=" . $res2['Token'] . "&game=" . $params['kind_id'] . "&mobile=true&lang={$lang}&redirectUrl=" . $back_url;
                //余额转入第三方
                $result = $this->rollInThird();
                if (!$result['status']) {
                    return [
                        'status' => 886,
                        'message' => $result['msg'],
                        'url' => ''
                    ];
                }

                $result = [
                    'status' => 0,
                    'url' => $game_url,
                    'message' => 'ok'
                ];
            } else {
                $result = [
                    'status' => -1,
                    'message' => $res2['Message']
                ];
            }
            return $result;


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
     * 5.3. 获取信用
     * API 返回玩家的当前信用余额
     * @return array
     */
    public function getThirdWalletBalance()
    {
        $account = $this->getGameAccount();
        $data = [
            'Method' => 'GC',
            'Username' => $account['account'],
        ];
        $res = $this->requestParam($data);
        if (isset($res['Credit'])) {
            return [bcmul($res['Credit'], 100, 0), bcmul($res['Credit'], 100, 0)];
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
            '59' => ['id' => 59, 'game' => 'GAME', 'type' => 'JOKER'],
            '60' => ['id' => 60, 'game' => 'BY', 'type' => 'JOKERBY'],
            '61' => ['id' => 61, 'game' => 'LIVE', 'type' => 'JOKERLIVE'],
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
            $user_id = (new GameToken())->getUserId(strtolower($val['Username']));
            if(!$user_id) continue;
            $val['user_id'] = $user_id;
            unset($val['tid'],$val['id']);
            $batchData[] = $val;

            $orders = [
                'user_id' => $val['user_id'],
                'game' => $platformTypes[$val['game_id']]['game'],
                'order_number' => $val['OCode'],
                'game_type' => $platformTypes[$val['game_id']]['type'],
                'type_name' => $this->lang->text($platformTypes[$val['game_id']]['type']),
                'play_id' => $platformTypes[$val['game_id']]['id'],
                'bet' => $val['betAmount'],
                'profit' => $val['income'],
                'send_money' => $val['winAmount'],
                'date' => $val['gameDate'],
                'order_time' => $val['gameDate'],
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
     * 登出
     * @return array|bool
     */
    public function quitChildGame()
    {
        return true;
    }

    public function checkMoney($data = null)
    {
        //确认转账
        $param = [
            'Method' => 'TCH',
        ];
        //转入金额超时则在此查询
        if (is_array($data) && $data['balance']) {
            // 查询玩家上下分订单
            $param['RequestID'] = $this->config['cagent'] . $data['tradeNo'];
            $res = $this->requestParam($param, true);
            //响应成功
            if (isset($res['status']) && $res['status'] == 200) {
                $res['content'] = json_decode($res['content'], true);
                $this->updateGameMoneyError($data, abs(bcmul($res['content']['Amount'], 100, 0)));
            }
            //requestId 不存在
            if (isset($res['status']) && $res['status'] == 404) {
                $this->refundAction($data);
            }
        }
    }

    public function rollOutChildThird(int $balance, string $tradeNo)
    {
        $res = $this->transfer($balance, $tradeNo, 'OUT');
        return [$res, $balance];
    }

    public function rollInChildThird(int $balance, string $tradeNo)
    {
        return $this->transfer($balance, $tradeNo, 'IN');
    }

    /**
     * 确认转账
     * {
     * "Username":"DEMO",
     * "RequestID":"xea1rt9gebhwy",
     * "Credit":1000.00,
     * BeforeCredit":0.00,
     * "OutstandingCredit":0.00,
     * "Time": "2017-11-16T13:59:35.885+08:00"
     * }
     * @param $balance
     * @param $tradeNo
     * @param string $type
     * @return bool|int
     */
    public function transfer($balance, $tradeNo, $type = 'IN')
    {
        $balance = bcdiv($balance, 100, 2);  //这边金额为分，  第三方金额为元
        $account = $this->getGameAccount();

        $data = [
            'Method' => 'TC',
            'Amount' => $type == 'OUT' ? bcmul($balance, -1, 2) : $balance,//正数转入负数转出
            'RequestID' => $this->config['cagent'] . $tradeNo,
            'Username' => $account['account'],
        ];
        $res = $this->requestParam($data, true);
        if ($res['status'] >= 400 || $res['status'] == 0) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 5.1.获取游戏列表
     * {
     * "ListGames": [
     * {
     * "GameType": "Slot",
     * "GameCode": "7ufj5fcktqre1",
     * "GameName": "Golden Shark",
     * "DefaultWidth": 960,
     * "DefaultHeight": 630,
     * "Special": "new,hot",
     * "Order": 40,
     * "Image1":
     * "//res.cloudinary.com/jsoftdev/image/upload/v1543399196/gameimages/landscape/7ufj5fcktqre1
     * .png",
     * "Image2":
     * "//res.cloudinary.com/jsoftdev/image/upload/v1543399196/gameimages/portrait/7ufj5fcktqre1.p
     * ng"
     * }
     * ] }
     */
    public function getListGames()
    {
        $fields = [
            'Method' => 'ListGames',
        ];
        $res = $this->requestParam($fields);
        return $res;
    }

    /**
     * 发送请求
     * @param array $param 请求参数
     * @param bool $status 是否返回请求状态
     * @return array|string
     */
    public function requestParam(array $param, bool $status = false)
    {
        if(is_null($this->config)){
            $ret = [
                'status' => 99999,
                'Message' => 'no api config'
            ];
            GameApi::addElkLog($ret,'JOKER');
            return $ret;
        }

        $param['Timestamp'] = time();

        $signature = $this->GetSignature($param);

        $url = $this->config['apiUrl'] . '?' . 'AppID=' . $this->config['cagent'] . '&Signature=' . $signature;
        $re = Curl::post($url, '', $param, '', $status);
        $remark = '';
        if (is_array($re)) {
            $remark = isset($re['status']) ? 'status:' . $re['status'] : '';
            $re = json_encode($re, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        GameApi::addRequestLog($url, 'JOKER', $param, $re, $remark);
        return json_decode($re, true);
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

    /**
     * 获取头奖
     * @return integer
     */
    public function getJackpot()
    {
        $fields = [
            'Method' => 'JP',
        ];
        $res = $this->requestParam($fields);
        if(isset($res['status']) && $res['status'] == 200) {
            $res['content'] = json_decode($res['content'], true);
            return $res['content']['Amount'];
        }
        return 0;
    }

    public function GetSignature($fields)
    {
        ksort($fields);
        $signature = urlencode(base64_encode(hash_hmac("sha1", urldecode(http_build_query($fields, '', '&')), $this->config['key'], TRUE)));

        return $signature;
    }

}

