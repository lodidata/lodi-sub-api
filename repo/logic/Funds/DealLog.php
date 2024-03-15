<?php

namespace Logic\Funds;

use Logic\Define\CacheKey;
use Logic\Logic;
use Logic\Set\SystemConfig;
use Logic\Wallet\Dml;
use Model\FundsDealLog;

class DealLog extends Logic {

    /*
     * 依据具体type获取大类
     */
    public static function getDealCategory(int $type) {
        $data = DealLog::getDealLogTypes();
        foreach ($data as $val) {
            if(in_array($type,array_column($val['children'],'id'))) {
                return $val['id'];
            }
        }
        return FundsDealLog::CATEGORY_COST;
    }

    /*
     * 依据具体type获取名称
     */
    public function getDealTypeName(int $type) {
        $name = $this->redis->hGet(CacheKey::$perfix['fundsDealLogType'].'name',$type);
        if($name)  return $name;
        $data = DealLog::getDealLogTypes();
        $data = array_column($data,'children');
        $result = array_reduce($data, function ($result, $value) {
            $res = [];
            foreach ($value as $v) {
                $res[$v['id']] = $v['name'];
            }
            return $result + $res;
        }, array());
        $this->redis->hMset(CacheKey::$perfix['fundsDealLogType'].'name',$result);
        return $result[$type] ?? '未分类';
    }

    /**
     * 大额审核里包含的人工存提交易类型
     * @param int $type
     * @return array
     */
    public static function getManualCheckDealTypes($type=1){
        global $app;
        $ci = $app->getContainer();
        $data = [
            ["id" => "1", "name" => $ci->lang->text('Manual deposit')],
            ["id" => "5", "name" => $ci->lang->text('Manual addmoney')],
            ["id" => "11", "name" => $ci->lang->text('Manual increase freemoney')],
            ["id" => "13", "name" => $ci->lang->text('Withdrawal increase share')],
            ["id" => "15", "name" => $ci->lang->text('Manual increase direct')],
        ];

        if($type==2){
            $data = array_column($data,"name","id");

        }
        return $data;
    }

    /**
     * 人工存提交易类型
     * @param int $type
     * @return array
     */
    public static function getManualDealTypes($type=1)
    {
        global $app;
        $ci = $app->getContainer();
        $data = [
            ["id" => "1", "name" => $ci->lang->text('Manual deposit')],
            ["id" => "2", "name" => $ci->lang->text('Manual decrease money')],
            ["id" => "3", "name" => $ci->lang->text('Manual discount amount')],
            ["id" => "4", "name" => $ci->lang->text('Manual decrease balance')],
            ["id" => "5", "name" => $ci->lang->text('Manual addmoney')],
            ["id" => "6", "name" => $ci->lang->text('Manual rebet money')],
            ["id" => "7", "name" => $ci->lang->text('Manual sub to master Wallet')],
            ["id" => "8", "name" => $ci->lang->text('Manual master to sub Wallet')],
            ["id" => "9", "name" => $ci->lang->text('Manual increase bet')],
            ["id" => "10", "name" => $ci->lang->text('Manual decrease bet')],
            ["id" => "11", "name" => $ci->lang->text('Manual increase freemoney')],
            ["id" => "12", "name" => $ci->lang->text('Manual decrease freemoney')],
            ["id" => "13", "name" => $ci->lang->text('Withdrawal increase share')],
            ["id" => "14", "name" => $ci->lang->text('Withdrawal decrease share')],
            ["id" => "15", "name" => $ci->lang->text('Manual increase direct')],
            ["id" => "16", "name" => $ci->lang->text('Manual decrease direct')],
        ];

        if($type==2){
            $data = array_column($data,"name","id");

        }

        return $data;
    }

