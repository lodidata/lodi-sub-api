<?php
use Logic\Admin\BaseController;

return new class() extends BaseController {
    const TITLE = '渠道管理=充值留存';
    const DESCRIPTION = '渠道管理-充值留存';
    const QUERY = [
        'date_start' => 'string() #开始日期',
        'date_end' => 'string() #结束日期',
        'channel_no' => 'string() #渠道号',
    ];
    const SCHEMAS = [];

    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run() {
        $date_start = $this->request->getParam('date_start', date("Y-m-d",strtotime("-1 day")));   //默认查前一天数据
        $date_end = $this->request->getParam('date_end', date("Y-m-d",strtotime("-1 day")));
        $channel_no = $this->request->getParam('channel_no','');
        $sort_field = $this->request->getParam('sort_field', 'dt');    //排序字段
        $sort_type = $this->request->getParam('sort_type', 'desc');    //排序类型

        if(strtotime($date_start) > strtotime($date_end)){
            return $this->lang->set(886, ['结束日期不能小于开始日期']);
        }

        //计算出查询时间范围内的每个天日期
        $day_list = [];
        $start = $date_start;
        $end = $date_end;
        while ($start <= $end) {
            $day_list[] = $start;
            $start = date("Y-m-d",strtotime("+1 day", strtotime($start)));
        }
        //查询天数不能超过一个月
        if(count($day_list) > 31){
            return $this->lang->set(886, ['查询期间不能大于1个月']);
        }
        //获取要统计的几个日期
        $tag_num = [2,3,5,7,15,30];     //第一天为首充统计数据，所以不放在列表中
        $tag_day = [];
        foreach ($tag_num as $tg) {
            $tag_day[$tg] = date("Y-m-d",strtotime("+".($tg-1)." day", strtotime($date_start)));
        }

        //查询数据
        if (!empty($channel_no)) {
            $channel_list = explode(',', $channel_no);
            $exp_channel_list = implode(',', $channel_list);
//            $u_data = DB::table('user')->selectRaw("channel_id,date_format(first_recharge_time, '%Y-%m-%d') as dt,GROUP_CONCAT(id) as id")
//                ->whereIn('channel_id',$channel_list)
//                ->whereRaw('first_recharge_time>=? and first_recharge_time<=?', [$date_start." 00:00:00",$date_end." 23:59:59"])->groupBy(['dt','channel_id'])->get()->toArray();
            $u_data = \DB::connection('slave')->table('user')->selectRaw("channel_id,date_format(first_recharge_time, '%Y-%m-%d') as dt,id")
                ->whereIn('channel_id',$channel_list)
                ->whereRaw('first_recharge_time>=? and first_recharge_time<=?', [$date_start." 00:00:00",$date_end." 23:59:59"])->groupBy(['dt','channel_id'])->get()->toArray();
        } else {
//            $u_data = DB::table('user')->selectRaw("'' as channel_id,date_format(first_recharge_time, '%Y-%m-%d') as dt,GROUP_CONCAT(id) as id")
//                ->whereRaw('first_recharge_time>=? and first_recharge_time<=? and channel_id is not null', [$date_start." 00:00:00",$date_end." 23:59:59"])->groupBy(['dt'])->get()->toArray();
            $u_data = \DB::connection('slave')->table('user')->selectRaw("'' as channel_id,date_format(first_recharge_time, '%Y-%m-%d') as dt,id")
                ->whereRaw('first_recharge_time>=? and first_recharge_time<=? and channel_id is not null', [$date_start." 00:00:00",$date_end." 23:59:59"])->groupBy(['dt'])->get()->toArray();
        }

        $part_data = [];
        $exist_date = [];
        if (!empty($u_data)) {
            foreach ($u_data as $dt) {
                $exist_date[$dt->dt][] = $dt->channel_id;     //已经存在的日期

                $tmp = [
                    'channel_id' => $dt->channel_id,           //渠道号
                    'dt' => $dt->dt,                           //首充时间
                ];

                //统计该渠道在当前日期为起始， 后续每日充值人数、充值金额、留存率、人均充值
                $tag_day = [];
                foreach ($tag_num as $tg) {
                    $tag_day[$tg] = date("Y-m-d",strtotime("+".($tg-1)." day", strtotime($dt->dt)));    //计算出当条数据所需的N日日期：第二日，第单日，第五日，...
                }
                if ($channel_no) {
                    //首日的首充金额
                    $first_sql = "select sum(money) as money,count(DISTINCT user_id) as num from funds_deposit where user_id in (select id as user_id from user where first_recharge_time>='{$dt->dt} 00:00:00' and first_recharge_time<='{$dt->dt} 23:59:59' and channel_id in ({$dt->channel_id})) ".
                        " and money>0 and status='paid' and find_in_set('new',state) and created>='{$dt->dt} 00:00:00' and created<='{$dt->dt} 23:59:59'";
                    $firstData = \DB::connection('slave')->select($first_sql);
                    $tmp['first_recharge_amount'] = $firstData[0]->money ? bcdiv($firstData[0]->money,100,2) : 0;  //首充金额
                    $tmp['first_recharge_num'] = $firstData[0]->num ?? 0;  //首充人数
                    //后续每日数据
                    $sql = "select sum(money) as money,count(DISTINCT user_id) as u_num,date(created) as ymd from funds_deposit where user_id in (select id as user_id from user where first_recharge_time>='{$dt->dt} 00:00:00' and first_recharge_time<='{$dt->dt} 23:59:59' and channel_id in ({$dt->channel_id}))".
                        " and status='paid' and money>0 and created>='{$tag_day[2]} 00:00:00' and created<='{$tag_day[30]} 23:59:59' group by ymd";
                    $dailyInfo = \DB::connection('slave')->select($sql);
                } else {
                    //首日的首充金额
                    $first_sql = "select sum(money) as money,count(DISTINCT user_id) as num from funds_deposit where user_id in (select id as user_id from user where first_recharge_time>='{$dt->dt} 00:00:00' and first_recharge_time<='{$dt->dt} 23:59:59' and channel_id is not null) ".
                        " and money>0 and status='paid' and find_in_set('new',state) and created>='{$dt->dt} 00:00:00' and created<='{$dt->dt} 23:59:59'";
                    $firstData = \DB::connection('slave')->select($first_sql);
                    $tmp['first_recharge_amount'] = $firstData[0]->money ? bcdiv($firstData[0]->money,100,2) : 0;  //首充金额
                    $tmp['first_recharge_num'] = $firstData[0]->num ?? 0;  //首充人数
                    //后续每日数据
                    $sql = "select sum(money) as money,count(DISTINCT user_id) as u_num,date(created) as ymd from funds_deposit where user_id in (select id as user_id from user where first_recharge_time>='{$dt->dt} 00:00:00' and first_recharge_time<='{$dt->dt} 23:59:59' and channel_id is not null)".
                    " and status='paid' and money>0 and created>='{$tag_day[2]} 00:00:00' and created<='{$tag_day[30]} 23:59:59' group by ymd";
                    $dailyInfo = \DB::connection('slave')->select($sql);
                }

                //格式化成键值对数组
                $fmt_daily = [];
                if (!empty($dailyInfo)) {
                    foreach ($dailyInfo as $dy) {
                        $fmt_daily[$dy->ymd] = [
                            'money' => bcdiv($dy->money,100,2),     //该日充值金额
                            'u_num' => $dy->u_num,     //该日充值人数
                            'avg_amount' => empty($dy->u_num) ? 0 : bcdiv(bcdiv($dy->money,100,2), $dy->u_num, 2),  //该日人均充值
                            'ymd' => $dy->ymd,         //该日日期
                        ];
                    }
                }
                foreach ($tag_day as $k_no => $v_day) {
                    $tmp['recharge_num_' . $k_no] = $fmt_daily[$v_day]['u_num'] ?? 0;      //复充人数
                    $tmp['recharge_amount_' . $k_no] = $fmt_daily[$v_day]['money'] ?? 0;   //复充金额
                    //人均充值、留存率
                    if (empty($tmp['recharge_num_' . $k_no])) {
                        $tmp['recharge_avg_' . $k_no] = 0;        //人均充值
                        $tmp['retention_' . $k_no] = 0;           //留存率
                    } else {
                        $tmp['recharge_avg_' . $k_no] = bcdiv($tmp['recharge_amount_' . $k_no], $tmp['recharge_num_' . $k_no], 2);
                        $tmp['retention_' . $k_no] = bcdiv($tmp['recharge_num_' . $k_no]*100,$tmp['first_recharge_num'], 2);
                    }
                }
                $part_data[] = $tmp;
            }
        }

        //填充一下没有数据的日期
        $res_data = [];
        foreach ($day_list as $d) {
            if (empty($channel_no)) {
                if (!isset($exist_date[$d])) {
                    $tmp = [
                        'channel_id' => '',
                        'dt' => $d,
                        'first_recharge_num' => 0,
                        'first_recharge_amount' => 0,
                    ];
                    foreach ($tag_day as $k_no=>$v_day) {
                        $tmp['recharge_num_'.$k_no] = 0;
                        $tmp['recharge_amount_'.$k_no] = 0;
                        $tmp['recharge_avg_'.$k_no] = 0;
                        $tmp['retention_'.$k_no] = 0;
                    }
                    $res_data[] = $tmp;
                }
            } else {
                foreach ($channel_list as $ch) {
                    if (!isset($exist_date[$d]) || !in_array($ch,$exist_date[$d])) {
                        $tmp = [
                            'channel_id' => $ch,
                            'dt' => $d,
                            'first_recharge_num' => 0,
                            'first_recharge_amount' => 0,
                        ];
                        foreach ($tag_day as $k_no=>$v_day) {
                            $tmp['recharge_num_'.$k_no] = 0;
                            $tmp['recharge_amount_'.$k_no] = 0;
                            $tmp['recharge_avg_'.$k_no] = 0;
                            $tmp['retention_'.$k_no] = 0;
                        }
                        $res_data[] = $tmp;
                    }
                }
            }
        }
        $res_data = array_merge($res_data, $part_data);
        //结果排序
        $us_sort_field = array_column($res_data,$sort_field);
        if ($sort_type == 'asc') {
            array_multisort($us_sort_field,SORT_ASC,$res_data);
        } else {
            array_multisort($us_sort_field,SORT_ASC,$res_data);
        }

        return $this->lang->set(0, [], $res_data);
    }

};