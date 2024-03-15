<?php

use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const TITLE = '注单查询--彩票标准/快速';
    const DESCRIPTION = '';
    
    const QUERY = [
        'play_type' => 'string() #(fast=快速,std=标准, chat聊天, video视频,all=所有)',
        'user_name' => 'string #用户名',
        'order_number' => 'string #注单号',
        'lottery_id' => 'int #彩种id，见彩种列表',
        'lottery_number' => 'string #期号',
        'order_origin' => 'int #来源，1 pc，2 h5，3 app',
        'start_time' => 'datetime #查询时间，开始',
        'end_time' => 'datetime #查询时间，结束',
        'page' => 'int #条数',
        'page_size' => 'int #页码'
    ];
    
    const PARAMS = [];
    const SCHEMAS = [
        [
            "id" => "45",
            "created" => "2018-02-06 15 =>41 =>10",
            "unix_created" => "1517902870",
            "order_number" => "2018020603411045879",
            "user_name" => "ni",
            "lottery_id" => "2",
            "origin" => "2",
            "chase_number" => "0",
            "lottery_number" => "871175",
            "odds" => [
                "单" => "2.00",
                "双" => "2.00",
                "大" => "2.00",
                "小" => "2.00",
                "大单" => "4.33",
                "大双" => "3.72",
                "小单" => "3.72",
                "小双" => "4.33",
                "极大" => "17.86",
                "极小" => "17.86"
            ],
            "room_id" => "13",
            "lottery_name" => "北京幸运28",
            "play_group" => "大小单双",
            "play_name" => "大小单双",
            "pay_money" => "1000",
            "bet_num" => "10",
            "one_money" => "100",
            "times" => "1",
            "play_number" => "大|小|单|双|小单|小双|大单|大双|极小|极大",
            "p_money" => null,
            "lose_earn" => null,
            "state" => "",
            "marks" => "",
            "agent" => null,
            "period_code" => null,
            "hall_id" => null,
            "hall_name" => "",
            "state2" => "未结算"
        ]
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run()
    {

        $params = $this->request->getParams();

        $params['no_include_tags'][] = DB::table('label')->where('title','试玩')->value('id');
        $params['no_include_tags'][] = DB::table('label')->where('title','测试')->value('id');

        return $this->getLotteryRecords($params,$params['page'],$params['page_size']);


    }


    protected function getLotteryRecords($params, $page = 1, $size = 10){

        $query = DB::table('lottery_order as lo')
            ->leftJoin('lottery as l','lo.lottery_id','=','l.id')
//            ->leftJoin('send_prize as s','lo.order_number','=','s.order_number')
            ->leftJoin('user as u','lo.user_id','=','u.id')
            ->whereNotIn('u.tags',[4,7])
            ->where('lo.chase_number','<=',0);
//            ->leftJoin('agent as a','u.agent_id','=','a.id')
//            ->leftJoin('lottery_info as li',function($q){
//                $q->on('li.lottery_number','=','u.id')
//                    ->on('li.lottery_type','=','lo.lottery_id');
//            });
//            ->leftJoin('hall as h','h.id','=','lo.hall_id')
//            ->leftJoin('room as r','r.id','=','lo.room_id');



        $where = [];
        isset($params['order_status']) && $where[] = "find_in_set('{$params['order_status']}', lo.state)";
        isset($params['opposite_status']) && $where[] = " !find_in_set('{$params['opposite_status']}', lo.state)";
        isset($params['opposite_status2']) && $where[] = " !find_in_set('{$params['opposite_status2']}', lo.state)";


        $query = isset($params['order_status']) && is_numeric($params['order_status']) ? $query->whereRaw("find_in_set('{$params['order_status']}', lo.state)") : $query;
        $query = isset($params['opposite_status']) && is_numeric($params['opposite_status']) ? $query->whereRaw("!find_in_set('{$params['opposite_status']}', lo.state") : $query;
        $query = isset($params['opposite_status2']) && is_numeric($params['opposite_status2']) ? $query->whereRaw("!find_in_set('{$params['opposite_status2']}', lo.state)") : $query;


        if (isset($params['play_type']) && !empty($params['play_type'])) {
            switch ($params['play_type']) {
                case 'std':
                    $where[] = " !find_in_set('std', lo.state)";
                    $query = $query->whereRaw("!find_in_set('std', lo.state)");
                    break;
                case 'fast':
                    $where[] = " find_in_set('fast', lo.state)";
                    $query = $query->whereRaw("find_in_set('fast', lo.state)");
                    break;
                case 'chat':
                    $where[] = " find_in_set('chat', lo.state)";
                    $query = $query->whereRaw("find_in_set('chat', lo.state)");
                    break;
                case 'vedio':
                    $where[] = " find_in_set('vedio', lo.state)";
                    $query = $query->whereRaw("find_in_set('vedio', lo.state)");
                    break;
                case 'all':
                    $query = $query->whereRaw("FIND_IN_SET('{$params['play_type']}',lo.state)");
                    break;
                default:
                    $where[] = " find_in_set('{$params['play_type']}', d.state)";
                    $query = $query->whereRaw("find_in_set('{$params['play_type']}', d.state)");
                    break;
            }
        }
        if (isset($params['state']) && !empty($params['state'])) {
            switch ($params['state']) {
                case 'open':
                    $where[] = " find_in_set('open', lo.state)";
                    $query = $query->whereRaw("find_in_set('open', lo.state)");
                    break;
                case 'noopen':
                    $where[] = " lo.state = ''";
                    $query = $query->whereRaw("lo.state = ''");
                    break;
                case 'canceled':
                    $where[] = " lo.state like '%canceled' ";
                    $query = $query->whereRaw("lo.state like '%canceled'");
                    break;
                case 'all':
                    break;
                default:
                    break;
            }
        }
//        isset($params['user_id']) && $where[] = " lo.user_id = '{$params['user_id']}'";
        $query = isset($params['user_id']) && !empty($params['user_id']) ? $query->whereRaw("lo.user_id = '{$params['user_id']}'") : $query;

//        isset($params['user_name']) && $where[] = " locate('{$params['user_name']}', lo.user_name) > 0";
        $query = isset($params['user_name']) && !empty($params['user_name']) ? $query->whereRaw("locate('{$params['user_name']}', lo.user_name) > 0") : $query;

        isset($params['order_number']) && $where[] = " lo.order_number like '{$params['order_number']}%'";
        $query = isset($params['order_number']) && !empty($params['order_number']) ? $query->whereRaw("lo.order_number like '{$params['order_number']}%'") : $query;

        isset($params['lottery_id']) && $where[] = " l.`id` = '{$params['lottery_id']}'";
        $query = isset($params['order_number']) && !empty($params['order_number']) ? $query->whereRaw("l.`id` = '{$params['lottery_id']}'") : $query;

        isset($params['play_id']) && $where[] = " lo.play_id = '{$params['play_id']}'";
        $query = isset($params['play_id']) && !empty($params['play_id']) ? $query->whereRaw("lo.play_id = '{$params['play_id']}'") : $query;

        isset($params['lottery_number']) && $where[] = " lo.lottery_number = '{$params['lottery_number']}'";
        $query = isset($params['lottery_number']) && !empty($params['lottery_number']) ? $query->whereRaw("lo.lottery_number = '{$params['lottery_number']}'") : $query;

        isset($params['order_origin']) && $where[] = " lo.origin = '{$params['order_origin']}'";
        $query = isset($params['order_origin']) && !empty($params['order_origin']) ? $query->whereRaw("lo.origin = '{$params['order_origin']}'") : $query;
        isset($params['start_time']) && $where[] = " lo.created >= '{$params['start_time']}'";
        $query = isset($params['start_time']) && !empty($params['start_time']) ? $query->whereRaw(" lo.created >= '{$params['start_time']}'") : $query;

        isset($params['pc_start_time']) && $where[] = " lo.created >= FROM_UNIXTIME({$params['pc_start_time']})";
        $query = isset($params['pc_start_time']) && !empty($params['pc_start_time']) ? $query->whereRaw("lo.created >= FROM_UNIXTIME({$params['pc_start_time']})") : $query;
        if (isset($params['end_time']) && !empty($params['end_time'])) {
            $params['end_time'] = date('Y-m-d 23:59:59', strtotime($params['end_time']));
            $query->whereRaw("lo.created <= '{$params['end_time']}'");
        }
        isset($params['pc_end_time']) && $where[] = " lo.created <= FROM_UNIXTIME({$params['pc_end_time']})";
        $query = isset($params['pc_end_time']) && !empty($params['pc_end_time']) ? $query->whereRaw("lo.created <= FROM_UNIXTIME({$params['pc_end_time']})") : $query;
        isset($params['chase']) && $where[] = " lo.chase_number != 0";
        $query = isset($params['chase']) && !empty($params['chase']) ? $query->whereRaw("lo.chase_number != 0") : $query;

//        isset($params['no_include_tags']) && $where[] = " u.tags NOT IN  (".join(',', $params['no_include_tags']).')';
//        $query = isset($params['no_include_tags']) && !empty($params['no_include_tags']) ? $query->whereNotIn('u.tags', "'".join(',', $params['no_include_tags'])."'") : $query;

//        isset($params['agent_id']) && $where[] = " u.agent_id in( {$params['agent_id']})";//查询代理下面的投注
//        if (isset($params['is_win'])) {
//            if ($params['is_win'] == 'yes') {
//                $where[] = "  find_in_set('winning',lo.state)";
//            } else if ($params['is_win'] == 'no') {
//                $where[] = "  !find_in_set('winning',lo.state)";
//            }
//
//        }
        $attributes['total'] = $query->count();

//        $where = count(array($where)) ? 'AND ' . implode(' AND', $where) : '';
//        $where = 'lo.chase_number <= 0 ' . $where;

        $group = isset($params['group']) ? $params['group'] . ' ' : 'lo.id';

        $res = $query->leftJoin('send_prize as s','lo.order_number','=','s.order_number')
            ->leftJoin('agent as a','u.agent_id','=','a.id')
            ->leftJoin('lottery_info as li',function($q){
                $q->on('li.lottery_number','=','u.id')
                    ->on('li.lottery_type','=','lo.lottery_id');
            })
            ->leftJoin('hall as h','h.id','=','lo.hall_id')
            ->leftJoin('room as r','r.id','=','lo.room_id')
            ->select(DB::raw('
                lo.id,lo.created,UNIX_TIMESTAMP(lo.created) unix_created,
	            CONCAT(chase_number,\'\') as chase_number,lo.user_name,lo.lottery_id,
	            lo.origin,CONCAT(lo.chase_number,\'\') as chase_number,lo.lottery_number,lo.order_number,
                lo.odds,lo.room_id,l.`name` AS lottery_name,lo.play_id,
                lo.play_group,lo.play_name,lo.pay_money,
                lo.bet_num,lo.chase_number,lo.one_money,
                lo.times,lo.play_number,s.money as p_money,
                s.lose_earn,lo.state,lo.marks,a.`name` as agent,
                li.period_code,lo.hall_id,h.hall_name,
                lo.win_bet_count,r.room_name,l.pid
            '))
            ->groupBy($group)
            ->orderBy('lo.id','DESC')
            ->forPage($page,$size)->get()->toArray();

//        $query = DB::table('lottery_order as lo')
//            ->leftJoin('lottery as l','lo.lottery_id','=','l.id')
//            ->leftJoin('send_prize as s','lo.order_number','=','s.order_number')
//            ->leftJoin('user as u','lo.user_id','=','u.id')
//            ->leftJoin('agent as a','u.agent_id','=','a.id')
//            ->leftJoin('lottery_info as li',function($q){
//                $q->on('li.lottery_number','=','u.id')
//                    ->on('li.lottery_type','=','lo.lottery_id');
//            })
//            ->leftJoin('hall as h','h.id','=','lo.hall_id')
//            ->leftJoin('room as r','r.id','=','lo.room_id');
//
//        $attributes['total'] = $query->whereRaw($where)->count();
//        $res = $query->select(DB::raw('
//                lo.id,lo.created,UNIX_TIMESTAMP(lo.created) unix_created,
//	            CONCAT(chase_number,\'\') as chase_number,lo.user_name,lo.lottery_id,
//	            lo.origin,CONCAT(lo.chase_number,\'\') as chase_number,lo.lottery_number,lo.order_number,
//                lo.odds,lo.room_id,l.`name` AS lottery_name,lo.play_id,
//                lo.play_group,lo.play_name,lo.pay_money,
//                lo.bet_num,lo.chase_number,lo.one_money,
//                lo.times,lo.play_number,s.money as p_money,
//                s.lose_earn,lo.state,lo.marks,a.`name` as agent,
//                li.period_code,lo.hall_id,h.hall_name,
//                lo.win_bet_count,r.room_name,l.pid
//            '))
//            ->whereRaw($where)
//            ->groupBy($group)
//            ->orderBy('lo.id','DESC')
//            ->forPage($page,$size)->get()->toArray();

        if(!$res){
            return [];
        }
        $res = array_map('get_object_vars',$res);

        dd(DB::getQueryLog());exit;
        $attributes['number'] = $page;
        $attributes['a'] = $this->getLotteryOrderSum($params);

        $hallArr = DB::table('hall')->get(['id','hall_name'])->pluck('hall_name','id');
//            $canceleds = ['canceled'=>'手动撤销','auto_canceled'=>'自动撤销','system_canceled'=>'系统撤销'];

        $logic = new \LotteryPlay\Logic();
        foreach ($res as $k => &$v) {
            $hall_id = $v['hall_id'];
            if(!$hall_id) $hall_id = 0;
            $v['hall_name'] = isset($hallArr[$hall_id]) ? $hallArr[$hall_id] : '';
            if(strstr($v['state'],'open')){
                $v['state2'] = '已结算';
            }else{
                $v['state2'] = '未结算';
            }
            if(strstr($v['state'],'auto_canceled')) {
                $v['state2'] = '自动撤销';
            }elseif(strstr($v['state'],'system_canceled')) {
                $v['state2'] = '系统撤销';
            }elseif(strstr($v['state'],'canceled')) {
                $v['state2'] = '手动撤销';
            }


            $v['play_numbers'] = $logic->getPretty($v['pid'],$v['play_id'],$v['play_number']);
            $v['period_codes'] = isset($v['period_code'])?str_replace(',','|',$v['period_code']):'';
            $v['odds'] = json_decode($v['odds'], true);

        }

        return $this->lang->set(0,[],$res,$attributes);

    }

    protected function getLotteryOrderSum($condtion){

        $query = DB::table('lottery_order as lo')
            ->leftJoin('send_prize as s','lo.order_number','=','s.order_number')
            ->leftJoin('user as u','lo.user_id','=','u.id')
            ->select(DB::raw('
            sum(lo.pay_money) as pay_money,
            sum(s.money) as send_money,
            sum(s.lose_earn) as lose_earn

                '))
        ;

        isset($condtion['order_status']) && $where[] = "find_in_set('{$condtion['order_status']}', lo.state)";
        isset($condtion['opposite_status']) && $where[] = " !find_in_set('{$condtion['opposite_status']}', lo.state)";
        isset($condtion['opposite_status2']) && $where[] = " !find_in_set('{$condtion['opposite_status2']}', lo.state)";
        if (isset($condtion['odds_type'])) {
            switch ($condtion['odds_type']) {
                case 'std':
                    $where[] = " !find_in_set('fast', d.state)";
                    break;
                case 'fast':
                    $where[] = " find_in_set('fast', d.state)";
                    $where[] = "  o.room_id < 1 ";//
                    break;
                case 'chat':
                    $where[] = " find_in_set('chat', d.state)";
                    break;
                case 'all':
                    break;
                default:
                    $where[] = " find_in_set('{$condtion['odds_type']}', d.state)";
                    break;
            }
        }
        if (isset($condtion['state'])) {
            switch ($condtion['state']) {
                case 'open':
                    $where[] = " find_in_set('open', lo.state)";
                    break;
                case 'noopen':
                    $where[] = " lo.state = ''";
                    break;
                case 'canceled':
//                    $where[] = " find_in_set('canceled', o.state)";
                    $where[] = " lo.state like '%canceled' ";
                    break;
                case 'all':
                    break;
                default:
                    break;
            }
        }
        isset($condtion['user_id']) && $where[] = " lo.user_id = '{$condtion['user_id']}'";
        isset($condtion['user_name']) && $where[] = " locate('{$condtion['user_name']}', lo.user_name) > 0";
        isset($condtion['order_number']) && $where[] = " lo.order_number like '{$condtion['order_number']}%'";
        isset($condtion['lottery_id']) && $where[] = " l.`id` = '{$condtion['lottery_id']}'";
        isset($condtion['play_type1']) && $where[] = " lo.play_type1 = '{$condtion['play_type1']}'";
        isset($condtion['lottery_number']) && $where[] = " lo.lottery_number = '{$condtion['lottery_number']}'";
        isset($condtion['order_origin']) && $where[] = " lo.origin = '{$condtion['order_origin']}'";
        isset($condtion['start_time']) && $where[] = " lo.created >= '{$condtion['start_time']}'";
        isset($condtion['pc_start_time']) && $where[] = " lo.created >= FROM_UNIXTIME({$condtion['pc_start_time']})";
        isset($condtion['end_time']) && $where[] = " lo.created <= '{$condtion['end_time']}'";
        isset($condtion['pc_end_time']) && $where[] = " lo.created <= FROM_UNIXTIME({$condtion['pc_end_time']})";
        isset($condtion['chase']) && $where[] = " lo.chase_number != 0";
        isset($condtion['no_include_tags']) && $where[] = " u.tags NOT IN  (".join(',', $condtion['no_include_tags']).')';
        isset($condtion['agent_id']) && $where[] = " u.agent_id in( {$condtion['agent_id']})";//查询代理下面的投注
        if (isset($condtion['is_win'])) {
            if ($condtion['is_win'] == 'yes') {
                $where[] = "  find_in_set('winning',lo.state)";
            } else if ($condtion['is_win'] == 'no') {
                $where[] = "  !find_in_set('winning',lo.state)";
            }

        }
        $where[] = " lo.chase_number <= 0";
        $where = count($where) ? implode(' AND', $where) : '';

        $res = $query->whereRaw($where)->get()->toArray();
        $res = (array) $res[0];
        $res['lose_earn'] = $res['pay_money'] - $res['send_money'];
        return $res;
    }

    protected function getIdByTag($tag){

        return DB::table('label')->where('title',$tag)->value('id');

    }

};
