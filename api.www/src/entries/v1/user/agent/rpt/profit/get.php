<?php

use Logic\Set\SystemConfig;
use Utils\Www\Action;

return new class extends Action {
    const TOKEN = true;
    const TITLE = "盈亏统计";
    const TAGS = "盈亏统计";
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
            'all_vip'      => 0,
            'direct_vip'   => 0,
            'next_vip'     => 0,
            'settle_title' => '',
        ];
        $agent_info['direct_agent'] = \Model\UserAgent::where('uid_agent', $uid)->count();//直系下级代理人数
        $agent_info['next_agent'] = $agent_info['all_agent'] - $agent_info['direct_agent'] - 1;//下下级代理数

        if($agent_info['next_agent'] < 0){
            $agent_info['next_agent'] = 0;
        }
        $count_self             = SystemConfig::getModuleSystemConfig('rakeBack')['bkge_calculation_self'] ?? 0;  //1:统计自身，0:不统计
        //获取返佣结算方式
        $settle_type = 1;
        $system_info = \DB::table('system_config')->where('key','bkge_settle_type')->first();
        if(!empty($system_info)){
            $settle_type = $system_info->value;
        }
        //1、日 2、周 3、月
        if($settle_type == 2){
            $agent_info['settle_title'] = $this->lang->text('bkge_settle_week');
        }elseif($settle_type == 3){
            $agent_info['settle_title'] = $this->lang->text('bkge_settle_month');
        }else{
            $agent_info['settle_title'] = $this->lang->text('bkge_settle_day');
        }

        //获取盈亏统计
        $profit_info     = [
            'return_amount'    => 0,//总流水
            'earn_amount'      => 0,//总收益
            'valid_bet'        => 0,//有效投注
            'profit_amount'    => 0,//总盈亏
            'cost_amount'      => 0,//总成本
            'bet_user'         => 0,//投注人数
            'new_user'         => 0,//新用户
            'valid_user'       => 0,//活跃用户
            'withdraw_amount'  => 0,//提现金额
            'recharge_amount'  => 0,//充值金额
            'first_reg_amount' => 0,//首充金额
            'first_reg_user'   => 0,//首充人数
            'second_reg_user'  => 0,//复充人数
        ];

        $params = $this->request->getParams();
        $start_time = $params['start_time'] ?? '';
        if(empty($start_time)){
            $start_time = date('Y-m-d',time());
        }
        $end_time = $params['end_time'] ?? '';
        if(empty($end_time)){
            $end_time = date('Y-m-d',time());
        }

        $rpt_agent_list = \DB::table('rpt_agent')
            ->where('agent_id', '=', $uid)
            ->Where('count_date', '>=', $start_time)
            ->Where('count_date', '<=', $end_time)
            ->get()->toArray();
        $loseearn_list = \DB::table('agent_loseearn_bkge')
            ->where('user_id', '=', $uid)
            ->Where('date', '>=', $start_time)
            ->Where('date', '<=', $end_time)
            ->get()->toArray();

        foreach($rpt_agent_list as $key => $val){
            $profit_info['return_amount'] = bcadd($val->return_agent_amount, $profit_info['return_amount'], 2);
            $profit_info['new_user'] = bcadd($val->agent_inc_cnt, $profit_info['new_user']);
            $profit_info['withdraw_amount'] = bcadd($val->withdrawal_agent_amount, $profit_info['withdraw_amount'], 2);
            $profit_info['recharge_amount'] = bcadd($val->deposit_agent_amount, $profit_info['recharge_amount'], 2);
            $profit_info['first_reg_amount'] = bcadd($val->new_register_deposit_amount, $profit_info['first_reg_amount'], 2);
            $profit_info['first_reg_user'] = bcadd($val->new_register_deposit_num, $profit_info['first_reg_user']);
        }

        foreach($loseearn_list as $key => $val){
            $profit_info['earn_amount'] = bcadd($val->bkge, $profit_info['earn_amount'], 2);
            //盈亏包含自身  因为现在数据是从日表取的  日表的loseearn_amount是总盈亏，包含了自身
            //   而周表和月表的 loseearn_amount 是处理过的 不包含自身时 周表和月表里 loseearn_amountd等于sub_loseearn_amount
            if($count_self == 1){
                $profit_info['profit_amount'] = bcadd($val->loseearn_amount, $profit_info['profit_amount'], 2);
            }else{
                $profit_info['profit_amount'] = bcadd($val->sub_loseearn_amount, $profit_info['profit_amount'], 2);
            }

            $profit_info['valid_bet'] = bcadd($val->bet_amount, $profit_info['valid_bet'], 2);
            $profit_info['cost_amount'] = bcadd($val->fee_amount, $profit_info['cost_amount'], 2);
        }

        //投注人数
        //获取下级代理uids
        $child_agent = \DB::table('child_agent')->where('pid', '=', $uid)->get()->toArray();
        $agent_uids = [];
        if (!empty($child_agent)) {
            $agent_uids = array_column($child_agent, 'cid');
        }
        $profit_info['bet_user'] = $profit_info['valid_user'] = \DB::table('rpt_user')
            ->Where('count_date', '>=', $start_time)
            ->Where('count_date', '<=', $end_time)
            ->WhereIn('user_id', $agent_uids)
            ->Where('bet_user_amount', '>', 0)
            ->distinct()
            ->count('user_id');
        //复充人数
        $profit_info['second_reg_user'] = \DB::table('rpt_user')
            ->Where('count_date', '>=', $start_time)
            ->Where('count_date', '<=', $end_time)
            ->WhereIn('user_id', $agent_uids)
            ->Where('deposit_user_amount', '>', 0)
            ->Where('first_deposit', 0)
            ->distinct()
            ->count('user_id');

        //获取昨日数据
        $yesterday_time      = date('Y-m-d', (time() - 24 * 60 * 60));
        $game_list = [];
