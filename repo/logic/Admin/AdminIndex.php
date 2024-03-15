<?php

namespace Logic\Admin;

use Logic\Define\CacheKey;
use Logic\Logic;
use Model\UserLogs;
use Model\FundsDeposit;
use Model\FundsTransferLog;
use Model\FundsWithdraw;
use Model\RptDepositWithdrawalDay;

/**
 * 后台首页统计
 * Class AdminIndex
 * @package Logic\Admin
 */
class AdminIndex extends Logic
{

    public function getParent($todayData, $yesterdayData){
        $parent = $yesterdayData == 0 ? 100 : bcmul(bcdiv($todayData - $yesterdayData, $yesterdayData, 5), 100, 2);
        return $parent;
    }

    /**
     * 首页统计
     * @return array|mixed
     */
    public function first()
    {
        $data = $this->redis->get('admin-index-top');
        if ($data) {
            return json_decode($data, true);
        }
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $before_yesterday = date('Y-m-d', strtotime('-2 day'));
        $today = date('Y-m-d');
        $time = date('H:i:s');

        //总注册人数
        $register_total = \DB::table('user')->count();
        //当前在线人数
        $online_total = $this->getTotalOnlineUserNum();
        //今天注册人数
        $register_today_total = \DB::table('user')->where('created', '>=', $today)->count();
        //昨天注册人数（同期）
        $register_yesterday_total = \DB::table('user')
            ->where('created', '>=', $yesterday)
            ->where('created', '<=', $yesterday . ' ' . $time)
            ->count();
        $register_yesterday_parent = $this->getParent($register_today_total,$register_yesterday_total);

        $today_rpt_user_data = (array)\DB::table('rpt_user')
            ->where('count_date', $today)
            ->selectRaw("sum(if(bet_user_amount > 0 AND deposit_user_amount > 0,1,0)) as active_deposit_user_count")
            ->first();
        $yesterday_rpt_user_data = (array)\DB::table('rpt_user')
            ->where('count_date', $yesterday)
            ->selectRaw("sum(if(bet_user_amount > 0 AND deposit_user_amount > 0,1,0)) as active_deposit_user_count")
            ->first();

        //活跃付费用户
        $today_active_deposit_user_count = $today_rpt_user_data['active_deposit_user_count'] ?? 0;
        $yesterday_active_deposit_user_count = $yesterday_rpt_user_data['active_deposit_user_count'] ?? 0;

        //30天活跃留存
        $day_30 = date('Y-m-d', strtotime('-30 day'));
        list($day30_login_total, $day30_login_parent) = $this->getLoginParent($day_30, $today, $time);

        //30天活跃留存(昨天同期)
        $yesterday_30 = date('Y-m-d', strtotime('-31 day'));
        list($day30_yesterday_login_total, $day30_yesterday_login_parent) = $this->getLoginParent($yesterday_30, $yesterday, $time);

        //15天活跃留存
        $day_15 = date('Y-m-d', strtotime('-15 day'));
        list($day15_login_total, $day15_login_parent) = $this->getLoginParent($day_15, $today, $time);

        //15天活跃留存(昨天同期)
        $yesterday_15 = date('Y-m-d', strtotime('-16 day'));
        list($day15_yesterday_login_total, $day15_yesterday_login_parent) = $this->getLoginParent($yesterday_15, $yesterday, $time);
        $deposit_withdrawal_day = (array)\DB::table('rpt_deposit_withdrawal_day')->where('count_date',$today)->first();
        $deposit_withdrawal_yesterday = (array)\DB::table('rpt_deposit_withdrawal_day')->where('count_date',$yesterday)->first();
        $deposit_withdrawal_before_yesterday = (array)\DB::table('rpt_deposit_withdrawal_day')->where('count_date',$before_yesterday)->first();

        //总活动赠送
        $active_today_total = bcmul(($deposit_withdrawal_day['coupon_amount']??0 + ($deposit_withdrawal_day['turn_card_winnings']??0) + ($deposit_withdrawal_day['promotion_winnings']??0)),1,2);
        $active_yesterday_total = bcmul(($deposit_withdrawal_yesterday['coupon_amount']??0 + ($deposit_withdrawal_yesterday['turn_card_winnings']??0) + ($deposit_withdrawal_yesterday['promotion_winnings']??0)),1,2);
        //总活动赠送(昨天同期)
        $active_yesterday_parent = $this->getParent($active_today_total,$active_yesterday_total);

        //今日返佣金额 取昨天的
        $bkge_amount_today = bcadd($deposit_withdrawal_yesterday['back_amount']??0,$deposit_withdrawal_yesterday['shares_settle_amount']??0,2);
        //昨日返佣金额 取前天的
        $bkge_amount_yesterday = bcadd($deposit_withdrawal_before_yesterday['back_amount']??0,$deposit_withdrawal_before_yesterday['shares_settle_amount']??0,2);
        //返佣金额(昨日同期)
        $bkge_amount_yesterday_parent = $this->getParent($bkge_amount_today, $bkge_amount_yesterday);

        //今日回水金额
        $return_amount_today = $deposit_withdrawal_day['return_amount']??0;
        //昨日回水金额
        $return_amount_yesterday = $deposit_withdrawal_yesterday['return_amount']??0;
        //返水金额(昨日同期)
        $return_amount_yesterday_parent = $this->getParent($return_amount_today, $return_amount_yesterday);

        //今日总充值
        $recharge_today_total = $deposit_withdrawal_day['income_amount']??0;
        //昨日总充值 因为要同期所以没用报表的
        $recharge_yesterday_total = bcdiv(\DB::table('funds_deposit')
            ->where('status', 'paid')
            ->where('created', '>=', $yesterday)
            ->where('created', '<=', $yesterday . ' ' . $time)
            ->sum('money'), 100, 2);

        //总充值(昨天同期)
        $recharge_yesterday_total_parent = $this->getParent($recharge_today_total,$recharge_yesterday_total);
        //今日总兑换
        $withdraw_today_total = $deposit_withdrawal_day['withdrawal_amount']??0;
        $withdraw_yesterday_total = bcdiv(\DB::table('funds_withdraw')
            ->where('status', 'paid')
            ->where('created', '>=', $yesterday)
            ->where('created', '<=', $yesterday . ' ' . $time)
            ->sum('money'), 100, 2);
        //总兑换(昨天同期)
        $withdraw_yesterday_total_parent = $this->getParent($withdraw_today_total,$withdraw_yesterday_total);
        //今日总营收
        $revenue_today_total = bcsub($recharge_today_total, $withdraw_today_total, 2);
        $revenue_yesterday_total = bcsub($recharge_yesterday_total, $withdraw_yesterday_total, 2);
        //总营收(昨天同期)
        $revenue_yesterday_total_parent = $this->getParent($revenue_today_total,$revenue_yesterday_total);
        //今天新增充值
        $new_recharge_today_total = bcdiv(\DB::table('funds_deposit')
            ->where('status', 'paid')
            ->whereRaw(\DB::raw("FIND_IN_SET('new',state)"))
            ->where('created', '>=', $today)
            ->sum('money'), 100, 2);
        $new_recharge_yesterday_total = bcdiv(\DB::table('funds_deposit')
            ->where('status', 'paid')
            ->whereRaw(\DB::raw("FIND_IN_SET('new',state)"))
            ->where('created', '>=', $yesterday)
            ->where('created', '<=', $yesterday . ' ' . $time)
            ->sum('money'), 100, 2);
        //今天新增充值(昨天同期)
        $recharge_yesterday_parent = $this->getParent($new_recharge_today_total,$new_recharge_yesterday_total);

        //3天充值留存
        $day_3 = date('Y-m-d', strtotime('-3 day'));
        list($day3_recharge_total, $day3_recharge_parent) = $this->getFundsDepositParent($day_3, $today, $time);

        //3天充值留存(昨天同期)
        $yesterday_3 = date('Y-m-d', strtotime('-4 day'));
        list($day3_yesterday_recharge_total, $day3_yesterday_recharge_parent) = $this->getFundsDepositParent($yesterday_3, $yesterday, $time);

        //15天充值留存
        list($day15_recharge_total, $day15_recharge_parent) = $this->getFundsDepositParent($day_15, $today, $time);

        //15天充值留存(昨天同期)
        list($day15_yesterday_recharge_total, $day15_yesterday_recharge_parent) = $this->getFundsDepositParent($yesterday_15, $yesterday, $time);

        //今日总投注
        $today_bet_total = bcmul(\DB::table('rpt_order_amount')
            ->where('count_date', '=', $today)
            ->sum('game_bet_amount'), 1, 2);
        $yesterday_bet_total = bcmul(\DB::table('rpt_order_amount')
            ->where('count_date', '=', $yesterday)
            ->sum('game_bet_amount'), 1, 2);

        $yesterday_bet_total_parent = $this->getParent($today_bet_total,$yesterday_bet_total);

        //营收杀率=营收/充值
        $revenue_today_kill_rate = $recharge_today_total ==0 ? 0 : bcmul(bcdiv($revenue_today_total, $recharge_today_total, 5), 100, 2);
        $revenue_yesterday_kill_rate = $recharge_yesterday_total ==0 ? 0 : bcmul(bcdiv($revenue_yesterday_total, $recharge_yesterday_total, 5), 100, 2);
        $revenue_yesterday_kill_rate_parent = $this->getParent($revenue_today_kill_rate, $revenue_yesterday_kill_rate);

        //流水杀率=营收/流水
        $bet_today_kill_rate = $today_bet_total ==0 ? 0 : bcmul(bcdiv($revenue_today_total, $today_bet_total, 5), 100, 2);
        $bet_yesterday_kill_rate = $yesterday_bet_total ==0 ? 0 : bcmul(bcdiv($revenue_yesterday_total, $yesterday_bet_total, 5), 100, 2);
        $bet_yesterday_kill_rate_parent = $this->getParent($bet_today_kill_rate, $bet_yesterday_kill_rate);

        //今日ARPPU=今日充值/活跃付费用户数 (活跃付费用户：总充值有流水产生的用户)
        $arppu_today = $today_active_deposit_user_count == 0 ? 0 : bcdiv($recharge_today_total, $today_active_deposit_user_count, 2);
        $arppu_yesterday = $yesterday_active_deposit_user_count == 0 ? 0 : bcdiv($recharge_yesterday_total, $yesterday_active_deposit_user_count, 2);

        $arppu_yesterday_parent = $this->getParent($arppu_today,$arppu_yesterday);

        $data = [
            'register_total' => $register_total,
            'online_total' => $online_total ?? 0,
            'register_today_total' => $register_today_total,
            'register_yesterday_parent' => $register_yesterday_parent,
            'day30_login_parent' => $day30_login_parent,
            'day30_yesterday_login_parent' => $day30_yesterday_login_parent,
            'day15_login_parent' => $day15_login_parent,
            'day15_yesterday_login_parent' => $day15_yesterday_login_parent,
            'revenue_today_total' => $revenue_today_total,
            'revenue_yesterday_total_parent' => $revenue_yesterday_total_parent,
            'withdraw_today_total' => $withdraw_today_total,
            'withdraw_yesterday_total_parent' => $withdraw_yesterday_total_parent,
            'recharge_today_total' => $recharge_today_total,
            'recharge_yesterday_total_parent' => $recharge_yesterday_total_parent,
            'new_recharge_today_total' => $new_recharge_today_total,
            'recharge_yesterday_parent' => $recharge_yesterday_parent,
            'day3_recharge_parent' => $day3_recharge_parent,
            'day3_yesterday_recharge_parent' => $day3_yesterday_recharge_parent,
            'day15_recharge_parent' => $day15_recharge_parent,
            'day15_yesterday_recharge_parent' => $day15_yesterday_recharge_parent,
            'bet_total' => $today_bet_total,
            'yesterday_bet_total_parent' => $yesterday_bet_total_parent,
            'revenue_today_kill_rate' => $revenue_today_kill_rate,
            'revenue_yesterday_kill_rate_parent' => $revenue_yesterday_kill_rate_parent,
            'bet_today_kill_rate' => $bet_today_kill_rate,
            'bet_yesterday_kill_rate_parent' => $bet_yesterday_kill_rate_parent,
            'arppu_today' => $arppu_today,
            'arppu_yesterday_parent' => $arppu_yesterday_parent,
            'active_total' => $active_today_total,
            'active_yesterday_parent' => $active_yesterday_parent,
            'rebet_total' => $return_amount_today,
            'rebet_yesterday_parent' => $return_amount_yesterday_parent,
            'bkge_amount_today' => $bkge_amount_today,
            'bkge_amount_yesterday_parent' => $bkge_amount_yesterday_parent,

        ];
        $this->redis->setex('admin-index-top', 300, json_encode($data, JSON_UNESCAPED_UNICODE));
        return $data;
    }

