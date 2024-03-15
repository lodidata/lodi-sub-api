<?php
/**
 * Created by PhpStorm.
 * User: Nico
 * Date: 2018/6/22
 * Time: 14:06
 */
use Logic\Admin\BaseController;


return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '盈亏报表';
    const DESCRIPTION = '获取盈亏报表';
    
    const QUERY       = [
        'page'        => 'int()   #页码',
        'page_size'   => 'int()    #每页大小',
        'lottery_id' => 'string()    #彩种id',
        'user_name'    => 'string()   #用户名',
        'update_at'       => 'string() #更新时间',
    ];
    
    const PARAMS      = [];
    const SCHEMAS     = [
        [
            'type'  => 'enum[rowset, row, dataset]',
            'size'  => 'unsigned',
            'total' => 'unsigned',
            'data'  => [
                "total_earnlose"=>"string #输赢",
                "bet_num"=>"string #投注笔数",
                "bet_money"=>"string #投注金额",
                "send_money"=>"string #派奖金额",
                "name"=>"# 账号",
                "id"=>"# 序号",
            ]
        ],
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];
    private $reportDB;
    public function run()
    {
        $this->reportDB = \DB::connection('slave');

        $params = $this->request->getParams();
        $page = isset($params['page']) ? $params['page'] : 1;
        $size = isset($params['page_size']) ? $params['page_size'] : 20;

        $start = ($page-1)*$size;


        $sql = 'select sum(bet_num) bet_num,sum(bet_money) bet_money,sum(send_money) send_money,sum(total_earnlose) * -1 as  total_earnlose,u.`name` 
                from rpt_userlottery_earnlose t1
                left join `user` u on t1.user_id = u.id where 1=1 ';
        if (isset($params['lottery_id'])) $sql .= ' AND  t1.lottery_id='.$params['lottery_id'];

        if (!isset($params['start_time']) && !isset($params['start_time'])){
            $sql .= " AND  t1.count_date='".date("Y-m-d",strtotime("-1 day"))."'";
        }
        if (isset($params['user_name'])) $sql .= "  AND u.name='{$params['user_name']}'";
        $condition = [];

        if (isset($params['start_time'])) $sql .= "  and  t1.count_date>='{$params['start_time']}'";
        if (isset($params['end_time'])) $sql .= " and  t1.count_date<='{$params['end_time']}'";


        $sql .= " group by user_id";



        //排序规则
        if (isset($params['order_by']) && in_array($params['order_by'], ['total_earnlose', 'bet_num', 'bet_money','send_money'])
            &&
            isset($params['order_rule']) && in_array($params['order_rule'], ['desc', 'asc'])
        ) {
            $sql .= " order by ".$params['order_by'].' '.$params['order_rule'];
        }else{
            $sql .= " order by total_earnlose desc";
        }
        //echo $sql;die;
        //$count_sql = $sql;
        $temp=[];
        $temp['bet_num'] = 0;
        $temp['bet_money'] = 0;
        $temp['send_money'] = 0;
        $temp['total_earnlose'] = 0;
        $temp['id'] = 0;
        $temp['name'] = 0;
        $total = $this->reportDB->select($sql);

        $json = json_encode($total);
        foreach ($total as $k=>$v){
            $temp['bet_num'] +=$v->bet_num;
            $temp['bet_money'] += $v->bet_money;
            $temp['send_money'] += $v->send_money;
            $temp['total_earnlose'] +=$v->total_earnlose;
        }
        $temp['name'] = count($total);
        $data = json_decode($json,true);
        $data = array_slice($data,$start,$size);

        foreach ($data as $key=>$val) {
            $data[$key]['id'] = $key+$start+1;
           /* if ($data[$key]['id'] == 0){
                $data[$key]['bet_num'] = $temp['totoal_bet_num'];
                $data[$key]['bet_money'] = $temp['totoal_bet_money'];
                $data[$key]['send_money'] = $temp['totoal_send_money'];
                $data[$key]['total_earnlose'] = $temp['totoal_total_earnlose'];
                $data[$key]['name'] = $temp['totoal_user_num'];
            }*/
        }
        if ($params['page'] == 1) array_unshift($data,$temp);


        $data = array_values($data);
      /*  $sql .= " limit $start,$size";

        $data = \DB::select($sql);
        foreach ($data as $key=>$val){
            $data[$key]->id = $key+$start;

        }*/

        $attributes['total'] = $temp['name'];
        $attributes['size'] = $size;
        $attributes['number'] = $page;
        return $this->lang->set(0,[],$data,$attributes);


    }
};