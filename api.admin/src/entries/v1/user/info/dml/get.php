<?php

use Logic\Admin\BaseController;
use Model\RptUser;

return new class() extends BaseController
{
    const TITLE       = '用户打码量';
    const DESCRIPTION = '用户打码量';

    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run($id=null)
    {
        $this->checkID($id);

        $date_start = $this->request->getParam('date_start',date('Y-m-d',strtotime('-6day')));
        $date_end = $this->request->getParam('date_end',date('Y-m-d'));

        $game_menu = DB::table('game_menu')
            ->selectRaw('type,`rename` as name')
            ->where('pid','<>',0)
            /*->where('pid','<>',23)*/
            ->where('id','<>',27)
            ->where('switch', 'enabled')
            ->get()->toArray();

        if($game_menu){
            foreach ($game_menu as &$v){
                $v->name = $this->lang->text($v->type);
            }
            unset($v);
        }


        $dates = RptUser::selectRaw('count_date date')
            ->where('user_id',$id)
            ->where('bet_user_amount','>',0)
            ->where('count_date','>=',$date_start)
            ->where('count_date','<=',$date_end)
            ->orderByDesc('count_date')
            ->get()
            ->toArray();

        if($dates){

            $dml_logs = \DB::table('order_game_user_middle')->selectRaw('sum(dml) as dml,`date`,game_type')
                ->where('user_id',$id)
                ->where('date','>=',$date_start)
                ->where('date','<=',$date_end)
                ->groupBy(['game_type','date'])
                ->get()
                ->toArray();

            foreach($dates as &$date){
                $date = (array)$date;
                $date['dml'] = 0;
                foreach ($dml_logs as $dml_log){
                    $dml_log = (array)$dml_log;
                    if($dml_log['date'] == $date['date']){
                        $date['dml'] += $dml_log['dml'];
                        $date['logs'][] = $dml_log;
                    }
                }
            }
            unset($date);

        }
        $data['game_menu'] = $game_menu;
        $data['dml_logs'] = $dates;
        return (array)$data;
    }

};