    /**
     * 首页统计第二部分
     * @param string $field 统计字段
     * @param string $type 统计类型
     * @return array
     */
    public function second($field = 'online', $type = 'minute')
    {
        $data = [];
        //按分钟
        if ($type == 'minute' || $type == 'hour') {
            $data['today']['list'] = $this->getSecondDataFromRedis($type, 'today', $field);
            $data['today']['total'] = $this->getSecondTotalData('today', $field);

            $data['yesterday']['list'] = $this->getSecondDataFromRedis($type, 'yesterday', $field);;
            $data['yesterday']['total'] = $this->getSecondTotalData('yesterday', $field);

            $data['lastweek']['list'] = $this->getSecondDataFromRedis($type, 'lastweek', $field);
            $data['lastweek']['total'] = $this->getSecondTotalData('lastweek', $field);

        } elseif ($type == 'day') {
            $month_list = $this->getSecondDataFromRedis($type, 'month', $field);
            $today_data = $this->getSecondTotalData('today', $field);

            $today_data = [
                $field => $today_data[0][$field] ?? 0,
                'day'    => date('Y-m-d'),
            ];
            //前29天存缓存 今天的实时查
            array_push($month_list,$today_data);
            $data['list'] = $month_list;
            $data['total'] = $this->getSecondTotalData('month', $field);
        }

        return $data;
    }

    public function getSecondDataFromRedis($type, $dateType, $field){
        $date = date('Y-m-d');
        $redis_key = "index_dada:second:{$date}:{$field}:{$type}:{$dateType}";
        $redis_data = $this->redis->get($redis_key);
        if($redis_data){
            return json_decode($redis_data, true);
        }

        $data_list = $this->getSecondData($type, $dateType, $field);
        //分 小时 数据需要格式化
        if ($type == 'minute' || $type == 'hour') {
            $data_list = $this->formatData($data_list, $type, $field, $dateType);
        }
        //昨天的数据 每月数据写入缓存
        if($dateType == 'yesterday' || $dateType == 'month' || $dateType == 'lastweek'){
            $this->redis->setex($redis_key,86400,json_encode($data_list));
        }

        return $data_list;
    }

