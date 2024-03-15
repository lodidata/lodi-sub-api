<?php

use Utils\Www\Action;

return new class extends Action {
    const TOKEN = true;
    const TITLE = "代理统计";
    const TAGS = "代理统计";
    const SCHEMAS = [];

    public function run() {
        $verify = $this->auth->verfiyToken();

        if(!$verify->allowNext()) {
            return $verify;
        }

        $uid        = $this->auth->getUserId();
        $agent      = new \Logic\User\Agent($this->ci);
        $user_agent = $agent->generalizeList($uid);

        $agent_info = [
            'all_agent'    => $user_agent['all_agent'],
            'direct_agent' => 0,
            'next_agent'   => 0,
        ];
        $agent_info['direct_agent'] = \Model\UserAgent::where('uid_agent', $uid)->count();//直系下级代理人数
        $agent_info['next_agent'] = $agent_info['all_agent'] - $agent_info['direct_agent'] - 1;//下下级代理数

        if($agent_info['next_agent'] < 0){
            $agent_info['next_agent'] = 0;
        }

        //获取今日统计
        $today_info     = [
            'bet_amount'       => 0,//自身流水
            'next_bet_amount'  => 0,//下级流水
            'total_bet_amount' => 0,//总流水
            'new_register'     => 0,//注册用户
//            'valid_user'       => 0,//有效用户
            'next_agent'       => 0,//下级人数
            'recharge_user'    => 0,//总充值人数
            'recharge_amount'  => 0,//总充值金额
//            'mean_recharge'    => 0,//新增人均充值
//            'withdraw_amount'  => 0,//取款金额
//            'bet_amount'       => 0,//流水金额
            'profits'          => 0,//盈亏金额

        ];
        $today_time     = date('Y-m-d', time());
        $rpt_agent_info = \DB::table('rpt_agent')->where('agent_id', '=', $uid)->Where('count_date', '=', $today_time)->first();

        $rpt_user = DB::table("rpt_user")->where('user_id', $uid)->where('count_date', $today_time)->first(["bet_user_amount",'prize_user_amount']);
        if(!empty($rpt_agent_info)) {
            $rpt_agent_info                 = (array)$rpt_agent_info;
            $today_info['new_register']     = $rpt_agent_info['agent_inc_cnt'];
//            $today_info['valid_user']      = $rpt_agent_info['deposit_user_num'];
            $today_info['next_agent']       = $rpt_agent_info['agent_cnt'];
            $today_info['recharge_user']    = $rpt_agent_info['deposit_user_num'];
            $today_info['recharge_amount']  = $rpt_agent_info['deposit_agent_amount'];
            $today_info['total_bet_amount'] = $rpt_agent_info['bet_agent_amount'];
            $today_info['profits']          = bcsub($rpt_agent_info['bet_agent_amount'], $rpt_agent_info['prize_agent_amount'], 2);
//            $today_info['withdraw_amount'] = $rpt_agent_info['withdrawal_agent_amount'];
//            if($rpt_agent_info['new_register_deposit_num'] > 0 && $rpt_agent_info['new_register_deposit_amount'] > 0){
//                $mean = $rpt_agent_info['new_register_deposit_amount'] / $rpt_agent_info['new_register_deposit_num'];
//                $today_info['mean_recharge'] = round($mean,2);
//            }
        }
        if(!empty($rpt_user)) {
            $today_info['bet_amount'] = $rpt_user->bet_user_amount;
        }
        $today_info['next_bet_amount'] = bcsub($today_info['total_bet_amount'], $today_info['bet_amount'], 2);


//        $today_bkge_info = \DB::table('unlimited_agent_bkge')->where('user_id', '=', $uid)->Where('date', '=', $today_time)->first();
//        if(!empty($today_bkge_info)) {
//            $today_bkge_info          = (array)$today_bkge_info;
//            $today_info['bet_amount'] = $today_bkge_info['bet_amount'];
//            $today_info['profits']    = $today_bkge_info['bkge'];
//        }

        //获取昨日统计
        $yesterday_time      = date('Y-m-d', (time() - 24 * 60 * 60));
        $yesterday_info      = [
            'bet_amount' => 0,//总流水
            'fee_amount' => 0,//总成本
            'profits'    => 0,//总分红
            'game_list'  => [],//游戏投注
            'other_fee'  => [],//其他成本
        ];
        $yesterday_bkge_info = \DB::table('unlimited_agent_bkge')->where('user_id', '=', $uid)->Where('date', '=', $yesterday_time)->first();
        if(!empty($yesterday_bkge_info)) {
            $yesterday_bkge_info          = (array)$yesterday_bkge_info;
            $yesterday_info['bet_amount'] = $yesterday_bkge_info['bet_amount'];
            $yesterday_info['fee_amount'] = $yesterday_bkge_info['fee_amount'];
            $yesterday_info['profits']    = $yesterday_bkge_info['settle_amount'];
            $bet_list = [];
            $bkge_list = [];
            $fee_list = [];
            $proportion_list = [];
            if(!empty($yesterday_bkge_info['bet_amount_list'])){
                $bet_array = json_decode($yesterday_bkge_info['bet_amount_list'], true);
                if(!empty($bet_array) && is_array($bet_array)){
                    $bet_list = $bet_array;
                }
            }
            if(!empty($yesterday_bkge_info['bkge_list'])){
                $bkge_array = json_decode($yesterday_bkge_info['bkge_list'], true);
                if(!empty($bkge_array) && is_array($bkge_array)){
                    $bkge_list = $bkge_array;
                }
            }
            if(!empty($yesterday_bkge_info['proportion_list'])){
                $proportion_array = json_decode($yesterday_bkge_info['proportion_list'], true);
                if(!empty($proportion_array) && is_array($proportion_array)){
                    $proportion_list = $proportion_array;
                }
            }
            if(!empty($yesterday_bkge_info['fee_list'])){
                $fee_array = json_decode($yesterday_bkge_info['fee_list'], true);
                if(!empty($fee_array) && is_array($fee_array)){
                    $fee_list = $fee_array;
                }
            }
            foreach($bet_list as $key => $val){
                $yesterday_info['game_list'][] = [
                    'game_name'  => $this->lang->text($key),
                    'bet'        => $val,
                    'bkge'       => $bkge_list[$key] ?? 0,
                    'proportion' => $proportion_list[$key] ?? 0,
                    'fee'        => $fee_list[$key] ?? 0,
                ];
            }
            $yesterday_info['other_fee']['coupon'] = 0;
            $yesterday_info['other_fee']['manual'] = 0;
            $yesterday_info['other_fee']['deposit'] = 0;
            $yesterday_info['other_fee']['revenue'] = 0;
            if(isset($fee_list['coupon_ratio_amount'])){
                $yesterday_info['other_fee']['coupon'] = $fee_list['coupon_ratio_amount'];
            }
            if(isset($fee_list['manual_ratio_amount'])){
                $yesterday_info['other_fee']['manual'] = $fee_list['manual_ratio_amount'];
            }
            if(isset($fee_list['deposit_ratio_amount'])){
                $yesterday_info['other_fee']['deposit'] = $fee_list['deposit_ratio_amount'];
            }
            if(isset($fee_list['revenue_ratio_amount'])){
                $yesterday_info['other_fee']['revenue'] = $fee_list['revenue_ratio_amount'];
            }
        }
        if(empty($yesterday_info['other_fee'])){
            $yesterday_info['other_fee'] = (object)[];
        }

        //近7日统计
        $day_bet_list = $agent->betStatic($uid, 1);
        if(empty($day_bet_list)) {
            $day_bet_list = [];
        }

        //当前年月份统计
        $month_bet_list = $agent->betStatic($uid, 2);
        if(empty($month_bet_list)) {
            $month_bet_list = [];
        }
        //年月份倒叙
        $month_sort = array_column($month_bet_list, 'time');
        array_multisort($month_sort, SORT_DESC, $month_bet_list);
        foreach($month_bet_list as $key => $val) {
            $month_bet_list[$key]['diff'] = 0;
            $month_bet_list[$key]['proportion'] = '--';
            if(isset($month_bet_list[$key + 1])) {
                if($month_bet_list[$key]['bkge'] > $month_bet_list[$key + 1]['bkge']) {
                    $month_bet_list[$key]['diff'] = 1;
                } elseif($month_bet_list[$key]['bkge'] < $month_bet_list[$key + 1]['bkge']) {
                    $month_bet_list[$key]['diff'] = 2;
                }
            }
        }
        //检查开关
        $switch_list = \Model\Admin\SystemConfig::where('state','enabled')->where('module','user_agent')->get();
        if(!$switch_list->isEmpty()){
            foreach($switch_list as $switch_k => $switch_v){
                foreach($today_info as $k => $v){
                    if($k == $switch_v->key && $switch_v->value == 0){
                        unset($today_info[$k]);
                    }
                }
            }
        }
        //兼容前端显示
        if(empty($today_info)){
            $today_info = (object)[];
        }

        $data = [
            'agent_info'     => $agent_info,
            'today_info'     => $today_info,
            'yesterday_info' => $yesterday_info,
            'day_bet_list'   => $day_bet_list,
            'month_bet_list' => $month_bet_list,
        ];

        return $data;
    }
};