    public static function getDealLogTypes() {
        global $app;
        $ci = $app->getContainer();
        return [
            [
                'id'       => FundsDealLog::CATEGORY_INCOME,
                'name'     => $ci->lang->text('Income'),
                'children' => [
                    ['id' => FundsDealLog::TYPE_INCOME_ONLINE,              'name' => $ci->lang->text('Online payment')],
                    ['id' => FundsDealLog::TYPE_INCOME_OFFLINE,             'name' => $ci->lang->text('Offline payment')],
                    ['id' => FundsDealLog::TYPE_PAYOUT_LOTTERY,             'name' => $ci->lang->text('Lottery prize')],
                    ['id' => FundsDealLog::TYPE_ACTIVITY,                   'name' => $ci->lang->text('activity')],
                    ['id' => FundsDealLog::TYPE_INCOME_MANUAL,              'name' => $ci->lang->text('Manual deposit')],
                    ['id' => FundsDealLog::TYPE_REBET,                      'name' => $ci->lang->text('Rebet money')],
                    ['id' => FundsDealLog::TYPE_AGENT_CHARGES,              'name' => $ci->lang->text('Agent commission')],
                    ['id' => FundsDealLog::TYPE_CANCEL_ORDER,               'name' => $ci->lang->text('Cancel order refund')],
                    ['id' => FundsDealLog::TYPE_ADDMONEY_MANUAL,            'name' => $ci->lang->text('Manual addmoney')],
                    ['id' => FundsDealLog::TYPE_REBET_MANUAL,               'name' => $ci->lang->text('Manual rebet money')],
                    ['id' => FundsDealLog::TYPE_ACTIVITY_MANUAL,            'name' => $ci->lang->text('Manual discount amount')],
                    ['id' => FundsDealLog::TYPE_WIRTDRAW_REFUSE,            'name' => $ci->lang->text('Refund for withdrawal failure')],
                    ['id' => FundsDealLog::TYPE_DECREASE_FREEMONEY_MANUAL,  'name' => $ci->lang->text('Manual decrease freemoney')],
                    ['id' => FundsDealLog::TYPE_INCREASE_FREEMONEY_MANUAL,  'name' => $ci->lang->text('Manual increase freemoney')],
                    ['id' => FundsDealLog::TYPE_LOTTERY_SETTLE,             'name' => $ci->lang->text('Lottery settle')],
                    ['id' => FundsDealLog::TYPE_THIRD_SETTLE,               'name' => $ci->lang->text('Third party game settlement')],
                    ['id' => FundsDealLog::TYPE_HAND_DML_ADD,               'name' => $ci->lang->text('Manual increase bet')],
                    ['id' => FundsDealLog::TYPE_HAND_DML_PLUS,              'name' => $ci->lang->text('Manual decrease bet')],
                    ['id' => FundsDealLog::TYPE_LEVEL_MANUAL1,              'name' => $ci->lang->text('Level reward')],
                    ['id' => FundsDealLog::TYPE_LEVEL_MANUAL2,              'name' => $ci->lang->text('Level manual2')],
                    ['id' => FundsDealLog::TYPE_LEVEL_MONTHLY,              'name' => $ci->lang->text('Level monthly reward')],
                    ['id' => FundsDealLog::TYPE_LEVEL_WEEK,                 'name' => $ci->lang->text('Level week reward')],
                    ['id' => FundsDealLog::TYPE_TRANSFER_XIMA,              'name' => $ci->lang->text('Xima')],
                    ['id' => FundsDealLog::TYPE_INCREASE_SHARE_MANUAL,      'name' => $ci->lang->text('Withdrawal increase share')],
                    ['id' => FundsDealLog::TYPE_DECREASE_SHARE_MANUAL,      'name' => $ci->lang->text('Withdrawal decrease share')],
                    ['id' => FundsDealLog::TYPE_CTOM_SHARE,                 'name' => $ci->lang->text('Share wallet')],
                    ['id' => FundsDealLog::TYPE_WIRTDRAW_SHARE_REFUSE,      'name' => $ci->lang->text('Withdrawal share fail')],
                    ['id' => FundsDealLog::TYPE_DAILY_REBET,                'name' => $ci->lang->text('Daily rebet money')],
                    ['id' => FundsDealLog::TYPE_WEEKLY_REBET,               'name' => $ci->lang->text('Weekly rebet money')],
                    ['id' => FundsDealLog::TYPE_MONTHLY_REBET,              'name' => $ci->lang->text('Monthly rebet money')],
                    ['id' => FundsDealLog::TYPE_DIRECT_REWARD_INCOME,         'name' => $ci->lang->text('Direct award income')],
                    ['id' => FundsDealLog::TYPE_INCREASE_DIRECT_MANUAL,         'name' => $ci->lang->text('Manual increase direct')],
                    ['id' => FundsDealLog::TYPE_INCREASE_DIRECT_DML,         'name' => $ci->lang->text('Direct award increase dml')],
                ],
            ],
            [
                'id'       => FundsDealLog::CATEGORY_COST,
                'name'     => $ci->lang->text('Pay'),
                'children' => [
                    ['id' => 201, 'name' => $ci->lang->text('Successful withdrawal')],
                    ['id' => 202, 'name' => $ci->lang->text('Lottery bet')],
                    ['id' => 204, 'name' => $ci->lang->text('Manual decrease money')],
                    ['id' => 207, 'name' => $ci->lang->text('Manual decrease balance')],
                    ['id' => 208, 'name' => $ci->lang->text('Withdrawal under review')],
                    ['id' => 213, 'name' => $ci->lang->text('Withdrawal confiscated')],
                    //['id' => 209, 'name' => '追号冻结'],
                    ['id' => FundsDealLog::TYPE_REDUCE_MANUAL_OTHER, 'name' => $ci->lang->text('Decrease money other')],
                    ['id' => FundsDealLog::TYPE_WITHDRAW_SHARE, 'name' => $ci->lang->text('Withdrawal share')],
                    ['id' => FundsDealLog::TYPE_SHARE_WITHDRAW,             'name' => $ci->lang->text('Withdrawal share success')],
                    ['id' => FundsDealLog::TYPE_SHARE_WITHDRAW_CONFISCATE,  'name' => $ci->lang->text('Withdrawal share confiscate')],
                    ['id' => FundsDealLog::TYPE_DIRECT_REWARD_COST,           'name' => $ci->lang->text('Direct award cost')],
                    ['id' => FundsDealLog::TYPE_DECREASE_DIRECT_MANUAL,           'name' => $ci->lang->text('Manual decrease direct')],
                ],
            ],
            [
                'id'       => FundsDealLog::CATEGORY_TRANS,
                'name'     => $ci->lang->text('Exchange'),
                'children' => [
                    ['id' => 301, 'name' => $ci->lang->text('Sub to master Wallet')],
                    ['id' => 302, 'name' => $ci->lang->text('Master to sub Wallet')],
                    ['id' => 303, 'name' => $ci->lang->text('Manual sub to master Wallet')],
                    ['id' => 304, 'name' => $ci->lang->text('Manual master to sub Wallet')],
                    ['id' => 305, 'name' => $ci->lang->text('Master Wallet to safe')],
                    ['id' => 306, 'name' => $ci->lang->text('Safe to master Wallet')],
                ],
            ],
            [
                'id'       => FundsDealLog::CATEGORY_FREEMONEY,
                'name'     => $ci->lang->text('Freemoney'),
                'children' => [
                    ['id' => 119, 'name' => $ci->lang->text('Manual decrease freemoney')],
                    ['id' => 120, 'name' => $ci->lang->text('Manual increase freemoney')],
                ],
            ],
        ];
    }

