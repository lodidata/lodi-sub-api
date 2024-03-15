<?php
use Utils\Www\Action;
use Model\Admin\ActiveBkge as ActiveBkgeModel;

return new class extends Action
{
    const TOKEN = true;
    const TITLE = "团队概况";
    const DESCRIPTION = "";
    const TAGS = "代理返佣";
    const QUERY = [
        "start_time"    => "date() #开始日期 2021-08-12",
        "end_time"      => "date() #结束日期 2021-08-20",
    ];
    const SCHEMAS = [
        [
            "user_num"                  => "int(required) #下级人数",
            "register_num"              => "int(required) #注册人数",
            "valid_user_num"            => "int(required) #有效用户数",
            "need_valid_user_num"       => "int(required) #需要的有效用户数",
            "winloss"                   => "float(required) #盈亏金额",
            "first_deposit_user_num"    => "int(required) #首充人数",
            "deposit_user_num"          => "int(required) #总充值人数",
            "first_deposit_avg"         => "float(required) #新增人均充值",
            "deposit_amount"            => "float(required) #充值金额",
            "withdraw_amount"         => "float(required) #取款金额",
            "bet_amount"                => "float(required) #投注金额",
            "active_amount"             => "float(required) #活动金额",
        ]
    ];


    public function run(){
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $uid        = $this->auth->getUserId();

        $beginDate  = $this->request->getParam('start_time') ? : date('Y-m-d',strtotime("-1 day"));
        $endDate    = $this->request->getParam('end_time') ? : date('Y-m-d');

        if(strtotime($endDate) - strtotime($beginDate) > 3600*24*31){
            return $this->lang->set(886, ['The query time cannot exceed 31 days']);
        }

        $beginTime  = $beginDate.' 00:00:00';
        $endTime    = $endDate.' 23:59:59';

        $bkgeConfig = \Model\Admin\ActiveBkge::first(['new_bkge_set']);

        $valid_user_deposit = 0;
        $valid_user_bet     = 0;
        $new_bkge_set = [
            'valid_user_num' => 0,
            'valid_user_deposit' => 0,
            'valid_user_bet' => 0,
        ];

        if(!empty($bkgeConfig->new_bkge_set)){
            $new_bkge_set       = json_decode($bkgeConfig->new_bkge_set, true);
            $valid_user_deposit = $new_bkge_set['valid_user_deposit'];
            $valid_user_bet     = $new_bkge_set['valid_user_bet'];
        }

        //关于金额的统计
        $team_money_data = \DB::table('user_agent as ua')
            ->join('rpt_user as ru','ua.user_id','=','ru.user_id','inner')
            ->where('ua.uid_agent',$uid)
            ->where('ru.count_date', '>=', $beginDate)
            ->where('ru.count_date','<=', $endDate)
            ->first([
                \DB::raw('count(distinct if(ru.deposit_user_amount >0,ru.user_id,null)) deposit_user_num'),
                \DB::raw('ifnull(sum(if(ru.first_deposit,deposit_user_amount,0)),0) first_deposit_amount'),
                \DB::raw('ifnull(sum(if(ru.first_deposit,1,0)),0) first_deposit_user_num'),
                \DB::raw('ifnull(sum(prize_user_amount-bet_user_amount),0) winloss'),
                \DB::raw('ifnull(sum(deposit_user_amount),0) deposit_amount'),
                \DB::raw('ifnull(sum(withdrawal_user_amount),0) withdraw_amount'),
                \DB::raw('ifnull(sum(bet_user_amount),0) bet_amount'),
                \DB::raw('ifnull(sum(coupon_user_amount+return_user_amount+turn_card_user_winnings+promotion_user_winnings),0) active_amount'),
            ]);

        $data = [
            'winloss'                   => $team_money_data->winloss,
            'first_deposit_user_num'    => $team_money_data->first_deposit_user_num,
            'deposit_user_num'          => $team_money_data->deposit_user_num,
            'first_deposit_avg'         => $team_money_data->first_deposit_user_num ? bcdiv($team_money_data->first_deposit_amount, $team_money_data->first_deposit_user_num, 2) : 0,
            'deposit_amount'            => $team_money_data->deposit_amount,
            'withdraw_amount'         => $team_money_data->withdraw_amount,
            'bet_amount'                => $team_money_data->bet_amount,
            'active_amount'             => $team_money_data->active_amount,
        ];

        //有效用户数
        $sub_queary = \DB::table('user_agent as ua')->select('ru.user_id')
            ->join('rpt_user as ru','ua.user_id','=','ru.user_id','inner')
            ->where('ua.uid_agent',$uid)
            ->where('ru.count_date', '>=', $beginDate)
            ->where('ru.count_date','<=', $endDate)
            ->groupBy('ru.user_id')
            ->havingRaw("sum(ru.deposit_user_amount) >= {$valid_user_deposit} and sum(ru.bet_user_amount) >={$valid_user_bet}");
        $valid_user_num = \DB::table('t')->fromSUb($sub_queary, 't')
                                ->first([\DB::raw( 'count(t.user_id) valid_user_num')])
                                ->valid_user_num;

        $data['valid_user_num'] = $valid_user_num;

        //下级人数 和 注册人数
        $user_data = \DB::table('user_agent as ua')
            ->leftJoin('user as u','ua.user_id','=','u.id')
            ->where('ua.uid_agent',$uid)
            ->first([
                'uid_agent_name',
                \DB::raw('count(u.id) user_num'),
                \DB::raw("ifnull(sum(if(u.created >= '{$beginTime}' and u.created <= '{$endTime}',1,0)),0) register_num"),
            ]);

        $data['user_num']            = $user_data->user_num;
        $data['register_num']        = $user_data->register_num;
        $data['need_valid_user_num'] = $new_bkge_set['valid_user_num'];

        //整条代理线人数
        if (empty($user_data->uid_agent_name)) {    //测试环境兼容处理：测试环境有的代理线名称为null
            $allAgentNum = 0;
        } else {
            $allAgentNum = DB::table('user_agent')->where('uid_agent_name', $user_data->uid_agent_name)->count();
        }
        $data['all_agent_num'] = $allAgentNum;
        //下线：所有下级人数
        $user_sub_gent = DB::table('user_agent')->select('inferisors_all')->where('user_id', $uid)->first();
        $data['user_sub_agent'] = $user_sub_gent->inferisors_all;


        return $this->lang->set(0, [], $data);
    }
};