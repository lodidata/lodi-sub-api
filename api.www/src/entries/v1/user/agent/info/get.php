<?php

use Utils\Www\Action;

return new class extends Action
{
    const TOKEN = true;
    const TITLE = "个人中心-我的团队-详情";
    const DESCRIPTION = "当team=1时查团队详情，当team为0或者不传是查个人详情";
    const TAGS = "代理返佣";
    const QUERY = [
        "team"          => "enum[1,0](,1) #是否为团队 1 团队详情，0 个人详情",
        "start_time"    => "date() #开始日期 2021-08-12",
        "end_time"      => "date() #结束日期 2021-08-20",
    ];
    const SCHEMAS = [
        'user_name'         => "string(required) #用户名",
        'sum_inferisors'    => "int(required) #团队人数",
        'sum_deposit'       => "int(required) #充值总额 -- 单位分",
        'sum_withdraw'      => "int(required) #提现总额 -- 单位分",
        'sum_pay'           => "int(required) #累计投注 -- 单位分",
        'sum_bkge'          => "int(required) #累计返佣 -- 单位分",
        'sum_prize'         => "int(required) #累计中奖 -- 单位分",
        'win_loss'          => "int(required) #投注盈亏 -- 单位分",
        'active_amount'     => "int(required) #活动彩金 -- 单位分",
        'sum_return'        => "int(required) #回水金额 -- 单位分",
        'game_bkge_list'    => [
            [
                'type_name' => "string(required) #游戏名称",
                "type"      => "string(required) #游戏模式简称",
                'bkge'      => "int(required) #投注金额 -- 单位分",
                'rake_back' => "int(required) #返佣比例 如10%返回10",
                'total_bet' => "int(required) #投注金额 -- 单位分",
            ]
        ]
    ];

    public function run($uid) {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $team = $this->request->getParam('team') ?: 0;
        $start_time = $this->request->getParam('start_time') ?: date('Y-m-d');
        $end_time = $this->request->getParam('end_time') ?: date('Y-m-d'.' 23:59:59');
        $uid_agent = $this->auth->getUserId();
        return $team ? $this->getTeamDetails($uid, $uid_agent,$start_time,$end_time) : $this->getAccountDetails($uid, $uid_agent,$start_time,$end_time);
    }

    /**
     * 个人详情
     * @param $uid int 用户id
     * @param $uid_agent  int 代理id,也就是自己
     * @param $start_time
     * @param $end_time
     * @return array
     */
    private function getAccountDetails($uid, $uid_agent,$start_time,$end_time) {
        // 通过报表查询
        $user_tab = DB::table('rpt_user')
            ->Where('user_id', '=', $uid)
            ->Where('count_date', '>=', $start_time)
            ->Where('count_date', '<=', $end_time)
            ->first([
                'user_name as name',
                \DB::raw("sum(deposit_user_amount)*100 as sum_deposit"),
                \DB::raw("sum(withdrawal_user_amount)*100 as sum_withdraw"),
                \DB::raw("sum(bet_user_amount)*100 as sum_pay"),
                \DB::raw("sum(bet_user_amount)*100 as order_amount"),
                \DB::raw("sum(prize_user_amount)*100 as sum_prize"),
                \DB::raw("sum(coupon_user_amount)*100 as active_amount"),
                \DB::raw("sum(return_user_amount)*100 as rebet_amount"),
            ]);
        $user_tab->name = $user_tab->name ?? \Model\User::where('id',$uid)->value('name');
        $user_tab->bkge_json = \DB::table('user_agent')->where('user_id',$uid)->value('bkge_json');
        // 返佣给 这个上级的收益
        $bkge_tab_list = (array)DB::table('bkge')
            ->where('user_id', '=', $uid)
            ->where('bkge_uid', '=', $uid_agent)  //被返佣用户ID
            ->where('day', '>=', $start_time." 00:00:00")
            ->where('day', '<=', $end_time." 23:59:59")
            ->groupBy('game')
            ->select([
                // 游戏 type
                'game',
                // 投注金额
                DB::raw('sum(bet_amount) as bet_amount'),
                // 累计回水
                DB::raw('sum(bkge) as bkge'),
            ])
            ->get()->toArray();

        // 游戏
        $rpt_tab = DB::table('rpt_agent as rpt');
        // 时间
        if ($start_time) $rpt_tab->whereDate('rpt.count_date', '>=', $start_time);
        if ($end_time) $rpt_tab->whereDate('rpt.count_date', '<=', $end_time);

        $rpt_tab->agent_cnt = \Model\UserAgent::where('user_id',$uid)->value('inferisors_all');

        $total_bkge = 0;
        foreach ($bkge_tab_list as $item) {
            // 累计返佣
            $total_bkge += $item->bkge;
        }
        // 个游戏投注 返佣设置
        $bkge_json = json_decode($user_tab->bkge_json, true);
        $game = \Model\Admin\GameMenu::where('pid', 0)->get(['name', 'type',])->toArray();
        // 总投注的游戏
        $bkge_game_list = array_column($bkge_tab_list, NULL, 'game');
        // 游戏列表
        $game_list = array_column($game, NULL, 'type');
        $game_bkge_list = [];
        unset($bkge_json['agent_switch']);
        foreach ($bkge_json as $type => $pos) {
            $bet_amount = isset($bkge_game_list[$type]) ? $bkge_game_list[$type]->bet_amount : 0;
            $bkge = isset($bkge_game_list[$type]) ? $bkge_game_list[$type]->bkge : 0;
            $game_bkge_list[] = [
                'type_name' => $game_list[$type]['name'],
                'type' => $type,
                'bkge' => $bkge,
                'rake_back' => $pos,
                'total_bet' => $bet_amount
            ];
        }
        return [
            // 用户名
            'user_name' => $user_tab->name,
            // 团队人数
            'sum_inferisors' => intval($rpt_tab->agent_cnt),
            // 充值总额
            'sum_deposit' => intval($user_tab->sum_deposit),
            // 提现总额
            'sum_withdraw' => intval($user_tab->sum_withdraw),
            // 累计投注
            'sum_pay' => intval($user_tab->sum_pay),
            // 累计返佣
            'sum_bkge' => intval($total_bkge),
            // 累计中奖
            'sum_prize' => intval($user_tab->sum_prize),
            // 投注盈亏
            'win_loss' => intval($user_tab->sum_prize - $user_tab->order_amount),
            // 活动彩金
            'active_amount' => intval($user_tab->active_amount),
            // 回水金额
            'sum_return' => intval($user_tab->rebet_amount),
            // 不同第三方
            'game_bkge_list' => $game_bkge_list
        ];
    }

    /**
     * 团队详情
     * @param $uid
     * @param $uid_agent
     * @param $start_time
     * @param $end_time
     * @return array
     */
    private function getTeamDetails($uid, $uid_agent,$start_time,$end_time) {
        $rpt_tab = DB::table('rpt_agent as rpt');
        // 时间
        if ($start_time) $rpt_tab->whereDate('rpt.count_date', '>=', $start_time);
        if ($end_time) $rpt_tab->whereDate('rpt.count_date', '<=', $end_time);
        // 其他
        $rpt_tab = $rpt_tab->where('rpt.agent_id', '=', $uid)
            ->first([
                'agent_name',
                DB::raw('max(rpt.agent_cnt) as agent_cnt'),
                DB::raw('sum(rpt.deposit_agent_amount) as deposit_agent_amount'),
                DB::raw('sum(rpt.withdrawal_agent_amount) as withdrawal_agent_amount'),
                DB::raw('sum(rpt.bet_agent_amount) as bet_agent_amount'),
                DB::raw('sum(rpt.back_agent_amount) as back_agent_amount'),
                DB::raw('sum(rpt.prize_agent_amount) as prize_agent_amount'),
                DB::raw('sum(rpt.coupon_agent_amount) as coupon_agent_amount'),
                DB::raw('sum(rpt.return_agent_amount) as return_agent_amount'),
            ]);
        $rpt_tab->agent_name = $rpt_tab->agent_name ?? \Model\User::where('id',$uid)->value('name');
        $rpt_tab->agent_cnt = \Model\UserAgent::where('user_id',$uid)->value('inferisors_all');
        // 返佣给 这个上级的收益
        $bkge_tab_list = (array)DB::table('bkge')
            ->where('user_id', '=', $uid)
            ->where('bkge_uid', '=', $uid_agent)    //被返佣用户ID
            ->where('day', '>=', $start_time." 00:00:00")
            ->where('day', '<=', $end_time." 23:59:59")
            ->groupBy('game')
            ->select([
                // 游戏 type
                'game',
                // 投注金额
                DB::raw('sum(bet_amount) as bet_amount'),
                // 累计回水
                DB::raw('sum(bkge) as bkge'),
            ])
            ->get()->toArray();

        // 用户返佣设置
        $user_agent = DB::table('user_agent')
            ->where('user_id', '=', $uid)
            ->first(['bkge_json']);

        $bkge_json = json_decode($user_agent->bkge_json, true);
        $game = \Model\Admin\GameMenu::where('pid', 0)->get(['name', 'type',])->toArray();
        // 总投注的游戏
        $bkge_game_list = array_column($bkge_tab_list, NULL, 'game');
        // 游戏列表
        $game_list = array_column($game, NULL, 'type');
        $game_bkge_list = [];

        foreach ($bkge_json ?? [] as $type => $pos) {
            $bet_amount = isset($bkge_game_list[$type]) ? $bkge_game_list[$type]->bet_amount : 0;
            $bkge = isset($bkge_game_list[$type]) ? $bkge_game_list[$type]->bkge : 0;
            $game_bkge_list[] = [
                'type_name' => $game_list[$type]['name'],
                'type' => $type,
                'bkge' => $bkge,
                'rake_back' => $pos,
                'total_bet' => $bet_amount
            ];
        }

        return [
            // 用户名
            'user_name' => $rpt_tab->agent_name,
            // 团队人数
            'sum_inferisors' => $rpt_tab->agent_cnt,
            // 充值总额
            'sum_deposit' => intval($rpt_tab->deposit_agent_amount * 100),
            // 提现总额
            'sum_withdraw' => intval($rpt_tab->withdrawal_agent_amount * 100),
            // 累计投注
            'sum_pay' => intval($rpt_tab->bet_agent_amount * 100),
            // 累计返佣
            'sum_bkge' => intval($rpt_tab->back_agent_amount * 100),
            // 累计中奖
            'sum_prize' => intval($rpt_tab->prize_agent_amount * 100),
            // 投注盈亏
            'win_loss' => intval(($rpt_tab->prize_agent_amount - $rpt_tab->bet_agent_amount) * 100),
            // 活动彩金
            'active_amount' => intval($rpt_tab->coupon_agent_amount * 100),
            // 回水金额
            'sum_return' => intval($rpt_tab->return_agent_amount * 100),
            // 不同第三方
            'game_bkge_list' => $game_bkge_list
        ];
    }
};