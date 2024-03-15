<?php

use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const TITLE = '游戏数据汇总';

    //前置方法
    protected $beforeActionList = [
       'verifyToken',
       'authorize',
    ];

    public function run()
    {

        $start_date = $this->request->getParam('start_date',date('Y-m-d',strtotime("-1 day")));
        $end_date = $start_date .' 23.59.59';
//        if($end_date > date('Y-m-d')) $end_date = date('Y-m-d',strtotime("-1 day")) .' 23.59.59';
        $game_type = $this->request->getParam('game_type','');

        if(!$game_type) return ['data'=>[]];
        $class = 'Logic\GameApi\Order\\'.strtoupper($game_type);
        if(!class_exists($class) || !method_exists($class, 'querySumOrder')) return ['data'=>[]];

        $res = (new $class($this->ci))->querySumOrder($start_date,$end_date);
        return $res;


        $date = $this->request->getParam('start_date',date('Y-m-d',strtotime("-1 day")));
//        $end_date = $this->request->getParam('end_date',date('Y-m-d',strtotime("-1 day")) .' 23.59.59');
//        if($end_date > date('Y-m-d')) $end_date = date('Y-m-d',strtotime("-1 day")) .' 23.59.59';
        $game_type = $this->request->getParam('game_type','');
        $game_types = DB::connection('slave')->table('game_menu')->where('switch', 'enabled')->where('alias',$game_type)->pluck('type');
        $data = \DB::connection('slave')->table('order_game_user_middle')->whereIn('game_type',$game_types)
            ->where('date',$date)
            ->selectRaw("IFNULL(sum(bet),0) as bet,IFNULL(sum(profit),0) as win_loss")
            ->first();
        return (array)$data;

    }

};