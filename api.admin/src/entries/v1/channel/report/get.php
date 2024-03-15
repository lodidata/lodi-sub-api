<?php

use Logic\Admin\BaseController;

return new class () extends BaseController {
    const TITLE = '渠道管理-渠道报表';
    const DESCRIPTION = '获取报表';
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
        "first_diff_tzpc" => "新投注派彩差",
        "click_num" => "总访问次数",
        "distinct_click" => "独立访问次数",
    ];

    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run()
    {
        $page = $this->request->getParam('page', 1);
        $page_size = $this->request->getParam('page_size', 20);
        $date_start = $this->request->getParam('date_start', "");
        $date_end = $this->request->getParam('date_end', "");
        $channel_no = $this->request->getParam('channel_no', '');
        $channel_name = $this->request->getParam('channel_name', '');
        $new_no = 0;

        //获取允许访问的渠道
        $channel_ids = $this->channelAuth();
        if(empty($channel_ids)){
            $attr = [
                'total' => 0,
                'size' => $page_size,
                'number' => 0,
                'current_page' => 0,
                //当前页数
                'last_page' => 0, //最后一页数
            ];
            return $this->lang->set(0, [], [], $attr);
        }

        //没有传递时间，则查询全部数据
        if (empty($date_start) || empty($date_end)) {
            $sql = \DB::connection('slave')->table('rpt_channel_total')->whereRaw('channel_id is not null');
        } else {
            $sql = \DB::connection('slave')->table('rpt_channel')->whereRaw('count_date>=? and count_date<=? and channel_id is not null', [$date_start, $date_end]);
        }
        if (!empty($channel_no)) {
            $sql->whereIn('channel_id', explode(',', $channel_no));
        }
        $sql->whereIn('channel_id', $channel_ids);
        if (!empty($channel_name)) { //根据渠道名称查询
            $sql->where('channel_name', $channel_name);
        }
        $data = $sql->groupBy(['channel_id'])->paginate($page_size, [
            DB::raw('channel_id,channel_name,sum(cz_amount) as cz_amount,
            sum(qk_person) as qk_person,sum(qk_amount) as qk_amount,sum(tz_amount) as tz_amount,sum(pc_amount) as pc_amount,sum(hd_amount) as hd_amount,sum(hs_amount) as hs_amount,
            sum(js_amount) as js_amount,sum(zk_amount) as zk_amount,sum(fyz_amount) as fyz_amount,sum(first_recharge_user) as first_recharge_user,sum(first_recharge) as first_recharge,
            sum(click) as first_click_num,sum(award_money) as award_money,sum(first_withdraw) as first_withdraw,
            sum(first_bet) as first_bet,sum(first_prize) as first_prize')
        ], 'page', $page)->toJson();
        $data = json_decode($data, true);
        
        if ($data['data']) {
            //统计每个渠道历史累计注册人数
            $reg = \DB::connection('slave')->table('user')->selectRaw('IFNULL(channel_id,"default") as channel_id,count(id) as num,count(distinct login_ip) as ip_count')
                ->groupBy(['channel_id'])->get()->toArray();
            $first_reg = \DB::connection('slave')->table('user')->selectRaw('IFNULL(channel_id,"default") as channel_id,count(id) as num,count(distinct login_ip) as ip_count')
                ->where('created', '>=', $date_start)->where('created', '<=', $date_end . ' 23:59:59')->groupBy(['channel_id'])->get()->toArray();
            //每个渠道的总访问次数、独立访问次数 【如果不要求实时性，可以通过缓存每小时或每天更新一次】
            // $visit = \DB::connection('slave')->table('user_channel_logs')->selectRaw('IFNULL(channel_id,"default") as channel_id,count(distinct log_ip) as distinct_click,count(log_ip) as click_num')
            //         ->groupBy(['channel_id'])->get()->toArray();

            $reg_map = [];
            $first_reg_map = [];
            foreach ($reg as $g) {
                $cur_no = $g->channel_id;
                if (empty($g->channel_id)) {
                    $cur_no = 'default';
                }
                $reg_map[$cur_no]['num'] = $g->num;
                $reg_map[$cur_no]['ip_count'] = $g->ip_count;
            }
            foreach ($first_reg as $g) {
                $cur_no = $g->channel_id;
                if (empty($g->channel_id)) {
                    $cur_no = 'default';
                }
                $first_reg_map[$cur_no]['num'] = $g->num;
                $first_reg_map[$cur_no]['ip_count'] = $g->ip_count;
            }
            // foreach ($visit as $g) {
            //     $cur_no = $g->channel_id;
            //     if (empty($g->channel_id)) {
            //         $cur_no = 'default';
            //     }
            //     $visit_map[$cur_no]['distinct_click'] = $g->distinct_click;
            //     $visit_map[$cur_no]['click_num'] = $g->click_num;
            // }

            //统计充值人数按用户去重，按渠道分组。
            $cz_sql = DB::connection('slave')->table('rpt_user')->leftJoin('user', 'user.id', '=', 'rpt_user.user_id')
                ->selectRaw('count(DISTINCT rpt_user.user_id) as num, IFNULL(user.channel_id,"default") as channel_id');
            if (empty($date_start) || empty($date_end)) {
                $all_cz_num = $cz_sql->whereRaw('deposit_user_cnt>?', [0])->groupBy(['user.channel_id'])->get()->toArray();
            } else {
                $all_cz_num = $cz_sql->whereRaw('rpt_user.count_date>=? and rpt_user.count_date<=? and deposit_user_cnt>?', [$date_start, $date_end, 0])->groupBy(['user.channel_id'])->get()->toArray();
            }
            $all_cz_map = [];
            if (!empty($all_cz_num)) {
                foreach ($all_cz_num as $acz) {
                    $all_cz_map[$acz->channel_id] = $acz->num;
                }
            }
            //统计取款按用户去重，按渠道分组
            $qk_sql = DB::connection('slave')->table('rpt_user')->leftJoin('user', 'user.id', '=', 'rpt_user.user_id')
                ->selectRaw('count(DISTINCT rpt_user.user_id) as num, IFNULL(user.channel_id,"default") as channel_id');
            if (empty($date_start) || empty($date_end)) {
                $all_qk_num = $qk_sql->whereRaw('withdrawal_user_cnt>?', [0])->groupBy(['user.channel_id'])->get()->toArray();
            } else {
                $all_qk_num = $qk_sql->whereRaw('rpt_user.count_date>=? and rpt_user.count_date<=? and withdrawal_user_cnt>?', [$date_start, $date_end, 0])->groupBy(['user.channel_id'])->get()->toArray();
            }
            $all_qk_map = [];
            if (!empty($all_qk_num)) {
                foreach ($all_qk_num as $aqk) {
                    $all_qk_map[$aqk->channel_id] = $aqk->num;
                }
            }
            //补充每个渠道数据字段
            foreach ($data['data'] as &$itm) {
                if (empty($itm['channel_id'])) {
                    $itm['channel_id'] = "original";
                }
                //补充每个渠道的历史累计注册人数
                $itm['register_num'] = $reg_map[$itm['channel_id']]['num'] ?? 0;
                //补充每个渠道的历史累计注册ip人数
                $itm['register_ip_count'] = $reg_map[$itm['channel_id']]['ip_count'] ?? 0;
                //补充每个渠道的新注册人数
                $itm['first_register_num'] = $first_reg_map[$itm['channel_id']]['num'] ?? 0;
                //补充每个渠道的新注册ip人数
                $itm['first_register_ip_count'] = $first_reg_map[$itm['channel_id']]['ip_count'] ?? 0;
                //总访问次数  直接从redis中获取独立ip访问次数 与总访问次数
                $itm['click_num'] = $this->redis->get("channel_total:{$itm['channel_id']}") ?? 0;
                //独立访问次数
                $itm['distinct_click'] = $this->redis->pfcount("channel_distinct:{$itm['channel_id']}") ?? 0;
                //月俸字段在rpt_channel中已经存的是单位元了，这里不用再除以100了
                $itm['award_money'] = empty($itm['award_money']) ? 0 : bcadd($itm['award_money'], 0, 2);
                //存取款差额
                $itm['diff_cqk'] = bcsub($itm['cz_amount'], $itm['qk_amount'], 2);
                //投注派彩差额
                $itm['diff_tzpc'] = bcsub($itm['tz_amount'], $itm['pc_amount'], 2);
                //首充投注派彩差额
                $itm['first_diff_tzpc'] = bcsub($itm['first_bet'], $itm['first_prize'], 2);
                //渠道人数字段需要动态查询，因为按时间段查询时sum报表中数据会有重复的用户数据，需要根据用户id去重统计充值人数
                $itm['cz_person'] = isset($all_cz_map[$itm['channel_id']]) ? $all_cz_map[$itm['channel_id']] : 0;
                $itm['qk_person'] = isset($all_qk_map[$itm['channel_id']]) ? $all_qk_map[$itm['channel_id']] : 0;
            }
        }

        //权限处理
        $data['data'] = $this->listAuth($data['data']);

        $attr = [
            'total' => $data['total'] ?? count($data['data']),
            'size' => $page_size,
            'number' => $data['last_page'] ?? 0,
            'current_page' => $data['current_page'] ?? 0,
            //当前页数
            'last_page' => $data['last_page'] ?? 0, //最后一页数
        ];
        return $this->lang->set(0, [], $data['data'], $attr);
    }

    public function channelAuth(){
        $admin_user = $this->playLoad['uid'];

        //获取所有渠道
        $channel_list = \DB::table('channel_management')->select('number','admin_user')->get()->toArray();
        $channel_nums = [];
        foreach($channel_list as $key => $val){
            if(empty($val->admin_user)){
                continue;
            }
            $arr = explode(',',$val->admin_user);
            if(!in_array($admin_user, $arr)){
                continue;
            }
            $channel_nums[] = $val->number;
        }

        return $channel_nums;
    }

    public function listAuth($data){
        $list_old = [
            1  => 'click_num',               //总访问次数
            2  => 'distinct_click',          //独立访问次数
            3  => 'first_click_num',         //新增访问次数
            4  => 'register_num',            //总注册人数
            5  => 'register_ip_count',       //总注册IP数
            6  => 'first_register_num',      //新增注册人数
            7  => 'first_register_ip_count', //新增注册IP
            8  => 'first_recharge_user',     //新充人数
            9  => 'first_recharge',          //新充充值金额
            10 => 'first_withdraw',          //新充取款金额
            11 => 'first_bet',               //新充投注金额
            12 => 'first_prize',             //新充派彩金额
            13 => 'first_diff_tzpc',         //新充投注派彩差
            14 => 'cz_person',               //充值人数
            15 => 'cz_amount',               //充值金额
            16 => 'qk_person',               //取款人数
            17 => 'qk_amount',               //取款金额
            18 => 'diff_cqk',                //存取款差额
            19 => 'tz_amount',               //投注金额
            20 => 'pc_amount',               //派彩金额
            21 => 'diff_tzpc',               //投注派彩差额
            22 => 'hd_amount',               //活动彩金
            23 => 'hs_amount',               //回水金额
            24 => 'js_amount',               //晋升彩金
            25 => 'zk_amount',               //转卡彩金
            26 => 'fyz_amount',              //返佣总金额
            27 => 'award_money',             //月俸
            28 => 'operate',                 //操作
        ];
        //获取管理员的权限
        $rid = $this->playLoad['rid'];
        $list_auth = [];
        $bool = false;//用于初始化
        $role_info = \DB::table('admin_user_role')->where('id', $rid)->first();
        if(!empty($role_info) ){
            if(!empty($role_info->list_auth)){
                $list_arr = json_decode($role_info->list_auth,true);
                if(!empty($list_arr) && isset($list_arr['channelReport'])){
                    $list_auth = $list_arr['channelReport'];
                }
            }else{
                $bool = true;
            }
        }
        $diff_list = [];
        foreach($list_old as $key => $val){
            if($bool || in_array($key, $list_auth)){
                $diff_list[] = $val;
            }
        }
        $new_data = [];
        foreach($data as $key => $val){
            $item = [];
            foreach($val as $k => $v){
                if($k != 'channel_id' && $k != 'channel_name' && !in_array($k, $diff_list)){
                    continue;
                }
                $item[$k] = $v;
            }
            $item['operate'] = 0;
            if(in_array('operate', $diff_list)){
                $item['operate'] = 1;
            }
            $new_data[] = $item;
        }

        return $new_data;
    }
};