    public static function getDealLogTypeFlat() {
        $_ = [];
        foreach (self::getDealLogTypes() as $types) {
            $_ = array_merge($_, $types['children']);
        }

        return array_column($_, 'name', 'id');
    }

    public static function addDealLog (
        int $user_id,   //用户ID
        string $user_name,  //用户名
        int $balance,   //余额钱包
        string $orderNumber,   //订单
        int $money,   //处理金额
        int $dealType,   //处理类型
        $memo = null,   //备注
        $dealDMLMoney = 0,   //打码码金额
        $gameType = 'CP',   //游戏类型，彩票等
        $isAddDML = false   //是否添加打码量
    ) {
        global $app;
        //需要增加应有打码量的
        if (in_array($dealType, \Model\Dml::DML_REQUEST)) {
            $opt = 2;
        }else if(in_array($dealType, \Model\Dml::DML_ACTUAL)) {
            $dmlCent = SystemConfig::getModuleSystemConfig('audit');
            $dealDMLMoney = isset($dmlCent[$gameType]) ? $dmlCent[$gameType]*$dealDMLMoney / 100 : $dealDMLMoney;
            $opt = 1;
        }else if($isAddDML){
            $opt = 2;
        }

        $dmlLogin = new Dml($app->getContainer());
        //应有打码量
        if (isset($opt) && $dealDMLMoney > 0) {
            $dml = $dmlLogin->getUserDmlData($user_id, $dealDMLMoney, $opt);
        } else {
            $dml = $dmlLogin->getUserDmlData($user_id);
        }
        \Model\FundsDealLog::create([
            'user_id' => $user_id,
            'user_type' => 1,
            'username' => $user_name,
            'order_number' => $orderNumber,
            'deal_type' => $dealType,
            'deal_category' => self::getDealCategory($dealType),
            'deal_money' => $money, //交易金额
            'balance' => $balance,
            'withdraw_bet' => $dealDMLMoney, //交易打码量
            'free_money' => $dml->free_money,
            'total_require_bet' => $dml->total_require_bet,
            'total_bet' => $dml->total_bet,
            'memo' => $memo,
            'wallet_type' => \Model\FundsDealLog::WALLET_TYPE_PRIMARY,
        ]);

    }

