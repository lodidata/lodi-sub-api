<?php

use Logic\Admin\BaseController;
use Model\Profile;
use Model\UserData;

return new class() extends BaseController
{
//    const STATE       = \API::DRAFT;
    const TITLE = '用户详情';
    const DESCRIPTION = '';
    
    const QUERY = [
        'id' => 'int(required) #用户id',
        'type' => 'enum[stat,base,balance,withdraw,bank](required) #获取细分项，可能值：统计 stat，基本信息 base，账户余额 balance，取款稽核 withdraw，银行信息 bank',
        'page' => 'int()#当前页数',
        'page_size' => 'int() #一页多少条数'
    ];
    
    const STATEs = [
//        \Las\Utils\ErrorCode::INVALID_VALUE => '无效用户id'
    ];
    const PARAMS = [];
    const SCHEMAS = [
        ['map # channel: registe=网站注册, partner=第三方,reserved =保留'],
    ];

    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run($id = null)
    {
        $this->checkID($id);

        $result = ['inCome' => [], 'balance' => [], 'earnLoss' => [], 'order' => [], 'rebet' => [], 'rakeAgent' => []];
        $profile = DB::table('user_data')
            ->where('user_id', $id)
            ->selectRaw('order_num,order_amount,deposit_num,deposit_amount,withdraw_num,withdraw_amount,send_amount,rebet_amount,bkge_lottery,bkge_game,bkge_live,bkge_sport')
            ->first();
        $depositTimes = $depositSum = $withdrawTimes = $withdrawSum = $orderTimes = $orderSum = $sendMoneySum = $earnLossSum = $rebetSum = $activeSum = 0;

        $depositTimes = $profile->deposit_num;
        $depositSum = $profile->deposit_amount;
        $withdrawTimes = $profile->withdraw_num;
        $withdrawSum = $profile->withdraw_amount;
        $orderTimes = $profile->order_num;
        $orderSum = $profile->order_amount;
        $sendMoneySum = $profile->send_amount;
        $rebetSum = $profile->rebet_amount;
        $activeSum = DB::table('active_apply')->where('user_id', $id)->where('status', 'pass')->sum('coupon_money');

        $result['inCome'] = array('depositTimes' => $depositTimes, 'depositSum' => $depositSum, 'withdrawTimes' => $withdrawTimes, 'withdrawSum' => $withdrawSum);
        $result['order'] = array('orderTimes' => $orderTimes, 'orderSum' => $orderSum, 'sendSum' => $sendMoneySum);
        $result['earnLoss'] = array('earnLoss' => $sendMoneySum - $orderSum + $rebetSum + $activeSum);
        $result['rebet'] = array('rebetSum' => $rebetSum, 'activeSum' => $activeSum);

        //转盘抽奖次数统计
        $counts = $this->getLuckyCount($id);
        $result['lucky'] = array('luckyCountSum' => $counts['luckyCountSum'], 'luckyCount' => $counts['luckyCount']);//当日可转次数   当日剩余次数

        //余额
        $dml = new \Logic\Wallet\Dml($this->ci);
        $tmp = $dml->getUserDmlData($id);
        $dmlData = [
            'factCode' => $tmp->total_bet,
            'codes' => $tmp->total_require_bet,
            'canMoney' => $tmp->free_money,
            'balance' => \Model\User::getUserTotalMoney($id)['lottery'] ?? 0,
        ];
        $result['balance'] = $dmlData;


        $rack_data = [
            'bkge_game' => $profile->bkge_game,
            'bkge_live' => $profile->bkge_live,
            'bkge_sport' => $profile->bkge_sport,
            'bkge_lottery' => $profile->bkge_lottery,
            'sum' => $profile->bkge_game + $profile->bkge_live + $profile->bkge_sport + $profile->bkge_lottery,
        ];

        $result['rakeAgent'] = $rack_data;
        return $result;

    }

    //获取抽奖次数
    public function getLuckyCount($id)
    {
        $countSum = $this->redis->hget(\Logic\Define\CacheKey::$perfix['luckyCountSum'] . date('Y-m-d'), $id);

        $countEveryday = (int)$this->redis->hget(\Logic\Define\CacheKey::$perfix['lockyCount'] . date('Y-m-d'), $id);


        $profile_model = new UserData();
        $count = $profile_model::where('user_id', $id)->value('draw_count');
        $count2 = $count + $countEveryday;

        if ($countSum === null) {
            /* 查询幸运转盘配置*/
            $luckyData = DB::table('active')->where('type_id', 6)->select(['id'])->first();
            /* 查询幸运转盘rule*/
            $ruleData = DB::table('active_rule')->select(['limit_times'])->where('active_id', $luckyData->id)->first();

            $dr = \Model\UserData::where('user_id', $id)->value('draw_count');

            $num = $countSum + (int)$ruleData->limit_times + $dr;
            $this->redis->hset(\Logic\Define\CacheKey::$perfix['luckyCountSum'] . date('Y-m-d'), $id, $num);

            $countSum = $num;

        }
        if ($countSum < $count2) {
            $countSum = $count2;
        }

        $counts = [
            'luckyCountSum' => $countSum,
            'luckyCount' => $count2
        ];

        return $counts;


    }


};
