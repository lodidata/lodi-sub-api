<?php

namespace Logic\GameApi\Game;

use Logic\Define\Cache3thGameKey;
use Logic\GameApi\GameApi;
use Logic\GameApi\GameToken;
use Model\FundsChild;
use Utils\Client;
use DB;
use Utils\Curl;

/**
 * SBO体育
 * Class SBO
 * @package Logic\GameApi\Game
 */
class SBO extends \Logic\GameApi\Api
{
    protected $langs = [
        'th' => 'th-th',
        'zh-cn' => 'zh-cn',
        'en-us' => 'en',
        'vn' => 'vi-vn',
        'pt' => 'pt-pt',
        'my' => 'my-mm',
        'id' => 'id-id',
        'jp' => 'ja-jp',
        'ko' => 'ko-kr',
        'de' => 'de-de',
        'es-mx' => 'es-es',
        'fr' => 'fr-fr',
        'ru' => 'ru-ru'
    ];

    protected $orderTable = 'game_order_sbo';

    /**
     * 注册代理
     * @return mixed
     */
    public function registerAgent()
    {
        $fields = [
            'Username' => $this->config['cagent'],
            'Password' => 'Bo4i5jA93hf3E4wfe',
            'Currency' => $this->config['currency'],
            'Min' => 1,
            'Max' => 10000,
            'MaxPerMatch' => 20000,
            'CasinoTableLimit' => 1,
            'ServerId' => $this->config['lobby'],
        ];
        $action = "/web-root/restricted/agent/register-agent.aspx";
        $res = $this->requestParam($action, $fields);
        return $res['status'];
    }

    /**
     * 关闭代理
     * @return mixed
     */
    public function closeAgent()
    {
        $tid = $this->ci->get('settings')['app']['tid'];
        $fields = [
            'Username' => $this->config['cagent'],
            'Status' => 'CLOSED',
        ];
        $action = "/web-root/restricted/agent/update-agent-status.aspx";
        $res = $this->requestParam($action, $fields);
        return $res['status'];
    }


