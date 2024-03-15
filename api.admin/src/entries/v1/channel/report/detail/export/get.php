<?php
use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const TITLE = '渠道管理-渠道报表-导出';
    const DESCRIPTION = '导出报表';
    const QUERY = [
        'page' => 'int(required) #当前页',
        'page_size' => 'int(required) #每页数量',
        'date_start' => 'string() #开始日期',
        'date_end' => 'string() #结束日期',
        'channel_no' => 'string() #渠道号',
        'channel_name' => 'string() #渠道名称',
    ];
    const PARAMS = [];
    const SCHEMAS = [
        "channel_id" => "渠道号",
        "award_money" => "月俸",
        "cz_amount" => "充值金额",
        "count_date" => "统计日期",
        "cz_person" => "充值人数",
        "qk_person" => "取款人数",
        "qk_amount" => "取款金额",
        "tz_amount" => "投注金额",
        "pc_amount" => "派彩金额",
        "hd_amount" => "活动彩金",
        "hs_amount" => "回水彩金",
        "js_amount" => "晋升彩金",
        "zk_amount" => "转卡彩金",
        "fyz_amount" => "返佣总金额",
        "first_recharge_user" => "首次充值人数",
        "first_recharge" => "首次充值金额",
        "first_withdraw" => "首次取款金额",
        "first_bet" => "首次投注金额",
        "first_prize" => "首次派彩金额",
        "channel_name" => "渠道名称",
        "register_num" => "注册人数",
        "first_register_num" => "新注册人数",
        "diff_cqk" => "存取款差额",
        "diff_tzpc" => "投注派彩差额",
        "first_diff_tzpc" => "新投注派彩差"
    ];

    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run($channel=null) {
        $page = $this->request->getParam('page',1);
        $page_size = $this->request->getParam('page_size',20);
        $date_start = $this->request->getParam('date_start', date("Y-m-d"),strtotime("-1 day"));
        $date_end = $this->request->getParam('date_end', date("Y-m-d", strtotime("-1 day")));
        $channel = $this->request->getParam('channel','');

        //获取所有渠道
        $channel_data = \DB::connection('slave')->table('channel_management')->get()->toArray();
        $channel_map = [];   //渠道名称 =》渠道号
        $channel_map2 = [];   //渠道号 =》 渠道名称
        if (!empty($channel_data)) {
            foreach ($channel_data as $c) {
                $channel_map[$c->name] = $c->number;
                $channel_map2[$c->number] = $c->name;
            }
        }
        $start_time=$date_start.' 00:00:00';
        $end_time=$date_end.' 23:59:59';

        //单独查询各渠道的月俸 (left join 查询sum的月俸数据不对)
        $yf_sql = "SELECT lt.channel_id,lt.id,SUM(user_monthly_award.award_money) as money from (select DISTINCT user.channel_id,user.id from `rpt_user` ".
            "left join `user` on `user`.`id` = `rpt_user`.`user_id` where rpt_user.count_date>='$date_start' and rpt_user.count_date<='$date_end' and user.channel_id is not null) as lt ".
            "LEFT JOIN `user_monthly_award` on `user_monthly_award`.`user_id` = lt.id ".
            "and `user_monthly_award`.`created` >= '{$start_time}' and `user_monthly_award`.`created` <= '{$end_time}' GROUP BY lt.channel_id ";
        $res = \DB::connection('slave')->select($yf_sql);
        $yf_map = [];
        if ($res) {
            foreach ($res as $yf) {
                if (empty($yf->channel_id)) {
                    $c = "original";
                } else {
                    $c = $yf->channel_id;
                }
                $yf_map[$c] = bcdiv(intval($yf->money), 100, 2);
            }
        }

        $sql = \DB::connection('slave')->table('rpt_user')
            ->leftJoin('user','user.id', '=','rpt_user.user_id')
//            ->leftJoin('user_monthly_award','user_monthly_award.user_id','=','rpt_user.user_id')
            ->leftjoin('user_monthly_award', function ($join) use ($start_time, $end_time,$date_start,$date_end){
                $join->on('user_monthly_award.user_id', '=', 'rpt_user.user_id')
                    ->where('user_monthly_award.created',">=",$start_time)
                    ->where('user_monthly_award.created',"<=",$end_time);
            })
            ->where('user.channel_id', $channel)
            ->whereRaw('rpt_user.count_date>=? and rpt_user.count_date<=? and user.channel_id is not null', [$date_start,$date_end]);
        $data = $sql->groupBy(['rpt_user.count_date'])->selectRaw( 'user.channel_id,sum(rpt_user.deposit_user_amount) as cz_amount,rpt_user.count_date,
        sum(IF(rpt_user.deposit_user_amount>0, 1, 0)) as cz_person,sum(IF(rpt_user.withdrawal_user_amount>0, 1, 0)) as qk_person,sum(rpt_user.withdrawal_user_amount) as qk_amount,sum(rpt_user.bet_user_amount) as tz_amount,
        sum(rpt_user.prize_user_amount) as pc_amount,sum(rpt_user.coupon_user_amount) as hd_amount,sum(rpt_user.return_user_amount) as hs_amount,sum(rpt_user.promotion_user_winnings) as js_amount,
        sum(rpt_user.turn_card_user_winnings) as zk_amount,sum(rpt_user.back_user_amount) as fyz_amount,SUM(IF(register_time>=count_date && register_time<=CONCAT(`count_date`," 23:59:59"), 1, 0)) as first_register_num,sum(IF(rpt_user.first_deposit>0, 1, 0)) as first_recharge_user,sum(IF(rpt_user.first_deposit>0, rpt_user.deposit_user_amount, 0)) as first_recharge,sum(IF(rpt_user.first_deposit>0, rpt_user.withdrawal_user_amount, 0)) as first_withdraw,sum(IF(rpt_user.first_deposit>0, rpt_user.bet_user_amount, 0)) as first_bet,sum(IF(rpt_user.first_deposit>0, rpt_user.prize_user_amount, 0)) as first_prize')->get()->toArray();

        $exp_data = [];
        if (!empty($data)) {
            //统计每个渠道历史累计注册人数
            $register_count = \DB::connection('slave')->table('user')->where('channel_id',$channel)->count();
            $first_register_count = \DB::connection('slave')->table('user')->where('created','>=',date('Y-m-d 00:00:00',strtotime($date_start)))->where('created','<=',date('Y-m-d 23:59:59',strtotime($date_end)))->where('channel_id',$channel)->count();
            $click  = \DB::connection('slave')->table('user_channel_logs')->selectRaw('count(distinct log_ip) as distinct_click,count(log_ip) as click_num')->where('channel_id',$channel)->get()->toArray();

            //补充每个渠道数据字段
            foreach ($data as &$itm) {
                $itm = (array)$itm;
                if (empty($itm['channel_id'])) {
                    $itm['channel_id'] = "original";
                }
                //新Ip访问
                $first_click_num = \DB::connection('slave')->table('user_channel_logs')->selectRaw('count(log_ip) as click')
                    ->where('created','>=',date('Y-m-d 00:00:00',strtotime($itm['count_date'])))
                    ->where('created','<=',date('Y-m-d 23:59:59',strtotime($itm['count_date'])))
                    ->where('channel_id',$channel)
                    ->get()[0]->click;
                if (is_array($click)){
                    //独立ip访问、总ip访问
                    $itm['distinct_click'] = $click[0]->distinct_click;
                    $itm['click_num']      = $click[0]->click_num;
                }
                $itm['first_click_num']    = $first_click_num ?? 0;
                //补充每个渠道的历史累计注册ip人数
                $itm['register_ip_count'] = $reg_map[$itm['channel_id']]['ip_count'] ?? 0;
                $itm['first_register_num'] = $first_reg_map[$itm['channel_id']]['num'] ?? 0;
                //补充每个渠道的新注册ip人数
                $itm['first_register_ip_count'] = $first_reg_map[$itm['channel_id']]['ip_count'] ?? 0;
                //补充渠道名称
                $itm['channel_name'] = $channel_map2[$itm['channel_id']] ?? "original";
                //补充每个渠道的历史累计注册人数
                $itm['register_num'] = $register_count;
                //月俸字段要除以100保留两位小数
                $itm['award_money'] = $yf_map[$itm['channel_id']] ?? 0;
                //存取款差额
                $itm['diff_cqk'] = bcsub($itm['cz_amount'], $itm['qk_amount'], 2);
                //投注派彩差额
                $itm['diff_tzpc'] = bcsub($itm['tz_amount'], $itm['pc_amount'], 2);
                //首充投注派彩差额
                $itm['first_diff_tzpc'] = bcsub($itm['first_bet'], $itm['first_prize'], 2);
                //新注册人数
                $itm['first_register_num'] = $first_register_count;
                $exp_data[] = $itm;
                unset($itm);
            }
        }

        $title = [
            "channel_id" => "渠道号",
            "channel_name" => "渠道名称",
            "register_num" => "注册人数",
            "click_num"                 => "总访问次数",
            "distinct_click"            => "独立访问次数",
            "first_click_num"           => "新增访问次数",
            "register_ip_count"         => "总注册IP数",
            "first_register_num" => "新增注册人数",
            "first_register_ip_count"   => "新增注册IP数",
            "first_recharge_user" => "新充人数",
            "first_recharge" => "新充充值金额",
            "first_withdraw" => "新充取款金额",
            "first_bet" => "新充投注金额",
            "first_prize" => "新充派彩金额",
            "first_diff_tzpc" => "新充投注派彩差",
            "cz_person" => "充值人数",
            "cz_amount" => "充值金额",
            "qk_person" => "取款人数",
            "qk_amount" => "取款金额",
            "diff_cqk" => "存取款差额",
            "tz_amount" => "投注金额",
            "pc_amount" => "派彩金额",
            "diff_tzpc" => "投注派彩差额",
            "hd_amount" => "活动彩金",
            "hs_amount" => "回水彩金",
            "js_amount" => "晋升彩金",
            "zk_amount" => "转卡彩金",
            "fyz_amount" => "返佣总金额",
            "award_money" => "月俸",
            "count_date" => "统计日期",
        ];
        $this->exportExcel("渠道报表汇总",$title, $exp_data);
        exit();
    }

    public function exportExcel($file, $title, $data) {
        header('Content-type:application/vnd.ms-excel');
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