//        $loseearn_first = \DB::table('agent_loseearn_bkge')
//            ->where('user_id', '=', $uid)
//            ->Where('date', '=', $yesterday_time)
//            ->first();
        $loseearn_first=DB::table('user_agent')->where('user_id',$uid)->first('profit_loss_value');
        if(!empty($loseearn_first) && !empty($loseearn_first->profit_loss_value)){
            $proportion_list = json_decode($loseearn_first->profit_loss_value, true);
            foreach($proportion_list as $pro_k => $pro_v){
                $game_list[] = [
                    'name'    => $this->lang->text($pro_k),
                    'percent' => $pro_v,
                ];
            }
        }

        //获取流水前10名
        $bet_max_list = [];
        $rpt_user_list = \DB::table('rpt_user')
            ->select('user_name',\DB::raw('sum(bet_user_amount) as bet_user_amount'),\DB::raw('sum(dml) as dml'),\DB::raw('sum(prize_user_amount) as prize_user_amount'))
            ->Where('count_date', '>=', $start_time)
            ->Where('count_date', '<=', $end_time)
//            ->WhereIn('user_id', $agent_uids)
            ->Where('superior_id', $uid)
            ->Where('bet_user_amount', '>', 0)
            ->groupBy('user_name')
            ->orderBy('bet_user_amount','desc')
            ->take(10)
            ->get()->toArray();
        foreach($rpt_user_list as $user_k => $user_v){
            $bet_max_list[] = [
                'name' => $user_v->user_name,
                'dml'  => $user_v->dml,
                'profit' => bcsub($user_v->bet_user_amount, $user_v->prize_user_amount, 2),
            ];
        }

        $data = [
            'agent_info'   => $agent_info,
            'profit_info'  => $profit_info,
            'game_list'    => $game_list,
            'bet_max_list' => $bet_max_list,
        ];

        return $data;
    }
};