    public function getSecondData($type, $dateType, $field){
        switch ($field){
            //实时在线和实时在玩取一样的
            case 'online':
            case 'game':
                $data_list = $this->getOnlineData($type, $dateType, $field);
                break;
            case 'recharge':
                $data_list = $this->getRechargeData($type, $dateType, $field);
                break;
            case 'oldRechargePeople':
            case 'newRechargePeople':
                $data_list = $this->getRechargePeople($type, $dateType, $field);
                break;
            case 'withdraw':
                $data_list = $this->getWithdrawData($type, $dateType, $field);
                break;
        }

        return $data_list??[];
    }

    public function getSecondTotalData($dateType, $field){
        $date = date('Y-m-d');
        $redis_key = "index_dada:second:{$date}:{$field}:total:{$dateType}";
        $redis_data = $this->redis->get($redis_key);
        if($redis_data){
            return json_decode($redis_data, true);
        }
        switch ($field){
            case 'online':
            case 'game':
                $data_list = $this->getOnlineTotalData($dateType, $field);
                break;
            case 'recharge':
                $data_list = $this->getRechargeTotalData($dateType, $field);
                break;
            case 'oldRechargePeople':
            case 'newRechargePeople':
                $data_list = $this->getRechargePeopleTotalData($dateType, $field);
                break;
            case 'withdraw':
                $data_list = $this->getWithdrawTotalData($dateType, $field);
                break;
        }
        //昨天的数据写入缓存
        if($dateType == 'yesterday'){
            $this->redis->setex($redis_key,86400,json_encode($data_list));
        }

        return $data_list??[];
    }

    /**
     * 实时数据  在线人数统计
     * @param $dateType
     * @param $field
     * @return mixed
     */
    public function getOnlineTotalData($dateType, $field){
        switch($dateType){
            case 'yesterday':
                $start_date = $end_date = date('Y-m-d', strtotime('-1 day'));
                break;
            case 'today':
                $start_date = $end_date  = date('Y-m-d');
                break;
            case 'month':
                $start_date = date('Y-m-d', strtotime('-30 day'));
                $end_date   = date('Y-m-d');
                break;
            default :
                $start_date = $end_date = date('Y-m-d');
        }

        $where = [
            ['created','>=',$start_date.' 00:00:00'],
            ['created','<=',$end_date.' 23:59:59'],
        ];

        $field = "ifnull(count(distinct user_id),0) {$field}";
        $date_list = FundsTransferLog::selectRaw($field)->where($where)->get()->toArray();
        return $date_list;
    }

    //新充人数、老充人数
    public function getRechargePeopleTotalData($dateType, $field){
        switch($dateType){
            case 'yesterday':
                $start_date = $end_date = date('Y-m-d', strtotime('-1 day'));
                break;
            case 'today':
                $start_date = $end_date  = date('Y-m-d');
                break;
            case 'month':
                $start_date = date('Y-m-d', strtotime('-30 day'));
                $end_date   = date('Y-m-d');
                break;
            default :
                $start_date = $end_date = date('Y-m-d');
        }

        $where = [
            ['status','=','paid'],
            ['money','>',0],
            ['created','>=',$start_date.' 00:00:00'],
            ['created','<=',$end_date.' 23:59:59'],
        ];

        $selectField = "ifnull(count(distinct user_id),0) {$field}";

        if ($field == "newRechargePeople") {    //新充人数
            $date_list = FundsDeposit::selectRaw($selectField)->where($where)->whereRaw("FIND_IN_SET('new', state)")->get()->toArray();
        } else {    //老充人数
//            $date_list = FundsDeposit::selectRaw($selectField)->where($where)->whereRaw("!FIND_IN_SET('new', state)")->get()->toArray();
            //老充人数 = 总充人数 - 新充人数
            $date_list = FundsDeposit::selectRaw($selectField)->where($where)->get()->toArray();
            $new_list = FundsDeposit::selectRaw($selectField)->where($where)->whereRaw("FIND_IN_SET('new', state)")->get()->toArray();
            if (!empty($date_list) && !empty($new_list)) {
                foreach ($date_list as $k=>&$v) {
                    if (isset($new_list[$k]['oldRechargePeople'])) {
                        $v['oldRechargePeople'] = $v['oldRechargePeople'] - $new_list[$k]['oldRechargePeople'];
                    }
                }
                unset($v);
            }
        }

        return $date_list;
    }

    public function getRechargeTotalData($dateType, $field){
        switch($dateType){
            case 'yesterday':
                $start_date = $end_date = date('Y-m-d', strtotime('-1 day'));
                break;
            case 'today':
                $start_date = $end_date  = date('Y-m-d');
                break;
            case 'month':
                $start_date = date('Y-m-d', strtotime('-30 day'));
                $end_date   = date('Y-m-d');
                break;
            default :
                $start_date = $end_date = date('Y-m-d');
        }

        $where = [
            ['count_date','>=',$start_date],
            ['count_date','<=',$end_date],
        ];

        $field = "round(ifnull(sum(income_amount),0),2)  {$field}";
        $date_list = RptDepositWithdrawalDay::selectRaw($field)->where($where)->get()->toArray();
        return $date_list;
    }

    public function getWithdrawTotalData($dateType, $field){
        switch($dateType){
            case 'yesterday':
                $start_date = $end_date = date('Y-m-d', strtotime('-1 day'));
                break;
            case 'today':
                $start_date = $end_date  = date('Y-m-d');
                break;
            case 'month':
                $start_date = date('Y-m-d', strtotime('-30 day'));
                $end_date   = date('Y-m-d');
                break;
            default :
                $start_date = $end_date = date('Y-m-d');
        }

        $where = [
            ['count_date','>=',$start_date],
            ['count_date','<=',$end_date],
        ];

        $field = "round(ifnull(sum(withdrawal_amount),0),2)  {$field}";
        $date_list = RptDepositWithdrawalDay::selectRaw($field)->where($where)->get()->toArray();
        return $date_list;
    }

    /**
     * 获取上一周的每一日
     */
    public function getLastWeekDayList(){
        $curr = date("Y-m-d");
        $w = date('w');
        $beginLastweek = strtotime($curr. '-'.($w ? $w-1 : 6).' days');
        $cur_monday = date('Y-m-d 00:00:00',$beginLastweek);
        $start_date = [];
        for ($i=7;$i>0;$i--){
            $start_date[] =  date('Y-m-d',strtotime("$cur_monday -$i days"));
        }
        return $start_date;
    }

