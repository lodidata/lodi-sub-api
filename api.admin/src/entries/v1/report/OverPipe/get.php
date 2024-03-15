<?php

use Logic\Admin\BaseController;

return new class() extends BaseController
{
    const TITLE = '经营详情中的详情';
    const QUERY = [
        'date' => 'date() #日期',
    ];

    const PARAMS = [];
    const SCHEMAS = [
            [
                'game_cnt' => 'int #注单数',
                'game_bet' => 'int #注单金额',
                'game_valid_bet' => 'int #有效注单金额',
                'game_prize' => 'int #派奖金额',
            ]
    ];
    protected $tables = [

    ];
    //前置方法
    protected $beforeActionList = [
       'verifyToken',
       'authorize',
    ];

    public function run()
    {
        $start_date = $this->request->getParam('start_date',date('Y-m-d',strtotime("-1 day")));
        $end_date = $this->request->getParam('end_date',date('Y-m-d',strtotime("-1 day")) .' 23.59.59');
        if($end_date > date('Y-m-d')) $end_date = date('Y-m-d',strtotime("-1 day")) .' 23.59.59';
        $game_type = $this->request->getParam('game_type','');

        if(!$game_type) return ['data'=>[]];
        $class = 'Logic\GameApi\Order\\'.strtoupper($game_type);
        if(!class_exists($class) || !method_exists($class, 'orderOverPipe')) return ['data'=>[]];

        $res = (new $class($this->ci))->orderOverPipe($start_date,$end_date);
        return $res;
    }

};