    /**
     * 第三方游戏的订单 交易流水写到 funds_order_deal_log表里
     * @param int $user_id
     * @param string $user_name
     * @param int $balance
     * @param string $orderNumber
     * @param int $money
     * @param int $dealType
     * @param null $memo
     * @param int $dealDMLMoney
     * @param string $gameType
     */
    public static function addOrderDealLog (
        int $user_id,   //用户ID
        string $user_name,  //用户名
        int $balance,   //余额钱包
        string $orderNumber,   //订单
        int $money,   //处理金额
        int $dealType,   //处理类型
        $memo = null,   //备注
        $dealDMLMoney = 0,   //打码码金额
        $gameType = 'CP'   //游戏类型，彩票等
    ) {
        global $app;
        //需要增加应有打码量的
        if (in_array($dealType, \Model\Dml::DML_REQUEST)) {
            $opt = 2;
        }else if(in_array($dealType, \Model\Dml::DML_ACTUAL)) {
            $dmlCent = SystemConfig::getModuleSystemConfig('audit');
            $dealDMLMoney = isset($dmlCent[$gameType]) ? $dmlCent[$gameType]*$dealDMLMoney / 100 : $dealDMLMoney;
            $opt = 1;
        }

        $dmlLogin = new Dml($app->getContainer());
        //应有打码量
        if (isset($opt) && $dealDMLMoney > 0) {
            $dml = $dmlLogin->getUserDmlData($user_id, $dealDMLMoney, $opt);
        } else {
            $dml = $dmlLogin->getUserDmlData($user_id);
        }
        \Model\FundsDealLog::create([
            'user_id' => $user_id,
            'user_type' => 1,
            'username' => $user_name,
            'order_number' => $orderNumber,
            'deal_type' => $dealType,
            'deal_category' => self::getDealCategory($dealType),
            'deal_money' => $money, //交易金额
            'balance' => $balance,
            'withdraw_bet' => $dealDMLMoney, //交易打码量
            'free_money' => $dml->free_money,
            'total_require_bet' => $dml->total_require_bet,
            'total_bet' => $dml->total_bet,
            'memo' => $memo,
            'wallet_type' => \Model\FundsDealLog::WALLET_TYPE_PRIMARY,
        ]);

    }

}