    /**
     * 实时数据  在线人数
     * @param $type
     * @param $dateType
     * @param $field
     * @return mixed
     */
    public function getOnlineData($type, $dateType, $field){
        switch($type){
            case 'minute':
                $concat = "concat(SUBSTRING(created,12,4),'0')";
                break;
            case 'hour':
                $concat = "SUBSTRING(created,12,2)";
                break;
            case 'day':
                $concat = "left(created,10)";
                break;
            default :
                $concat = "concat(SUBSTRING(created,12,4),'0')";
        }

        switch($dateType){
            case 'yesterday':
                $start_date = $end_date = date('Y-m-d', strtotime('-1 day'));
                break;
            case 'today':
                $start_date = $end_date  = date('Y-m-d');
                break;
            case 'lastweek':
                $start_date = $end_date  = $this->getLastWeekDayList();
                break;
            case 'month':
                $start_date = date('Y-m-d', strtotime('-29 day'));
                $end_date   = date('Y-m-d', strtotime('-1 day'));
                break;
            default :
                $start_date = $end_date = date('Y-m-d');
        }

        if($dateType == 'lastweek'){
            $end_date = array_map(function ($item){
                return $item.' 23:59:59';
            },$end_date);
            $date_list_array = [];
            // 获取上周平均时需要获取上周每一天的数据取平均值
            foreach ($start_date as $key=>$date){
                $where = [
                    ['created','>=',$date.' 00:00:00'],
                    ['created','<=',$end_date[$key]],
                ];
                $fieldStr = "ifnull(count(distinct user_id),0) {$field},{$concat} day";
                $date_list = FundsTransferLog::selectRaw($fieldStr)->where($where)->groupBy('day')->get()->toArray();
                $date_list_array[] = $date_list;
            }

            $day_keys = [];
            foreach ($date_list_array as $key=>$value){
                $day_keys += array_flip(array_column($value,'day'));
                $date_list_array[$key] = array_column($value,$field,'day');
            }
            $day_keys = array_keys($day_keys);

            $return_data = [];
            foreach ($day_keys as $day){
                $single_day = [];
                $single_day['day'] = $day;
                $single_day[$field] = 0;
                foreach ($date_list_array as $value){
                    $single_day[$field] += $value[$day] ?? 0;
                }
                $single_day[$field] = floor($single_day[$field] / 7); //人数为小数时向下取整
                $return_data[] = $single_day;
            }
            return $return_data;
        }else{
            //今天的分钟 当前几分钟不要 因为曲线尾端会掉下去
            if($type == 'minute' && $dateType == 'today'){
                $end_time = substr(date('Y-m-d H:i:s'),0,15).'0:00';
                $operator = '<';
            }else{
                $end_time = $end_date.' 23:59:59';
                $operator = '<=';
            }
            $where = [
                ['created','>=',$start_date.' 00:00:00'],
                ['created',$operator,$end_time],
            ];
            $field = "ifnull(count(distinct user_id),0) {$field},{$concat} day";
            $date_list = FundsTransferLog::selectRaw($field)->where($where)->groupBy('day')->get()->toArray();
            return $date_list;
        }

    }

    public function getRechargeData($type, $dateType, $field){
        switch($type){
            case 'minute':
                $concat = "concat(SUBSTRING(created,12,4),'0')";
                break;
            case 'hour':
                $concat = "SUBSTRING(created,12,2)";
                break;
            case 'day':
                $concat = "left(created,10)";
                break;
            default :
                $concat = "concat(SUBSTRING(created,12,4),'0')";
        }

        switch($dateType){
            case 'yesterday':
                $start_date = $end_date = date('Y-m-d', strtotime('-1 day'));
                break;
            case 'today':
                $start_date = $end_date  = date('Y-m-d');
                break;
            case 'month':
                $start_date = date('Y-m-d', strtotime('-29 day'));
                $end_date   = date('Y-m-d', strtotime('-1 day'));
                break;
            case 'lastweek':
                $start_date = $end_date  = $this->getLastWeekDayList();
                break;
            default :
                $start_date = $end_date = date('Y-m-d');
        }

        if($dateType == 'lastweek'){
            $end_date = array_map(function ($item){
                return $item.' 23:59:59';
            },$end_date);
            $date_list_array = [];
            // 获取上周平均时需要获取上周每一天的数据取平均值
            foreach ($start_date as $key=>$date){
                $where = [
                    ['status','=','paid'],
                    ['money','>',0],
                    ['created','>=',$date.' 00:00:00'],
                    ['created','<=',$end_date[$key]],
                ];
                $fieldStr = "round(ifnull(sum(money/100),0),2) {$field},{$concat} day";
                $date_list = FundsDeposit::selectRaw($fieldStr)->where($where)->groupBy('day')->get()->toArray();
                $date_list_array[] = $date_list;
            }
            $day_keys = [];
            foreach ($date_list_array as $key=>$value){
                $day_keys += array_flip(array_column($value,'day'));
                $date_list_array[$key] = array_column($value,$field,'day');
            }
            $day_keys = array_keys($day_keys);

            $return_data = [];
            foreach ($day_keys as $day){
                $single_day = [];
                $single_day['day'] = $day;
                $single_day[$field] = 0;
                foreach ($date_list_array as $value){
                    $single_day[$field] += $value[$day] ?? 0;
                }
                $single_day[$field] = bcdiv($single_day[$field],7,2); //人数为小数时向下取整
                $return_data[] = $single_day;
            }
            return $return_data;
        }else{
            //今天的分钟 当前几分钟不要 因为曲线尾端会掉下去
            if($type == 'minute' && $dateType == 'today'){
                $end_time = substr(date('Y-m-d H:i:s'),0,15).'0:00';
                $operator = '<';
            }else{
                $end_time = $end_date.' 23:59:59';
                $operator = '<=';
            }

            $where = [
                ['status','=','paid'],
                ['money','>',0],
                ['created','>=',$start_date.' 00:00:00'],
                ['created',$operator,$end_time],
            ];

            $field = "round(ifnull(sum(money/100),0),2) {$field},{$concat} day";
            $date_list = FundsDeposit::selectRaw($field)->where($where)->groupBy('day')->get()->toArray();

            return $date_list;
        }
    }

