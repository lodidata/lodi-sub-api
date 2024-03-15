<?php

use Utils\Www\Action;

return new class extends Action
{
    const TOKEN = true;
    const TITLE = '个人报表-返水记录';
    const TAGS = "个人报表";
    const DESCRIPTION = " 返回attributes[number =>'第几页', 'size' => '记录条数'， total=>'总记录数'，total_rebet_money => '列表总金额'，total_today_money => '今天返水总金额]";
    const QUERY = [
        'start_time' => 'string() #开始日期',
        'end_time' => 'string() #结束日期',
    ];

    const PARAMS = [];
    const SCHEMAS = [
        'data' => [
            [
                'day'       => 'string #日期',
                'game_name' => 'int #游戏name',
                'bet_money' => 'int #投注金额',
                'rebet_money' => 'int #回水金额',
            ]
        ],
    ];


    public function run()
    {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $user_id = $this->auth->getUserId();
        $stime = trim($this->request->getParam('start_time'));
        $etime = trim($this->request->getParam('end_time'));
        $pageSize = intval($this->request->getParam('page_size'));
        $page = intval($this->request->getParam('page'));
        !$page     && $page = 1;
        !$pageSize && $pageSize = 20;
        $today = date('Y-m-d');
        !$stime && $stime = $today;
        !$etime && $etime = $today;
        $query = \DB::table('rebet');
        $query->where('user_id','=',$user_id);
        $query->where('rebet','>',0);
        $query2 = clone $query;
        $query2->where('day',$today);
        $total_today_money = (float)$query2->get([ \DB::raw('sum(rebet) as total_bet_money')])->toArray()[0]->total_bet_money;
        $query->where('day','>=',$stime);
        $query->where('day','<=',$etime);
        $total_rebet_money = (float)$query->get([ \DB::raw('sum(rebet) as total_bet_money')])->toArray()[0]->total_bet_money;
        $data = $query->orderBy('id','DESC')->select([
            'plat_id AS game_id',
            \DB::raw('bet_amount as bet_money'),
            \DB::raw('rebet as rebet_money'),
            'day'
        ])->paginate($pageSize, ['*'], 'page', $page);
        $total = $data->total();
        $data = $data->toArray()['data'];
        if(!$data) return $this->lang->set(0);
        $games = \Model\Admin\GameMenu::where('pid','!=',0)->get(['id','name'])->toArray();
        $games = array_column($games,NULL,'id');
        foreach ($data as &$val) {
            $val = (array)$val;
            if($val['game_id'] == 0) {
                $val['game_name'] = 'TG彩票';
            }else {
                $val['game_name'] =$games[$val['game_id']]['name'] ?? $val['game_id'];
            }
            $val['bet_money'] = (float)$val['bet_money'] ?? 0;
            $val['rebet_money'] = (float)$val['rebet_money'] ?? 0;
            unset($val['game_id']);
        }
        unset($val);

        return $this->lang->set(0, [], $data, ['number' => $page, 'size' => $pageSize, 'total' => $total, 'total_rebet_money' => $total_rebet_money, 'total_today_money' => $total_today_money]);
    }
    /*public function run()
    {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $user_id = $this->auth->getUserId();
        $stime = trim($this->request->getParam('start_time'));
        $etime = trim($this->request->getParam('end_time'));
        $pageSize = intval($this->request->getParam('page_size'));
        $page = intval($this->request->getParam('page'));
        !$page     && $page = 1;
        !$pageSize && $pageSize = 20;
        !$stime && $stime = date('Y-m-d');
        !$etime && $etime = date('Y-m-d');
        $query = \DB::table('rebet');
        $query->where('user_id','=',$user_id);
        $query->where('day','>=',$stime);
        $query->where('day','<=',$etime);
        $total_rebet_money = (float)$query->get([ \DB::raw('sum(rebet) as total_bet_money')])->toArray()[0]->total_bet_money;
        $data = $query->groupBy('plat_id')->orderBy('id','DESC')->select([
            'plat_id AS game_id',
            \DB::raw('sum(bet_amount) as bet_money'),
            \DB::raw('sum(rebet) as rebet_money'),
            'day'
        ])->paginate($pageSize, ['*'], 'page', $page);
        $total = $data->total();
        $data = $data->toArray()['data'];
        if(!$data) return $this->lang->set(0);
        $games = \Model\Admin\GameMenu::where('pid','!=',0)->get(['id','name'])->toArray();
        $games = array_column($games,NULL,'id');
        foreach ($data as &$val) {
            $val = (array)$val;
            if($val['game_id'] == 0) {
                $val['game_name'] = 'TG彩票';
            }else {
                $val['game_name'] =$games[$val['game_id']]['name'] ?? $val['game_id'];
            }
            $val['bet_money'] = (float)$val['bet_money'] ?? 0;
            $val['rebet_money'] = (float)$val['rebet_money'] ?? 0;
            unset($val['game_id']);
        }
        return $this->lang->set(0, [], $data, ['number' => $page, 'size' => $pageSize, 'total' => $total, 'total_rebet_money' => $total_rebet_money]);
    }*/

};