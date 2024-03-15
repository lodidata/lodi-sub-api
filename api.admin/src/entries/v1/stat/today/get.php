<?php
/**
 * vegas2.0
 *
 * @auth *^-^*<dm>
 * @copyright XLZ CO.
 * @package
 * @date 2017/4/10 11:05
 */
use Logic\Admin\BaseController;
use Model\Label;
return new class() extends BaseController{
//    const STATE       = \API::DRAFT;
    const TITLE       = '获取今日用户、金额等统计';
    const DESCRIPTION = '';
    
    const QUERY       = [];
    
    const PARAMS      = [];
    const SCHEMAS = [];

    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run()
    {
//        $parmas = [
//            'select'    => 'new_members,active_members,online_members,deposit_money,withdraw_money,bet_times,bet_money,gross_profit',
//            'condition' => [
//                'date_from' => date('Y-m-d 00:00:00'),
//                'date_to'   => date('Y-m-d 23:59:59')
//            ]
//        ];
        $labelModel = new Label();
        $tags = $labelModel->getIdByTags('试玩');
        $tags2 = $labelModel->getIdByTags('测试');//

        $start = date('Y-m-d 00:00:00');
        $end = date('Y-m-d 23:59:59');
        $sql1 = "select COUNT(DISTINCT o.user_id) as c  from `send_prize` o left JOIN  user u on u.id = o.user_id where o.created >= '$start' and o.created <= '$end' and u.tags not in($tags,$tags2) ";//活跃用户
        //echo $sql1;exit;
        $sql2 = "select count(id) as c from user where created >= '$start' and created <= '$end'  and tags not in($tags,$tags2) ";//新增用户

       // echo $sql2;exit;
        $sql3 = "SELECT COUNT(DISTINCT l.user_id) as c  FROM user_logs l left join user u on u.id=l.user_id  WHERE l.created >= '$start' and l.created <= '$end' AND l.log_type = 1  and u.tags not in($tags,$tags2)"; //上线用户
        //echo $sql3;

        $sql4 = "select sum(f.deal_money) from funds_deal_log f left join user u on u.id = f.user_id where f.deal_type in(101,102,106) and  f.created >= '$start' and f.created <= '$end'  and u.tags not in($tags,$tags2)"; //存款

        //echo $sql4;
        $sql5 = "select count(o.id) as c  from `send_prize` o left join user u on u.id = o.user_id where o.created >= '$start' and o.created <= '$end'   and u.tags not in($tags,$tags2)";//注单数

        $sql6 ="select sum(o.pay_money) as money  from `send_prize` o left join user u on u.id=o.user_id where o.created >= '$start' and o.created <= '$end' and u.tags not in($tags,$tags2)";

        $sql ="select ($sql1) as a,($sql2) as b,($sql3) as c,($sql4) as d,($sql5) as e ,($sql6) as f from dual";
//        echo $sql;exit;
        $data = DB::select("$sql");

        $data= (array)$data[0];
        return  [
            'new_members'    => $data['b'],
            'active_members' =>  $data['a'],
            'online_members' => $data['c'],
            'deposit_money'  => $data['d'] ?? 0,
            'withdraw_money' => 0,
            'bet_times'      =>  $data['e'],
            'bet_money'      => $data['f'] ?? 0,
            'gross_profit'   => 0
        ];

    }
};