    public function getRechargePeople($type, $dateType, $field){
        switch($type){
            case 'minute':
                $concat = "concat(SUBSTRING(created,12,4),'0')";
                break;
            case 'hour':
                $concat = "SUBSTRING(created,12,2)";
                break;
            case 'day':
                $concat = "left(created,10)";
                break;
            default :
                $concat = "concat(SUBSTRING(created,12,4),'0')";
        }

        switch($dateType){
            case 'yesterday':
                $start_date = $end_date = date('Y-m-d', strtotime('-1 day'));
                break;
            case 'today':
                $start_date = $end_date  = date('Y-m-d');
                break;
            case 'month':
                $start_date = date('Y-m-d', strtotime('-29 day'));
                $end_date   = date('Y-m-d', strtotime('-1 day'));
                break;
            case 'lastweek':
                $start_date = $end_date  = $this->getLastWeekDayList();
                break;
            default :
                $start_date = $end_date = date('Y-m-d');
        }

        if($dateType == 'lastweek'){
            $end_date = array_map(function ($item){
                return $item.' 23:59:59';
            },$end_date);
            $date_list_array = [];
            // 获取上周平均时需要获取上周每一天的数据取平均值
            foreach ($start_date as $key=>$date){
                $where = [
                    ['status','=','paid'],
                    ['money','>',0],
                    ['created','>=',$date.' 00:00:00'],
                    ['created','<=',$end_date[$key]]
                ];
                $selectField = "ifnull(count(distinct user_id),0) {$field},{$concat} day";

                if ($field == "newRechargePeople") {    //新充人数
                    $date_list = FundsDeposit::selectRaw($selectField)->where($where)->whereRaw("FIND_IN_SET('new', state)")->groupBy('day')->get()->toArray();
                } else {    //老充人数
//            $date_list = FundsDeposit::selectRaw($selectField)->where($where)->whereRaw("!FIND_IN_SET('new', state)")->groupBy('day')->get()->toArray();
                    //老充人数 = 总充人数 - 新充人数
                    $date_list = FundsDeposit::selectRaw($selectField)->where($where)->groupBy('day')->get()->toArray();
                    $new_list = FundsDeposit::selectRaw($selectField)->where($where)->whereRaw("FIND_IN_SET('new', state)")->groupBy('day')->get()->toArray();
                    if (!empty($date_list) && !empty($new_list)) {
                        $fmt_new_list = [];   //格式化一下新充值人数列表，用时间段作为key
                        foreach ($new_list as $n) {
                            $fmt_new_list[$n["day"]] = $n;
                        }
                        foreach ($date_list as &$v) {
                            if (isset($fmt_new_list[$v['day']])) {
                                $v['oldRechargePeople'] = $v['oldRechargePeople'] - $fmt_new_list[$v['day']]['oldRechargePeople'];
                            }
                        }
                        unset($v);
                    }
                }
                $date_list_array[] = $date_list;
            }

            $day_keys = [];
            foreach ($date_list_array as $key=>$value){
                $day_keys += array_flip(array_column($value,'day'));
                $date_list_array[$key] = array_column($value,$field,'day');
            }
            $day_keys = array_keys($day_keys);

            $return_data = [];
            foreach ($day_keys as $day){
                $single_day = [];
                $single_day['day'] = $day;
                $single_day[$field] = 0;
                foreach ($date_list_array as $value){
                    $single_day[$field] += $value[$day] ?? 0;
                }
                $single_day[$field] = floor($single_day[$field] / 7); //人数为小数时向下取整
                $return_data[] = $single_day;
            }
            return $return_data;
        }else{
            //今天的分钟 当前几分钟不要 因为曲线尾端会掉下去
            if($type == 'minute' && $dateType == 'today'){
                $end_time = substr(date('Y-m-d H:i:s'),0,15).'0:00';
                $operator = '<';
            }else{
                $end_time = $end_date.' 23:59:59';
                $operator = '<=';
            }

            $where = [
                ['status','=','paid'],
                ['money','>',0],
                ['created','>=',$start_date.' 00:00:00'],
                ['created',$operator,$end_time],
            ];

            $selectField = "ifnull(count(distinct user_id),0) {$field},{$concat} day";

            if ($field == "newRechargePeople") {    //新充人数
                $date_list = FundsDeposit::selectRaw($selectField)->where($where)->whereRaw("FIND_IN_SET('new', state)")->groupBy('day')->get()->toArray();
            } else {    //老充人数
//            $date_list = FundsDeposit::selectRaw($selectField)->where($where)->whereRaw("!FIND_IN_SET('new', state)")->groupBy('day')->get()->toArray();
                //老充人数 = 总充人数 - 新充人数
                $date_list = FundsDeposit::selectRaw($selectField)->where($where)->groupBy('day')->get()->toArray();
                $new_list = FundsDeposit::selectRaw($selectField)->where($where)->whereRaw("FIND_IN_SET('new', state)")->groupBy('day')->get()->toArray();
                if (!empty($date_list) && !empty($new_list)) {
                    $fmt_new_list = [];   //格式化一下新充值人数列表，用时间段作为key
                    foreach ($new_list as $n) {
                        $fmt_new_list[$n["day"]] = $n;
                    }
                    foreach ($date_list as &$v) {
                        if (isset($fmt_new_list[$v['day']])) {
                            $v['oldRechargePeople'] = $v['oldRechargePeople'] - $fmt_new_list[$v['day']]['oldRechargePeople'];
                        }
                    }
                    unset($v);
                }
            }

            return $date_list;
        }
    }

    public function getWithdrawData($type, $dateType, $field){
        switch($type){
            case 'minute':
                $concat = "concat(SUBSTRING(created,12,4),'0')";
                break;
            case 'hour':
                $concat = "SUBSTRING(created,12,2)";
                break;
            case 'day':
                $concat = "left(created,10)";
                break;
            default :
                $concat = "concat(SUBSTRING(created,12,4),'0')";
        }

        switch($dateType){
            case 'yesterday':
                $start_date = $end_date = date('Y-m-d', strtotime('-1 day'));
                break;
            case 'today':
                $start_date = $end_date  = date('Y-m-d');
                break;
            case 'month':
                $start_date = date('Y-m-d', strtotime('-29 day'));
                $end_date   = date('Y-m-d', strtotime('-1 day'));
                break;
            case 'lastweek':
                $start_date = $end_date  = $this->getLastWeekDayList();
                break;
            default :
                $start_date = $end_date = date('Y-m-d');
        }

        if($dateType == 'lastweek'){
            $end_date = array_map(function ($item){
                return $item.' 23:59:59';
            },$end_date);
            $date_list_array = [];
            // 获取上周平均时需要获取上周每一天的数据取平均值
            foreach ($start_date as $key=>$date){
                $where = [
                    ['status','=','paid'],
                    ['created','>=',$date.' 00:00:00'],
                    ['created','<=',$end_date[$key]],
                ];
                $fieldStr = "round(ifnull(sum(money/100),0),2) {$field},{$concat} day";
                $date_list = FundsWithdraw::selectRaw($fieldStr)->where($where)->groupBy('day')->get()->toArray();
                $date_list_array[] = $date_list;
            }

            $day_keys = [];
            foreach ($date_list_array as $key=>$value){
                $day_keys += array_flip(array_column($value,'day'));
                $date_list_array[$key] = array_column($value,$field,'day');
            }
            $day_keys = array_keys($day_keys);

            $return_data = [];
            foreach ($day_keys as $day){
                $single_day = [];
                $single_day['day'] = $day;
                $single_day[$field] = 0;
                foreach ($date_list_array as $value){
                    $single_day[$field] += $value[$day] ?? 0;
                }
                $single_day[$field] = floor($single_day[$field] / 7); //人数为小数时向下取整
                $return_data[] = $single_day;
            }
            return $return_data;
        }else{
            //今天的分钟 当前几分钟不要 因为曲线尾端会掉下去
            if($type == 'minute' && $dateType == 'today'){
                $end_time = substr(date('Y-m-d H:i:s'),0,15).'0:00';
                $operator = '<';
            }else{
                $end_time = $end_date.' 23:59:59';
                $operator = '<=';
            }

            $where = [
                ['status','=','paid'],
                ['created','>=',$start_date.' 00:00:00'],
                ['created',$operator,$end_time],
            ];
            $field = "round(ifnull(sum(money/100),0),2) {$field},{$concat} day";
            $date_list = FundsWithdraw::selectRaw($field)->where($where)->groupBy('day')->get()->toArray();

            return $date_list;
        }

    }

    public function formatData($data,$type, $field, $dayType){
        if($type == 'minute'){
            for($i=0; $i<=23; $i++){
                for($j=0; $j<=5;$j++){
                    $hour   = $i<10 ? "0{$i}":$i;
                    $minute = "{$j}0";
                    $time_list[] = "{$hour}:{$minute}";
                }
            }
        }
        if($type == 'hour'){
            for($i=0; $i<=23; $i++){
                $hour   = $i<10 ? "0{$i}":$i;
                $time_list[] = "{$hour}";
            }
        }
        $data = array_column($data,null,'day');
        if($dayType == 'today'){
            ksort($data);
            $last_key = array_key_last($data);
        }

        foreach ($time_list as $v){
            //今天的数据 时间未到的点 就不赋值了
            if($dayType == 'today'){
                if($v == $last_key) break;
            }
            if(!isset($data[$v])){
                $data[$v] = [
                    'day' => $v,
                    "$field" => 0,
                ];
            }
        }
        ksort($data);
        $data = array_values($data);
        return $data;
    }

