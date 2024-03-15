<?php

use Logic\Admin\BaseController;

return new class() extends BaseController
{
    const TITLE = '经营详情中的详情';
    const QUERY = [
        'start_date' => 'string() #开始日期',
        'end_date' => 'string() #结束日期',
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
    protected $tables = [

    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run()
    {
        $page = $this->request->getParam('page',1);
        $size = $this->request->getParam('size',20);
        $game_type = $this->request->getParam('game_type');
        $start_date = $this->request->getParam('start_date',date('Y-m-d H:i:s',strtotime("-30 day")));
        $end_date = $this->request->getParam('end_date',date('Y-m-d H:i:s'));
        $end_date = $end_date .' 23.59.59';
        $class = 'Logic\GameApi\Order\\'.strtoupper($game_type);
        if(class_exists($class)){
            $obj = new $class($this->ci);
            $res = $obj->getOrderGroupUser($start_date,$end_date,$page,$size);
            if($res['data']){
                foreach ($res['data'] as &$val) {
                    $val = (array)$val;
                }
                unset($val);
            }
            return $this->lang->set(0,[],$res['data'],$res['attr']);
        }

        $query = \DB::connection('slave')->table('send_prize')
            ->leftJoin("user",'user.id','=',"send_prize.user_id")
            ->where('send_prize.created','>=',$start_date)
            ->where('send_prize.created','<=',$end_date)
            ->whereNotIn('user.tags',[4,7])
            ->groupBy('user_id');
        $total = clone $query;
        $data = $query->forPage($page,$size)->get([
            'send_prize.user_id',
            'send_prize.user_name',
            \DB::raw('count(1) as bet_count'),
            \DB::raw('sum(send_prize.pay_money) as bet'),
            \DB::raw('sum(send_prize.pay_money) as valid_bet'),
            \DB::raw('sum(send_prize.money) as send_prize'),
            \DB::raw('-sum(send_prize.lose_earn) AS win_loss'),
        ])->toArray();
        $attr = [
            'total' => $total->pluck('user_id')->count(),
            'number' => $page,
            'size' => $size,
        ];
        return $this->lang->set(0,[],$data,$attr);
    }

};