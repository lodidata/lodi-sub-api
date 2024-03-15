<?php

use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const TITLE = '注单查询--统计金额';
    const DESCRIPTION = '';
    
    const QUERY = ['play_type' => 'string() #(fast=快速,std=标准, chat聊天, video视频,all=所有)',
        'user_name' => 'string #用户名',
        'order_number' => 'string #注单号',
        'lottery_id' => 'int #彩种id，见彩种列表',
        'lottery_number' => 'string #期号',
        'order_origin' => 'int #来源，1 pc，2 h5，3 app',
        'start_time' => 'datetime #查询时间，开始',
        'end_time' => 'datetime #查询时间，结束',
        'page' => 'int #条数',
        'page_size' => 'int #页码'];
    
    const PARAMS = [];
    const SCHEMAS = [['map #
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
            "state2": "未结算"
        ', ]];
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

        return $this->getLotteryOrderSum($params);

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
                    $where[] = " find_in_set('fast', d.state)";
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
        isset($condtion['lottery_id']) && $where[] = " lo.`lottery_id` = '{$condtion['lottery_id']}'";
        isset($condtion['play_type1']) && $where[] = " lo.play_type1 = '{$condtion['play_type1']}'";
        isset($condtion['lottery_number']) && $where[] = " lo.lottery_number = '{$condtion['lottery_number']}'";
        isset($condtion['order_origin']) && $where[] = " lo.origin = '{$condtion['order_origin']}'";
        isset($condtion['start_time']) && $where[] = " lo.created >= '{$condtion['start_time']} 00:00:00'";
        isset($condtion['pc_start_time']) && $where[] = " lo.created >= FROM_UNIXTIME({$condtion['pc_start_time']})";
        isset($condtion['end_time']) && $where[] = " lo.created <= '{$condtion['end_time']} 23:59:59'";
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
//        dd(DB::getQueryLog());exit;
        $res = (array) $res[0];
        $res['lose_earn'] = $res['pay_money'] - $res['send_money'];
        return $res;
    }

    protected function getIdByTag($tag){

        return DB::table('label')->where('title',$tag)->value('id');

    }

};
