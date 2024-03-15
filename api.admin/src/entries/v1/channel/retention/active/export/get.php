<?php
use Logic\Admin\BaseController;

return new class() extends BaseController {
    const TITLE = '渠道管理=活跃留存';
    const DESCRIPTION = '渠道管理-活跃留存';
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
        $tag_num = [1,2,3,5,7,15,30];
        $tag_day = [];
        foreach ($tag_num as $tg) {
            $tag_day[$tg] = date("Y-m-d",strtotime("+".($tg-1)." day", strtotime($date_start)));
        }

        //查询数据
        if (!empty($channel_no)) {
            $channel_list = explode(',', $channel_no);
            $exp_channel_list = implode(',', $channel_list);
//            $u_data = DB::table('rpt_user')->selectRaw("user.channel_id,rpt_user.count_date as dt,GROUP_CONCAT(rpt_user.user_id) as uid_list")
//                ->leftJoin('user','rpt_user.user_id','=','user.id')
//                ->whereIn('user.channel_id',$channel_list)
//                ->whereRaw('rpt_user.count_date>=? and rpt_user.count_date<=? and rpt_user.bet_user_amount>?', [$date_start,$date_end,0])
//                ->groupBy(['dt','user.channel_id'])->get()->toArray();
            $u_data = \DB::connection('slave')->table('rpt_user')->selectRaw("user.channel_id,rpt_user.count_date as dt,rpt_user.user_id as uid_list")
                ->leftJoin('user','rpt_user.user_id','=','user.id')
                ->whereIn('user.channel_id',$channel_list)
                ->whereRaw('rpt_user.count_date>=? and rpt_user.count_date<=? and rpt_user.bet_user_amount>?', [$date_start,$date_end,0])
                ->groupBy(['dt','user.channel_id'])->get()->toArray();
        } else {
//            $u_data = DB::table('rpt_user')->selectRaw("'' as channel_id,rpt_user.count_date as dt,GROUP_CONCAT(rpt_user.user_id) as uid_list")
//                ->leftJoin('user','rpt_user.user_id','=','user.id')
//                ->whereRaw('rpt_user.count_date>=? and rpt_user.count_date<=? and rpt_user.bet_user_amount>? and user.channel_id is not null', [$date_start,$date_end,0])
//                ->groupBy(['dt'])->get()->toArray();
            $u_data = \DB::connection('slave')->table('rpt_user')->selectRaw("'' as channel_id,rpt_user.count_date as dt,rpt_user.user_id as uid_list")
                ->leftJoin('user','rpt_user.user_id','=','user.id')
                ->whereRaw('rpt_user.count_date>=? and rpt_user.count_date<=? and rpt_user.bet_user_amount>? and user.channel_id is not null', [$date_start,$date_end,0])
                ->groupBy(['dt'])->get()->toArray();
        }

        $part_data = [];
        $exist_date = [];
        if (!empty($u_data)) {
            foreach ($u_data as $dt) {
                $exist_date[$dt->dt][] = $dt->channel_id;     //已经存在的日期

                //统计该渠道在当前日期为起始， 后续每日活跃人数、总流水、人均流水、留存率
                $tag_day = [];
                foreach ($tag_num as $tg) {
                    $tag_day[$tg] = date("Y-m-d",strtotime("+".($tg-1)." day", strtotime($dt->dt)));    //计算出当条数据所需的N日日期：第二日，第单日，第五日，...
                }
                if ($channel_no) {
                    $sql = "select '' as channel_id, count(id) as active_num,ifnull(sum(bet_user_amount),0) as amount,count_date from rpt_user where user_id in (select rpt_user.user_id as user_id from rpt_user ".
                        " left join user on rpt_user.user_id=user.id where rpt_user.count_date='{$dt->dt}' and rpt_user.bet_user_amount>0 and user.channel_id in ({$dt->channel_id})) ".
                        " and rpt_user.count_date>='{$tag_day[1]}' and rpt_user.count_date<='{$tag_day[30]}' and rpt_user.bet_user_amount>0 group by count_date";
                    $dailyInfo = \DB::connection('slave')->select($sql);
                } else {
                    $sql = "select '' as channel_id, count(id) as active_num,ifnull(sum(bet_user_amount),0) as amount,count_date from rpt_user where user_id in (select rpt_user.user_id as user_id from rpt_user left join user on rpt_user.user_id=user.id where rpt_user.count_date='{$dt->dt}' and rpt_user.bet_user_amount>0 and user.channel_id is not null) ".
                        " and rpt_user.count_date>='{$tag_day[1]}' and rpt_user.count_date<='{$tag_day[30]}' and rpt_user.bet_user_amount>0 group by count_date";
                    $dailyInfo = \DB::connection('slave')->select($sql);
                }

                $tmp = [
                    'channel_id' => $dt->channel_id,           //渠道号
                    'dt' => $dt->dt,                           //统计时间
                ];

                //格式化成键值对数组
                $fmt_daily = [];
                if (!empty($dailyInfo)) {
                    foreach ($dailyInfo as $dy) {
                        $fmt_daily[$dy->count_date] = [
                            'ymd' => $dy->count_date,            //该日日期
                            'active_num' => $dy->active_num,     //该日活跃人数
                            'amount' => $dy->amount,             //该日总流水
                            'avg_amount' => empty($dy->active_num) ? 0 : bcdiv($dy->amount, $dy->active_num, 2),  //该日人均流水
                        ];
                    }
                }

                foreach ($tag_day as $k_no => $v_day) {
                    $tmp['active_num_' . $k_no] = $fmt_daily[$v_day]['active_num'] ?? 0;      //活跃人数
                    $tmp['amount_' . $k_no] = $fmt_daily[$v_day]['amount'] ?? 0;   //总流水
                    //人均流水、留存率
                    if (empty($tmp['active_num_' . $k_no])) {
                        $tmp['avg_amount_' . $k_no] = 0;           //人均流水
                        $tmp['retention_' . $k_no] = "0%";           //留存率
                    } else {
                        $tmp['avg_amount_' . $k_no] = bcdiv($tmp['amount_' . $k_no], $tmp['active_num_' . $k_no], 2);
                        $tmp['retention_' . $k_no] = bcdiv($tmp['active_num_' . $k_no]*100, $tmp['active_num_1'], 2)."%";
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
                    ];
                    foreach ($tag_day as $k_no=>$v_day) {
                        $tmp['active_num_'.$k_no] = 0;
                        $tmp['amount_'.$k_no] = 0;
                        $tmp['avg_amount_'.$k_no] = 0;
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
                        ];
                        foreach ($tag_day as $k_no=>$v_day) {
                            $tmp['active_num_'.$k_no] = 0;
                            $tmp['amount_'.$k_no] = 0;
                            $tmp['avg_amount_'.$k_no] = 0;
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

        $title = [
            'channel_id' => '渠道号',
            'dt' => '日期',
            'active_num_1' => '活跃人数',
            'amount_1' => '总流水',
            'avg_amount_1' => '人均流水',
        ];
        foreach ($tag_day as $k_no=>$v_day) {
            if ($k_no == 1) {
                continue;
            }
            $title['active_num_'.$k_no] = ($k_no==2) ? "次日活跃人数" : $k_no.'日活跃人数';
            $title['amount_'.$k_no] =  ($k_no==2) ? "次日总流水" : $k_no.'日总流水';
            $title['avg_amount_'.$k_no] = ($k_no==2) ? "次日人均流水" : $k_no.'日人均流水';
            $title['retention_'.$k_no] = ($k_no==2) ? "次日留存率" : $k_no.'日留存率';
        }

        $en_title = [
            'channel_id' => 'ChannelNo',
            'dt' => 'Date',
            'active_num_1' => 'Active user',
            'amount_1' => 'Total turnover',
            'avg_amount_1' => 'Average turnover',
        ];
        foreach ($tag_day as $k_no=>$v_day) {
            if ($k_no == 1) {
                continue;
            }
            $en_title['active_num_'.$k_no] = ($k_no==2) ? "Next day Active user" : $k_no.' days active user';
            $en_title['amount_'.$k_no] = ($k_no==2) ? "Next day total turnover" : $k_no.' days total turnover';
            $en_title['avg_amount_'.$k_no] = ($k_no==2) ? "Next day Avg. turnover" : $k_no.' days avg. turnover';
            $en_title['retention_'.$k_no] = ($k_no==2) ? "percentage of next day Dep" : $k_no.' days retention rate';
        }

        foreach ($en_title as $key => $value){
            $arr[$key] = $this->lang->text($value);
        }

        array_unshift($res_data,$arr);
        if ($this->lang->getLangSet() == 'th'){
            array_unshift($res_data,$en_title);
        }
        $this->exportExcel("渠道管理活跃留存",$title, $res_data);
        exit();
    }

    public function exportExcel($file, $title, $data) {
        header('Content-type:application/vnd.ms-excel;charset=utf-8;');
        header('Content-Disposition:attachment;filename=' . $file . '.xls');
        $content = '';
        foreach ($title as $tval) {
            $content .= $tval . "\t";
        }
        $content .= "\n";
        $keys = array_keys($title);
        if ($data) {
            foreach ($data as $ke=> $val) {
                if ($ke > 49999) {
                    break;
                }
                $val = (array)$val;
                foreach ($keys as $k) {
                    $content .= $val[$k] . "\t";
                }
                $content .= "\n";
                echo mb_convert_encoding($content, "UTF-8", "UTF-8");
                $content = '';
            }
        }
        exit;
    }
};