    /**
     * 无需创建账户，第三方会自动判定
     * @param string $account
     * @param string $password
     * @return bool
     */
    public function childCreateAccount(string $account, string $password)
    {

        $param = [
            'Username' => $account,
            'Agent' => $this->config['cagent'],
            'UserGroup' => 'a'
        ];

        $action = "/web-root/restricted/player/register-player.aspx";
        $res = $this->requestParam($action, $param);
        return $res['status'];
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
            'Username' => $account['account'],
            'Portfolio' => $params['kind_id'],
            'IsWapSports' => false,
        ];
        $action = "/web-root/restricted/player/login.aspx";
        $res = $this->requestParam($action, $data);
        if ($res['status']) {
            //余额转入第三方
            $result = $this->rollInThird();
            if (!$result['status']) {
                return [
                    'status' => 886,
                    'message' => $result['msg'],
                    'url' => ''
                ];
            }

            $lang = $this->langs[LANG]?? $this->langs['en-us'];
            return [
                'status' => 0,
                'url' => $res['url'] . "&lang={$lang}&oddstyle=MY&theme=sbo&oddsmode=double&device=m",
                'message' => 'ok'
            ];
        } else {
            return [
                'status' => -1,
                'message' => $res['error']['msg'] ?? '',
                'url' => ''
            ];
        }
    }

    /**
     * 资料的搜索范围必须是在60天以内。
     * 修改时间区间需小于或等于30分鐘。
     * @throws \Exception
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
                'game' => 'SPORT',
                'order_number' => $val['refNo'],
                'game_type' => $this->game_type,
                'type_name' => $this->lang->text($this->game_type),
                'play_id' => 72,
                'bet' => bcmul($val['stake'], 100, 0),
                'profit' => bcmul($val['winlost'], 100, 0),
                'send_money' => bcmul($val['stake'] + $val['winlost'], 100, 0),
                'order_time' => $val['orderTime'],
                'date' => substr($val['orderTime'], 0, 10),
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
     * 退出游戏
     * @return bool
     * @throws \Exception
     */
    public function quitChildGame()
    {
        $account = $this->getGameAccount();
        $data = [
            'Username' => $account['account'],
        ];
        $action = "/web-root/restricted/player/logout.aspx";
        $res = $this->requestParam($action, $data);
        if ($res['status']) {
            $this->rollOutThird();
            return true;
        }
        return false;
    }

    /**
     * 检测金额
     * @param null $data
     */
    public function checkMoney($data = null)
    {
        return true;
    }

    /**
     * 检查转账状态
     * @param $tradeNo
     * @return bool|int
     */
    public function transferCheck($tradeNo)
    {
        $data = [
            'TxnId' => $tradeNo
        ];
        $action = "/web-root/restricted/player/check-transaction-status.aspx";
        $res = $this->requestParam($action, $data);
        return $res['status'];
    }

    /**
     * 获取第三方钱包信息 (分为单位)
     * @return array[可下分金额，总金额]
     */
    public function getThirdWalletBalance()
    {
        $account = $this->getGameAccount();
        $data = [
            'Username' => $account['account'],
        ];
        $action = "/web-root/restricted/player/get-player-balance.aspx";
        $res = $this->requestParam($action, $data);
        if ($res['status']) {
            //该会员的余额，它还需要减去未结算投注才会是会员的真正余额。
            return [bcmul($res['balance'] - $res['outstanding'], 100, 0), bcmul($res['balance'], 100, 0)];
        }

        $balance = FundsChild::where('pid', $this->wid)->where('game_type', $this->game_alias)->value('balance');
        return [0, $balance];
    }

    /***
     * 退出第三方,并回收至钱包
     * @param int $balance
     * @param string $tradeNo
     * @return array(是否成功,转出后第三方剩余金额)
     */
    public function rollOutChildThird(int $balance, string $tradeNo)
    {
        $account = $this->getGameAccount();
        $data = [
            'Username' => $account['account'],
            'TxnId' => $tradeNo,
            'IsFullAmount' => true,
            'Amount' => bcdiv($balance, 100, 2)
        ];
        $action = "/web-root/restricted/player/withdraw.aspx";
        $res = $this->requestParam($action, $data);
        if ($res['status']) {
            return [true, $balance];
        } else {
            return [false, $balance];
        }
    }

    /**
     * 进入第三方，并转入钱包
     * @param int $balance
     * @param string $tradeNo
     * @return bool|int
     */
    function rollInChildThird(int $balance, string $tradeNo)
    {
        $account = $this->getGameAccount();
        $data = [
            'Username' => $account['account'],
            'Amount' => bcdiv($balance, 100, 2),
            'TxnId' => $tradeNo
        ];

        $action = "/web-root/restricted/player/deposit.aspx";
        $res = $this->requestParam($action, $data);

        return $res['status'];
    }

    /**
     * 发送请求
     * @param string $action 请求方法
     * @param array $param 请求参数
     * @param bool $is_post 是否为post请求
     * @param bool $status 是否返回请求状态
     * @return array|string
     */
    public function requestParam(string $action, array $param, bool $is_post = true, $status = false)
    {
        if(is_null($this->config)){
            $ret = [
                'status' => false,
                'error' => ['msg' => 'no api config']
            ];
            GameApi::addElkLog($ret,'SBO');
            return $ret;
        }
        $param['CompanyKey'] = $this->config['key'];
        $param['ServerId'] = $this->config['lobby'];
        //$querystring = urldecode(http_build_query($param,'', '&'));
        $url = $this->config['apiUrl'] . $action;
        if ($is_post) {
            $re = Curl::post($url, null, $param, null, true);
        } else {
            $re = Curl::get($url, null, true);
        }
        if ($re['status'] == 200) {
            $re['content'] = json_decode($re['content'], true);
        }
        GameApi::addRequestLog($url, 'SBO', $param, json_encode($re, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $ret = [];
        if ($re['status'] == 200) {
            $ret = $re['content'];
            $ret['responseStatus'] = true;
            if (isset($ret) && isset($ret['error']) && isset($ret['error']['id']) && $ret['error']['id'] == 0) {
                $ret['status'] = true;
            }else{
                $ret['status'] = false;
            }
        } else {
            $ret['responseStatus'] = false;
            $ret['error']['msg'] = $re['content'];
            $ret['status'] = false;
        }
        return $ret;
    }

    private function getMessage($code)
    {
        $message = [
            "0" => "No Error",
            "1" => "无效的Company Key",
            "2" => "无效的请求格式",
            "3" => "内部错误",
            "4" => "无效的会员名称",
            "5" => "无效的国家",
            "6" => "无效的语言",
            "13" => "请选择安全系数更高的账号。建议您使用字母、数字和 '_' 的组合",
            "3101" => "无效的币别",
            "3102" => "无效的主题Id",
            "3104" => "创建代理失败",
            "3201" => "更新状态失败",
            "3202" => "无效的会员名称-更新状态",
            "3203" => "已更新状态",
            "3204" => "无效的状态",
            "3205" => "无效的日期",
            "3206" => "无效的单笔注单最低限额",
            "3207" => "无效的单笔注单最高限额",
            "3208" => "无效的单场比赛最高限额",
            "3209" => "无效的真人赌场下注设定",
            "3303" => "用户不存在",
            "4101" => "代理不存在",
            "4102" => "创建会员失败",
            "4103" => "会员名称存在",
            "4106" => "代理帐号为关闭状态",
            "4107" => "請創建在代理而非下线底下",
            "4201" => "验证失败",
            "4401" => "无效的交易Id",
            "4402" => "无效的交易金额.比如:输入金额为 负值 或输入金额含有 超过两位小数 (范例: 19.217 和 19.2245 均会报错)",
            "4403" => "交易失败",
            "4404" => "重复使用相同的交易Id",
            "4501" => "余额不足",
            "4502" => "于余额不足导致的回滚(Rollback Transaction)交易",
            "4601" => "检查交易状态失败",
            "4602" => "未找到任何交易",
            "4701" => "获得余额失败",
            "6101" => "获取客户报表失败",
            "6102" => "获取客户注单失败",
            "6666" => "没有此下注纪录",
            "9527" => "无效的运动类型",
            "9528" => "无效的盘口",
            "9720" => "提款请求次数太过频繁",
            "9721" => "无效的密码格式"
        ];
    }

    function getJackpot()
    {
        return 0;
    }
}