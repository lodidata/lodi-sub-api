<?php
/**
 * Created by PhpStorm.
 * User: benchan
 * Date: 2019/1/17
 * Time: 14:39
 */


use Logic\Admin\BaseController;

return new  class() extends BaseController {
    const TITLE = '出款渠道统计';
    const DESCRIPTION = '';
    
    const QUERY = [
        'start_time'   => 'datetime(required) #开始日期',
        'end_time'     => 'datetime(required) #结束日期',
        
    ];
    
    const PARAMS = [];
    const SCHEMAS = [
        [

        ],
    ];
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run() {
        $start_time = $this->request->getParam('start_time');
        $end_time = $this->request->getParam('end_time');
        $page = $this->request->getParam('page', 1);
        $page_size = $this->request->getParam('page_size', 20);
        if(empty($start_time) || empty($end_time)){
            return $this->lang->set(10010);
        }

        $query=DB::connection('slave')
                ->table('funds_withdraw as t1')
                ->leftJoin('transfer_order as t2', function ($join){ $join->on('t1.trade_no','=','t2.withdraw_order')->where('t2.status','paid');})
                ->leftJoin('transfer_config as t3','t2.third_id','=','t3.id')
                ->where('t1.status','=','paid')
                ->where('t1.confirm_time','>=',$start_time.' 00:00:00')
                ->where('t1.confirm_time','<=',$end_time.' 23:59:59')

                ->selectRaw("sum(t1.money) as money,count(t1.id) as cnt,count(DISTINCT(t1.user_id)) as user_cnt,ifnull(t2.third_id,0) as third_id,ifnull(t3.`name`,'人工出款') as third_name")
                ->groupBy('t2.third_id');

        $count = clone $query;
        $countRes=$count->get()->toArray();
        $res=$query->forPage($page,$page_size)
                   ->get()->toArray();
        $attributes=[
            'total_money'=>0,
            'total_cnt'=>0,
            'total_user_cnt'=>0,
            'total'=>count($countRes),
            'size'=>$page_size,
            'number'=>$page
        ];
        if(!empty($res)){
            foreach($res as $v){
                $attributes['total_money'] +=$v->money;
                $attributes['total_cnt'] +=$v->cnt;
                $attributes['total_user_cnt'] +=$v->user_cnt;
            }
        }


        return $this->lang->set(0,[],$res,$attributes);
    }
};