    /**
     * 统计第三部分
     * @param $start_date
     * @param $end_date
     * @param $filed
     * @return array
     */
    public function third($start_date, $end_date, $filed)
    {
        $data = (array)\DB::table('admin_index_third')
            ->where('day', '>=', $start_date)
            ->where('day', '<=', $end_date)
            ->orderBy('day','asc')
            ->get(\DB::raw($filed))->toArray();
        //需求变更： 老充值人数改为总充人数-当日新充人数； 对应的老充金额和老用户平均付费也修改
        if ($data) {
            foreach ($data as &$itm) {
                $old_recharge_people = bcsub($itm->deposit_user_num, $itm->recharge_first_count);    //老充值人数 = 总充值人数 - 新充值人数
                $old_recharge_coin = bcsub($itm->recharge_total, $itm->recharge_first_money, 2);   //老充值金额 = 总充值金额 - 新充值金额
                //兼容负数
                $old_recharge_people = $old_recharge_people < 0 ? 0 : $old_recharge_people;
                $old_recharge_coin = $old_recharge_coin < 0 ? 0.00 : $old_recharge_coin;
                $old_recharge_avg = !empty($old_recharge_people) ? bcdiv($old_recharge_coin, $old_recharge_people, 2) : 0;    //老用户平均充值金额

                $itm->old_user_deposit_num = $old_recharge_people;
                $itm->old_user_deposit_amount = $old_recharge_coin;
                $itm->old_user_deposit_avg = $old_recharge_avg;
            }
        }
        return $data;
    }

    /**
     * 统计第四部分
     */
    public function four()
    {

    }

    /**
     * 统计第五部分
     */
    public function five()
    {
        $data = $this->redis->get('admin-index-five');
        if ($data) {
            return json_decode($data, true);
        }
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $today = date('Y-m-d');

        //总注册数
        $register_total = \DB::table('user')->count();
        //昨天注册人数
        $register_yesterday_total = \DB::table('user')
            ->where('created', '>=', $yesterday)
            ->where('created', '<', $today)
            ->count();
        //昨天在线数
        $yesterday_login_total =  $this->getTotalLoginUserNum($yesterday);

        //登录客户端列表
        $today_login_list     = $this->getLoginList($today);
        $yesterday_login_list = $this->getLoginList($yesterday);
        //充值占比
        $today_recharge_online_total = \DB::table('funds_deposit')
            ->where('status', 'paid')
            ->whereRaw("find_in_set('online',state)")
            ->where('created', '>=', $today)
            ->sum('money');
        $today_recharge_offline_total = \DB::table('funds_deposit')
            ->where('status', 'paid')
            ->whereRaw("!find_in_set('online',state)")
            ->where('created', '>=', $today)
            ->sum('money');
        $today_recharge_total = bcadd($today_recharge_online_total, $today_recharge_offline_total, 2);
        $today_online_parent = $today_recharge_online_total == 0 ? 0 : bcmul(bcdiv($today_recharge_online_total, $today_recharge_total, 5), 100, 2);
        $today_offline_parent = $today_recharge_offline_total == 0 ? 0 : bcsub(100, $today_online_parent, 2);

        $yesterday_recharge_online_total = \DB::table('funds_deposit')
            ->where('status', 'paid')
            ->whereRaw("find_in_set('online',state)")
            ->where('created', '>=', $yesterday)
            ->where('created', '<', $today)
            ->sum('money');
        $yesterday_recharge_offline_total = \DB::table('funds_deposit')
            ->where('status', 'paid')
            ->whereRaw("!find_in_set('online',state)")
            ->where('created', '>=', $yesterday)
            ->where('created', '<', $today)
            ->sum('money');
        $yesterday_recharge_total = bcadd($yesterday_recharge_online_total, $yesterday_recharge_offline_total, 2);
        $yesterday_online_parent = $yesterday_recharge_online_total == 0 ? 0 : bcmul(bcdiv($yesterday_recharge_online_total, $yesterday_recharge_total, 5), 100, 2);
        $yesterday_offline_parent = $yesterday_recharge_offline_total == 0 ? 0 : bcsub(100, $yesterday_online_parent, 2);

        $data = [
            'left' => [
                'total' => [
                    'register_total' => $register_total,
                    'register_yesterday_total' => $register_yesterday_total,
                    'yesterday_login_total' => $yesterday_login_total,
                ],
                'list' => [
                    'today' => $today_login_list,
                    'yesterday' => $yesterday_login_list,
                ]
            ],
            'right' => [
                'list' => [
                    'today' => [
                        'online' => [
                            'money' => bcdiv($today_recharge_online_total,100,2),
                            'parent' => $today_online_parent
                        ],
                        'offline' => [
                            'money' => bcdiv($today_recharge_offline_total,100,2),
                            'parent' => $today_offline_parent
                        ],
                    ],
                    'yesterday' => [
                        'online' => [
                            'money' => bcdiv($yesterday_recharge_online_total,100,2),
                            'parent' => $yesterday_online_parent
                        ],
                        'offline' => [
                            'money' => bcdiv($yesterday_recharge_offline_total,100,2),
                            'parent' => $yesterday_offline_parent
                        ],
                    ]
                ]
            ]
        ];
        $this->redis->setex('admin-index-five', 300, json_encode($data, JSON_UNESCAPED_UNICODE));
        return $data;
    }

    /**
     *生成首页统计第二部分
     */
    public function makeSecond()
    {
        $minute = date('i');
        $hour = date('H');
        $day = date('Y-m-d');
        $ten = date('Y-m-d H:i:s', strtotime('-10 minute'));
        //在线人数
        $online = $this->getTotalOnlineUserNum();
        //在线玩
        $game = \DB::table('funds_child')->where('balance', '>', '0')->count();
        $recharge = \DB::table('funds_deposit')->where('status', 'paid')->where('created', '>=', $ten)->sum('money');
        $withdraw = \DB::table('funds_withdraw')->where('status', 'paid')->where('created', '>=', $ten)->sum('money');
        $data = [
            'online' => $online ?? 0,
            'game' => $game,
            'recharge' => bcdiv($recharge,100,2),
            'withdraw' => bcdiv($withdraw,100,2),
            'minute' => $minute,
            'hour' => $hour,
            'day' => $day
        ];
        try {
            \DB::table('admin_index_second')->insert($data);
        } catch (\Exception $e) {
            $this->logger->error('admin_index_second ' . $e->getMessage());
        }
    }

