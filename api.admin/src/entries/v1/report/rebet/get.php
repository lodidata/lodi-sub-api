<?php

use Logic\Admin\BaseController;

return new class() extends BaseController
{
    const TITLE = '经营报表详情';
    const QUERY = [
        'game_id' => 'string() #游戏ID',
        'start_time' => 'string() #开始日期',
        'end_time' => 'string() #结束日期',
    ];

    const PARAMS = [];
    const SCHEMAS = [
        'data' => [
            [
                'game_id' => 'int #游戏ID',
                'game_name' => 'int #游戏name',
                'win_money' => 'int #盈亏',
                'bet_money' => 'int #投注金额',
                'rebet_money' => 'int #回水金额',
                'rebet_user' => 'int #回水人数',
                'rebet_count' => 'int #回水次数',
            ]
        ],
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run()
    {
        $stime = $this->request->getParam('start_time');
        $etime = $this->request->getParam('end_time');
        $game_id = $this->request->getParam('game_id');
        $query = \DB::connection('slave')->table('rebet');
        $stime && $query->where('day','>=',$stime);
        $etime && $query->where('day','<=',$etime);
        if($game_id){
            $game_id = in_array($game_id,[26,27]) ? 0 : $game_id;
            $query->where('plat_id','=',$game_id);
        }
        $data = $query->groupBy('plat_id')->orderBy('id','DESC')->get([
            'plat_id AS game_id',
            \DB::raw('sum(win_money) as win_money'),
            \DB::raw('sum(bet_amount) as bet_money'),
            \DB::raw('sum(rebet) as rebet_money'),
            \DB::raw('count(DISTINCT user_id ) as rebet_user'),
            \DB::raw('count(1) as rebet_count'),
        ])->toArray();
        if(!$data) return $this->lang->set(0);
        $games = \Model\Admin\GameMenu::where('pid','!=',0)->where('switch','enabled')->get(['id','name'])->toArray();
        $games = array_column($games,NULL,'id');
        foreach ($data as &$val) {
            $val = (array)$val;
            if($val['game_id'] == 0) {
                $val['game_name'] = 'TG彩票';
            }else {
                $val['game_name'] =$games[$val['game_id']]['name'] ?? $val['game_id'];
            }
            $val['win_money'] = $val['win_money'] ?? 0;
            $val['bet_money'] = $val['bet_money'] ?? 0;
            $val['rebet_money'] = $val['rebet_money'] ?? 0;
            $val['rebet_user'] = $val['rebet_user'] ?? 0;
            $val['rebet_count'] = $val['rebet_count'] ?? 0;
        }
        return $data;
    }

};