<?php

use Logic\Admin\BaseController;

return new class() extends BaseController {
    const TITLE = '注单查询--彩票标准/快速';
    const DESCRIPTION = '';

    const QUERY = [
        'play_type'      => 'string() #(fast=快速,std=标准, chat聊天, video视频,all=所有)',
        'user_name'      => 'string #用户名',
        'order_number'   => 'string #注单号',
        'lottery_id'     => 'int #彩种id，见彩种列表',
        'lottery_number' => 'string #期号',
        'order_origin'   => 'int #来源，1 pc，2 h5，3 app',
        'start_time'     => 'datetime #查询时间，开始',
        'end_time'       => 'datetime #查询时间，结束',
        'page'           => 'int #条数',
        'page_size'      => 'int #页码',
    ];
    
    const PARAMS = [];
    const SCHEMAS = [
        [
            'map #
            "id": "45",
            "created": "2018-02-06 15:41:10",
            "unix_created": "1517902870",
            "order_number": "2018020603411045879",
            "user_name": "ni",
            "lottery_id": "2",
            "origin": "2",
            "chase_number": "0",
            "lottery_number": "871175",
            "odds": {
                "单": "2.00",
                "双": "2.00",
                "大": "2.00",
                "小": "2.00",
                "大单": "4.33",
                "大双": "3.72",
                "小单": "3.72",
                "小双": "4.33",
                "极大": "17.86",
                "极小": "17.86"
            },
            "room_id": "13",
            "lottery_name": "北京幸运28",
            "play_group": "大小单双",
            "play_name": "大小单双",
            "pay_money": "1000",
            "bet_num": "10",
            "one_money": "100",
            "times": "1",
            "play_number": "大|小|单|双|小单|小双|大单|大双|极小|极大",
            "p_money": null,
            "lose_earn": null,
            "state": "",
            "marks": "",
            "agent": null,
            "period_code": null,
            "hall_id": null,
            "hall_name": "",
            "state2": "未结算",
        ',
        ],
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run() {

        $params = $this->request->getParams();

        //$params['no_include_tags'][] = DB::table('label')->where('title','试玩')->value('id');
        //$params['no_include_tags'][] = DB::table('label')->where('title','测试')->value('id');

        return $this->getLotteryRecords($params, $params['page'], $params['page_size']);
    }


    protected function getLotteryRecords($params, $page = 1, $size = 10) {

        $query = DB::table('lottery_order as lo')
            ->leftJoin('send_prize as s', 'lo.order_number', '=', 's.order_number')
            ->leftJoin('lottery as l', 'lo.lottery_id', '=', 'l.id')
            ->leftJoin('user as u', 'u.id', '=', 'lo.user_id')
//            ->leftJoin('agent as a','u.agent_id','=','a.id')
            ->leftJoin('lottery_info as li', function ($q) {
                $q->on('li.lottery_number', '=', 'lo.lottery_number')
                    ->on('li.lottery_type', '=', 'lo.lottery_id');
            })
            ->leftJoin('hall as h', 'h.id', '=', 'lo.hall_id')
            ->leftJoin('room as r', 'r.id', '=', 'lo.room_id');

        $query = isset($params['order_status']) && is_numeric($params['order_status']) ? $query->whereRaw("find_in_set('{$params['order_status']}', lo.state)") : $query;
        $query = isset($params['opposite_status']) && is_numeric($params['opposite_status']) ? $query->whereRaw("!find_in_set('{$params['opposite_status']}', lo.state") : $query;
        $query = isset($params['opposite_status2']) && is_numeric($params['opposite_status2']) ? $query->whereRaw("!find_in_set('{$params['opposite_status2']}', lo.state)") : $query;
        if (isset($params['hall_level']) && !empty($params['hall_level'])) {
            switch ($params['hall_level']) {
                case 5:   // 传统模式
                    $query = $query->whereIn('h.hall_level',[4,5]);
                    break;
                case 6:  //直播模式
                    $query = $query->where('h.hall_level',6);
                    break;
                case 1: //房间模式
                    $query = $query->whereNotIn('h.hall_level',[4,5,6]);
                    break;
            }
        }
        if (isset($params['play_type']) && !empty($params['play_type'])) {
            switch ($params['play_type']) {
                case 'std':
                    $query = $query->whereRaw("find_in_set('std', lo.state)");
                    break;
                case 'fast':
                    $query = $query->whereRaw("find_in_set('fast', lo.state)");
                    break;
                case 'chat':
                    $query = $query->whereRaw("find_in_set('chat', lo.state)");
                    break;
                case 'vedio':
                    $query = $query->whereRaw("find_in_set('vedio', lo.state)");
                    break;
                case 'all':
                    break;
                default:
                    $query = $query->whereRaw("find_in_set('{$params['play_type']}', lo.state)");
                    break;
            }
        }
        if (isset($params['state']) && !empty($params['state'])) {
            switch ($params['state']) {
                case 'open':
                    $query = $query->whereRaw("find_in_set('open', lo.state)");
                    break;
                case 'noopen':
                    $query = $query->whereRaw(" !find_in_set('open', lo.state) and !LOCATE('canceled',lo.`state`)");
                    break;
                case 'canceled':
                    $query = $query->whereRaw("LOCATE('canceled',lo.`state`)");
                    break;
                case 'all':
                    break;
                default:
                    break;
            }
        }

        $query = isset($params['user_id']) && !empty($params['user_id']) ? $query->whereRaw("lo.user_id = '{$params['user_id']}'") : $query;

        $query = isset($params['user_name']) && !empty($params['user_name']) ? $query->whereRaw("lo.user_name = '{$params['user_name']}'") : $query;

        $query = isset($params['order_number']) && !empty($params['order_number']) ? $query->whereRaw("lo.order_number = {$params['order_number']}") : $query;

        $query = isset($params['lottery_id']) && !empty($params['lottery_id']) ? $query->whereRaw("lo.`lottery_id` = '{$params['lottery_id']}'") : $query;

        $query = isset($params['play_id']) && !empty($params['play_id']) ? $query->whereRaw("lo.play_id = '{$params['play_id']}'") : $query;

        $query = isset($params['lottery_number']) && !empty($params['lottery_number']) ? $query->whereRaw("lo.lottery_number = '{$params['lottery_number']}'") : $query;

        $query = isset($params['order_origin']) && !empty($params['order_origin']) ? $query->whereRaw("lo.origin = '{$params['order_origin']}'") : $query;
        $query = isset($params['start_time']) && !empty($params['start_time']) ? $query->whereRaw(" lo.created >= '{$params['start_time']}'") : $query;

        $query = isset($params['pc_start_time']) && !empty($params['pc_start_time']) ? $query->whereRaw("lo.created >= FROM_UNIXTIME({$params['pc_start_time']})") : $query;
        if (isset($params['end_time']) && !empty($params['end_time'])) {
            $query->whereRaw("lo.created <= '{$params['end_time']}'");
        }
        $query = isset($params['pc_end_time']) && !empty($params['pc_end_time']) ? $query->whereRaw("lo.created <= FROM_UNIXTIME({$params['pc_end_time']})") : $query;
        $query = isset($params['chase']) && !empty($params['chase']) ? $query->whereRaw("lo.chase_number != 0") : $query;
        $attributes['total'] = $query->count();
        $attributes['number'] = $page;
        $attributes['size'] = $size;

        $group = isset($params['group']) ? $params['group'] . ' ' : 'lo.id';

        $res = $query
//            ->select(DB::raw('
//                lo.id,lo.created,UNIX_TIMESTAMP(lo.created) unix_created,
//	            CONCAT(chase_number,\'\') as chase_number,lo.user_name,lo.lottery_id,
//	            lo.origin,CONCAT(lo.chase_number,\'\') as chase_number,lo.lottery_number,CONCAT(lo.order_number,\'\') as order_number,
//                lo.odds,lo.room_id,l.`name` AS lottery_name,lo.play_id,
//                lo.play_group,lo.play_name,lo.pay_money,
//                lo.bet_num,lo.chase_number,lo.one_money,
//                lo.times,lo.play_number,lo.send_money as p_money,
//                lo.lose_earn,lo.state,lo.marks,
//                lo.open_code as period_code,lo.hall_id,h.hall_name,
//                lo.win_bet_count,r.room_name,l.pid,lo.tags
//            '))
            ->select(DB::raw('
                lo.id,lo.user_id,lo.created,UNIX_TIMESTAMP(lo.created) unix_created,
	            CONCAT(chase_number,\'\') as chase_number,lo.user_name,lo.lottery_id,
	            lo.origin,CONCAT(lo.chase_number,\'\') as chase_number,lo.lottery_number,CONCAT(lo.order_number,\'\') as order_number,
                lo.odds,lo.room_id,l.`name` AS lottery_name,lo.play_id,
                lo.play_group,lo.play_name,lo.pay_money,
                lo.bet_num,lo.chase_number,lo.one_money,
                lo.times,lo.play_number,s.money as p_money,
                s.lose_earn,lo.state,lo.marks,
                li.period_code,lo.hall_id,h.hall_name,h.hall_level,
                lo.win_bet_count,r.room_name,l.pid,u.tags
            '))
            ->whereRaw("not exists (select 1 from user where lo.user_id = id and tags =7)")
            ->groupBy($group)
            ->orderBy('lo.id', 'DESC')
            ->forPage($page, $size)
            ->get()
            ->toArray();

//        dd(DB::getQueryLog());exit;
        if (!$res) {
            return [];
        }
        $res = array_map('get_object_vars', $res);

        $attributes['number'] = $page;

        $hallArr = DB::table('hall')
                     ->get(['id', 'hall_name'])
                     ->pluck('hall_name', 'id');
//            $canceleds = ['canceled'=>'手动撤销','auto_canceled'=>'自动撤销','system_canceled'=>'系统撤销'];

        $logic = new \LotteryPlay\Logic();
        foreach ($res as $k => &$v) {
            $hall_id = $v['hall_id'];
            if (!$hall_id) $hall_id = 0;
            $v['hall_name'] = isset($hallArr[$hall_id]) ? $hallArr[$hall_id] : '';
            if (strstr($v['state'], 'open')) {
                $v['state2'] = $this->lang->text('settlement');
            } else {
                $v['state2'] = $this->lang->text('unsettlement');
            }
            if(strstr($v['state'],'auto_canceled')) {
                $v['state2'] = $this->lang->text('cancel');
            }elseif(strstr($v['state'],'system_canceled')) {
                $v['state2'] = $this->lang->text('cancel');
            }elseif(strstr($v['state'],'canceled')) {
                $v['state2'] = $this->lang->text('cancel');
            }
            if(strstr($v['state'],'fast')) {
                $tmp = $this->lang->text('state.fast');
            }elseif(strstr($v['state'],'std')) {
                $tmp = $this->lang->text('state.std');
            }else {
                $tmp = '';
            }
            //模式那栏  4是PC  5是传统  PC和传统是统一的
            switch ($v['hall_level']){
                case 4:
                case 5: $v['mode_str'] = $this->lang->text('Traditional model')." <{$tmp}>";break;
                case 6: $v['mode_str'] = $this->lang->text('Live mode');break;
                default:$v['mode_str'] = $this->lang->text('Room mode')." <{$v['hall_name']}>";
            }

            $v['play_numbers'] = $logic->getPretty($v['pid'], $v['play_id'], $v['play_number']);

            $v['period_codes'] = isset($v['period_code']) ? str_replace(',', '|', $v['period_code']) : '';
            $v['odds'] = json_decode($v['odds'], true);

        }

        return $this->lang->set(0, [], $res, $attributes);

    }


    protected function getIdByTag($tag) {

        return DB::table('label')
                 ->where('title', $tag)
                 ->value('id');

    }
};