    public function updateNextDayExtant(){
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $today = date("Y-m-d");
        //这个得继续更新 不然为0
        //次日留存 活跃用户留存
        $next_day_extant = $this->getActiveRetention($yesterday, $today);
        if($next_day_extant){
            \DB::table('admin_index_third')->where('day',$yesterday)->update(['next_day_extant'=>$next_day_extant]);
        }

        //首充用户 次日付费留存 次日活跃留存
        list($new_deposit_retention,$new_deposit_bet_retention) = $this->getNewDepositBetRetention($yesterday, $today);

        if($new_deposit_retention || $new_deposit_bet_retention){
            \DB::table('admin_index_third')->where('day',$yesterday)->update(['new_deposit_retention'=>$new_deposit_retention,'new_deposit_bet_retention'=>$new_deposit_bet_retention]);
        }
    }
    /**
     * 首页统计第三部分
     */
    public function makeThird()
    {
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $today = date("Y-m-d");

        if (\DB::table('admin_index_third')->where('day', $yesterday)->count()) {
            return true;
        }

        $deposit_withdraw_data = RptDepositWithdrawalDay::where('count_date','=',$yesterday)
            ->first();
        $deposit_withdraw_data && $deposit_withdraw_data = $deposit_withdraw_data->toArray();

        //新注册
        $register_new = $deposit_withdraw_data['new_user_num']??0;

        $rpt_user_data = (array)\DB::table('rpt_user')
            ->where('count_date', $yesterday)
            ->selectRaw("sum(if(bet_user_amount > 0,1,0)) as game_user_count, sum(if(bet_user_amount > 0 AND deposit_user_amount > 0,1,0)) as active_deposit_user_count, sum(if(first_deposit > 0,dml,0)) as new_deposit_user_dml")
            ->first();

        //新充打码量  当日首充用户的打码量
        $new_deposit_user_dml = bcdiv($rpt_user_data['new_deposit_user_dml']??0,1,2);

        //活跃用户
        $game_user_count = $rpt_user_data['game_user_count'] ?? 0;
        //活跃付费用户
        $active_deposit_user_count = $rpt_user_data['active_deposit_user_count'] ?? 0;

        //总充值
        $deposit_amount = $deposit_withdraw_data['income_amount']??0;
        $recharge_total = bcdiv($deposit_amount,1,2);
        //总充值人数
        $deposit_user_num = $deposit_withdraw_data['deposit_user_num']??0;

        //总兑换
        $withdraw_total = $deposit_withdraw_data['withdrawal_amount']??0;
        //充兑差
        $recharge_witchdraw = bcsub($recharge_total, $withdraw_total, 2);

        $order_amount_data = (array)\DB::table('rpt_order_amount')
            ->where('count_date', '=', $yesterday)
            ->selectRaw('sum(game_code_amount) game_code_amount,sum(game_bet_amount) game_bet_amount')
            ->first();

        $today_bet_total = bcdiv($order_amount_data['game_bet_amount']??0,1,2);
        //总打码量
        $dml_total = bcdiv($order_amount_data['game_code_amount']??0,1,2);

        //首充人数
        $recharge_first_count = $deposit_withdraw_data['new_deposit_user_num']??0;
        //转化率=新首充个数/新用户
        $inversion_rate = $register_new == 0 ? 0 : bcmul(bcdiv($recharge_first_count, $register_new, 5), 100, 2);
        //ARPPU=总充值/活跃付费用户数 (活跃付费用户：总充值有流水产生的用户)
        $arppu = $active_deposit_user_count? bcdiv($recharge_total, $active_deposit_user_count, 2) :0;
        //次日留存 活跃用户留存
        $next_day_extant = $this->getActiveRetention($yesterday, $today);
        //新增有效代理数
        $user_agent = $deposit_withdraw_data['new_valid_agent_num']??0;
        //新增代理新充值会员数(下级会员)
        $agent_new_user_count = $deposit_withdraw_data['agent_first_deposit_num']??0;

        //新增充值金额
        $recharge_first_money = $deposit_withdraw_data['new_register_deposit_amount']??0;

        //新增人均充值金额
        $recharge_first_avg = $deposit_withdraw_data['new_register_deposit_num'] == 0 ? 0: bcdiv($recharge_first_money,$deposit_withdraw_data['new_register_deposit_num'],2);

        //营收杀率=营收/充值
        $revenue_today_kill_rate = $recharge_total ==0 ? 0 : bcmul(bcdiv($recharge_witchdraw, $recharge_total, 5), 100, 2);
        //流水杀率=营收/流水
        $bet_today_kill_rate = $today_bet_total ==0 ? 0 : bcmul(bcdiv($recharge_witchdraw, $today_bet_total, 5), 100, 2);
        //老用户数据
        $order_user_where = [
            ['count_date','=', $yesterday],
            ['register_time','<', $yesterday],
            ['deposit_user_amount','>',0]
        ];

        //不是当日注册用户都为老用户
        $old_user_data = (array)\DB::table('rpt_user')->selectRaw('count(1) as deposit_num,sum(deposit_user_amount) deposit_user_amount')->where($order_user_where)->first();
        $old_user_deposit_num    = $old_user_data['deposit_num'];
        $old_user_deposit_amount = bcdiv($old_user_data['deposit_user_amount']??0,1,2);
        $old_user_deposit_avg    = $old_user_deposit_num ? bcdiv($old_user_deposit_amount,$old_user_deposit_num,2) : 0;

        //主渠道新增注册(注册时无上级代理)
        $no_agent_user_num_where = [
            ['u.created','>=',$yesterday],
            ['u.created','<',$today],
            ['ua.uid_agent','=',0],
        ];

        $no_agent_user_num = \DB::table('user as u')->join('user_agent as ua','u.id','=','ua.user_id','inner')->where($no_agent_user_num_where)->count('u.id');

        $data = [
            'register_new' => $register_new,
            'game_user_count' => $game_user_count,
            'recharge_total' => $recharge_total,
            'withdraw_total' => $withdraw_total,
            'new_register_withdraw_amount' => $deposit_withdraw_data['new_register_withdraw_amount']?? 0,
            'recharge_witchdraw' => $recharge_witchdraw,
            'dml_total' => $dml_total,
            'inversion_rate' => $inversion_rate,
            'arppu' => $arppu,
            'next_day_extant' => $next_day_extant,
            'user_agent' => $user_agent,
            'agent_new_user_count' => $agent_new_user_count,
            'recharge_first_count' => $recharge_first_count,
            'recharge_first_money' => $recharge_first_money,
            'recharge_first_avg' => $recharge_first_avg,
            'revenue_today_kill_rate' => $revenue_today_kill_rate,
            'bet_today_kill_rate' => $bet_today_kill_rate,
            'old_user_deposit_num' => $old_user_deposit_num,
            'old_user_deposit_amount' => $old_user_deposit_amount,
            'old_user_deposit_avg' => $old_user_deposit_avg,
            'no_agent_user_num' => $no_agent_user_num,
            'new_deposit_user_dml' => $new_deposit_user_dml,
            'deposit_user_num' => $deposit_user_num,
            'day' => $yesterday,
        ];

        try {
            \DB::table('admin_index_third')->insert($data);
        } catch (\Exception $e) {
            $this->logger->error('admin_index_third ' . $e->getMessage());
        }
    }

    /**
     * 活跃留存
     * @param $newDay
     * @param $lastDay
     * @param $time
     * @return array
     */
    public function getLoginParent($newDay, $lastDay, $time)
    {
        $day30_user_ids = (array)\DB::table('funds_deposit')
            ->whereRaw(\DB::raw("FIND_IN_SET('new',state)"))
            ->where('status', 'paid')
            ->where('created', '>=', $newDay)
            ->where('created', '<=', $newDay . ' ' . $time)
            ->where('money', '>', 0)
            ->pluck('user_id')
            ->toArray();
        $day30_user_ids_count = count($day30_user_ids);
        //充值留存率
        $day30_count = 0;
        $day30_parent = 0;
        if ($day30_user_ids_count) {
            //首充用户第30天玩游戏
            $day30_count = \DB::table('rpt_user')
                ->whereIn('user_id', $day30_user_ids)
                ->where('count_date', '=', $lastDay )
                ->where('bet_user_amount', '>', 0)
                ->distinct()->count('user_id');
            $day30_parent = bcmul(bcdiv($day30_count, $day30_user_ids_count, 5), 100, 2);
        }
        return [$day30_count, $day30_parent];
    }

