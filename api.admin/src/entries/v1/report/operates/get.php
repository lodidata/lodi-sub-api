<?php

use Logic\Admin\BaseController;

return new class() extends BaseController
{
    const TITLE = '经营报表';
    const QUERY = [
        'start_time' => 'string() #开始日期',
        'end_time' => 'string() #结束日期',
        'game_type' => 'string() #查询的游戏type  不传则为所有的游戏',
    ];

    const PARAMS = [];
    const SCHEMAS = [
            [
                'game_order_user' => 'int #有效用户数',
                'game_order_cnt' => 'int #注单数',
                'game_bet_amount' => 'int #注单金额',
                'game_prize_amount' => 'int #派奖金额',
                'game_code_amount' => 'int #总打码量',
                'game_deposit_amount' => 'int #总转入',
                'game_withdrawal_amount' => 'int #总转出',
            ]
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run()
    {
        $game_ids = $this->request->getParam('game_type');
        $stime = $this->request->getParam('start_time');
        $etime = $this->request->getParam('end_time');
        $query = \DB::connection('slave')->table('rpt_order_amount');
        $query2 = \DB::connection('slave')->table('rpt_order_user');
        $games = $game_ids ? explode(',',$game_ids) : [];
        $games && $query->whereIn('game_type',$games) && $query2->whereIn('game_type',$games);
        $stime && $query->where('count_date','>=',$stime) && $query2->where('count_date','>=',$stime);
        $etime && $query->where('count_date','<=',$etime) && $query2->where('count_date','<=',$etime);
        $res = (array)$query->first([
            \DB::raw('sum(game_order_cnt) as game_order_cnt'),
            \DB::raw('sum(game_bet_amount) as game_bet_amount'),
            \DB::raw('sum(game_code_amount) as game_code_amount'),
            \DB::raw('sum(game_prize_amount) as game_prize_amount'),
            \DB::raw('sum(game_deposit_amount) as game_deposit_amount'),
            \DB::raw('sum(game_withdrawal_amount) as game_withdrawal_amount'),
        ]);
        if(!$res) return $this->lang->set(0);
        $res['game_order_cnt'] = $res['game_order_cnt'] ?? '0';
        $res['game_bet_amount'] = $res['game_bet_amount'] ?? '0';
        $res['game_prize_amount'] = $res['game_prize_amount'] ?? '0';
        $res['game_code_amount'] = $res['game_code_amount'] ?? '0';
        $res['game_deposit_amount'] = $res['game_deposit_amount'] ?? '0';
        $res['game_withdrawal_amount'] = $res['game_withdrawal_amount'] ?? '0';
        $res['game_order_profit'] = sprintf("%0.2f",$res['game_bet_amount'] - $res['game_prize_amount']);
        $res['game_order_user'] = $query2->distinct()->count('user_id') ?? '0';
        return $res;
    }

};