    /**
     * 活跃留存率   有流水的
     *
     */
    public function getActiveRetention($startDay, $endDay){
        $user_id_list = \DB::table('rpt_user')
            ->where('bet_user_amount', '>', 0)
            ->where('count_date', $startDay)
            ->pluck('user_id')->toArray();

        if(!count($user_id_list)) return 0;
        $end_user_id_num = \DB::table('rpt_user')
            ->where('bet_user_amount', '>', 0)
            ->where('count_date', $endDay)
            ->whereIn('user_id', $user_id_list)
            ->count('user_id');
        $rate = bcdiv($end_user_id_num*100, count($user_id_list),2);
        return $rate;
    }

    /**
     * 次日付费留存 (当日首充用户  次日再复充的)
     * 次日活跃留存 (当日首充用户 次日投注的)
     *
     */
    public function getNewDepositBetRetention($startDay, $endDay){
        $user_id_list = \DB::table('rpt_user')
            ->where('first_deposit', 1)
            ->where('count_date',  $startDay)
            ->pluck('user_id')->toArray();

        $first_deposit_user_num = count($user_id_list);
        if(!$first_deposit_user_num) return 0;
        $deopsit_user_id_num = \DB::table('rpt_user')
            ->where('deposit_user_amount', '>', 0)
            ->where('count_date', $endDay)
            ->whereIn('user_id', $user_id_list)
            ->count('user_id');

        $bet_user_id_num = \DB::table('rpt_user')
            ->where('bet_user_amount', '>', 0)
            ->where('count_date', $endDay)
            ->whereIn('user_id', $user_id_list)
            ->count('user_id');

        $deposit_rate = bcdiv($deopsit_user_id_num*100, $first_deposit_user_num,2);
        $bet_rate = bcdiv($bet_user_id_num*100, $first_deposit_user_num,2);
        return [$deposit_rate, $bet_rate];
    }

    /**
     * 获取充值留存率
     * @param string $newDay 首充日期
     * @param string $lastDay 最后一天日期
     * @param string $time 当前时间
     * @return array
     */
    public function getFundsDepositParent($newDay, $lastDay, $time)
    {
        $day30_user_ids = (array)\DB::table('funds_deposit')
            ->whereRaw(\DB::raw("FIND_IN_SET('new',state)"))
            ->where('status', 'paid')
            ->where('created', '>=', $newDay)
            ->where('created', '<=', $newDay . ' ' . $time)
            ->where('money', '>', 0)
            ->pluck('user_id')
            ->toArray();
        $day30_user_ids_count = count($day30_user_ids);
        //充值留存率
        $day30_count = 0;
        $day30_parent = 0;
        if ($day30_user_ids_count) {
            //首充用户再次充值的数量（一个用户算一次）
            $day30_count = \DB::table('funds_deposit')
                ->whereIn('user_id', $day30_user_ids)
                ->where('status', 'paid')
                ->where('created', '>=', $lastDay)
                ->where('created', '<=', $lastDay . ' ' . $time)
                ->where('money', '>', 0)
                ->distinct()->count('user_id');
            $day30_parent = bcmul(bcdiv($day30_count, $day30_user_ids_count, 5), 100, 2);
        }
        return [$day30_count, $day30_parent];
    }

    public function getLoginList($date){
        $total = 0;
        $today_list = [];
        for ($i = 1; $i <= 4; $i++) {
            $num    = $this->getTotalLoginUserNum($date, $i);
            $total += $num;
            $today_list[$i] = [
                'platform' => $i,
                'num'      => $num,
                'parent'   => 0
            ];
        }
        //计算百分比
        foreach($today_list as &$v){
            $v['num'] && $v['parent'] = intval($v['num'] / $total *100);
        }

        return array_values($today_list);
    }
    /**
     * 按平台登录统计
     * @param $start_date
     * @param $end_date
     * @return array
     */
    public function getLoginLog($start_date, $end_date)
    {
        $list_today = \DB::table('user_logs')
            ->where('log_type', 1)
            ->where('status', 1)
            ->where('created', '>=', $start_date)
            ->where('created', '<', $end_date)
            ->groupBy('platform')
            ->get(['platform', \DB::raw("count(0) as num")])
            ->toArray();
        $today_login_total = 0;
        $today_list = [];
        for ($i = 1; $i <= 4; $i++) {
            $today_list[$i] = [
                'platform' => $i,
                'num' => 0,
                'parent' => 0
            ];
        }
        if ($list_today) {
            foreach ($list_today as &$v) {
                $v = (array)$v;
                $today_login_total += $v['num'];
                $today_list[$v['platform']]['num'] = $v['num'];
            }
            unset($v);
            foreach ($today_list as $key => $v) {
                if ($v['num'] == 0) {
                    continue;
                }
                $today_list[$key]['parent'] = bcmul(bcdiv($v['num'], $today_login_total, 5), 100, 2);
            }
            if ($today_list[4]['num'] > 0) {
                $today_list[4]['parent'] = bcsub(100, ($today_list[1]['parent'] + $today_list[2]['parent'] + $today_list[3]['parent']), 2);
            } elseif ($today_list[3]['num'] > 0) {
                $today_list[3]['parent'] = bcsub(100, ($today_list[1]['parent'] + $today_list[2]['parent']), 2);
            } elseif ($today_list[2]['num'] > 0) {
                $today_list[2]['parent'] = bcsub(100, ($today_list[1]['parent']), 2);
            } elseif ($today_list[1]['num'] > 0) {
                $today_list[1]['parent'] = 100;
            }
        }

        return array_values($today_list);
    }

    /**
     * 统计当前在线人数
     * @return mixed
     */
    public function getTotalOnlineUserNum(){
        $redis_key = 'user_online_num';
        $total = $this->redis->get($redis_key);

        if(!$total){
            //统计半小时内 有进出游戏的用户人数
            $date = date('Y-m-d H:i:s',time()-1800);
            $num = FundsTransferLog::where([['created','>=',$date]])->count(\DB::raw('distinct user_id'));

            $this->redis->setex($redis_key,120,$num);
            return (int)$num;
        }
        return (int)$total;

    }

    /**
     * 获取登录人数
     * @return mixed
     */
    public function getTotalLoginUserNum($date,$platform=0){
        $redis_key = "user_login_num:{$date}:{$platform}";
        $total = $this->redis->get($redis_key);

        if(!$total){
            $where = [
                ['created','>=',$date.' 00:00:00'],
                ['created','<=',$date.' 23:59:59'],
                ['log_type','=',1],
                ['status','=',1],
            ];
            $platform && $where[] = ['platform','=', $platform];
            $num = UserLogs::where($where)->count(\DB::raw('distinct user_id'));
            if($date == date('Y-m-d')){
                $expire_time = 120;
            }else{
                $expire_time = 86400;
            }

            $this->redis->setex($redis_key, $expire_time, $num);
            return (int)$num;
        }
        return (int)$total;

    }
}