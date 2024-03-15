<?php
/**
 * Created by PhpStorm.
 * User: benchan
 * Date: 2019/1/17
 * Time: 14:39
 */


use Logic\Admin\BaseController;

return new  class() extends BaseController {
    const TITLE = '代理报表';
    const DESCRIPTION = '';

    const QUERY = [
        'date_start'   => 'datetime(required) #开始日期 默认为当前日期',
        'date_end'     => 'datetime(required) #结束日期 默认为当前日期',
        'agent_id'     => "int() #代理ID号",
        'user_name'     => 'string() #会员账号',
        'page'          => "int(,1) #第几页 默认为第1页",
        "page_size"     => "int(,10) #分页显示记录数 默认10条记录",
        'field_id'    => "int() #排序字段  1=下级人数 2=注册人数 3=首充人数 4=总充值人数 5=新增充值金额 6=新增人均充值 7=余额 8=存 9=取 10=存取差 11=投注金额 12=派彩金额 13=差额 14=活动彩金 15=回水金额 16=晋升彩金 17=转卡彩金 18=返佣总金额 19=月俸禄",
        'sort_way'    => "string() #排序规则 desc=降序 asc=升序",
        'proportion_status' => "int #占成类型,1:固定,2:自动,默认为0"
    ];

    const PARAMS = [];
    const SCHEMAS = [
        [
            'agent_cnt' => '代理下級代理和用戶數',
            'agent_inc_cnt' => '代理下級新增代理和用戶數',
            'first_deposit_cnt' => '代理下級首充用戶和代理數',
            'deposit_agent_amount' => '代理下級團隊充值金额',
            'withdrawal_agent_amount' => '代理下級團隊充值金额',
            'bet_agent_amount' => '代理下級團隊投注金额',
            'prize_agent_amount' => '代理下級團隊派獎金额',
            'coupon_agent_amount' => '代理下級團隊活動金额',
            'return_agent_amount' => '代理下級團隊回水金额',
            'turn_card_agent_winnings' => '代理下級團隊轉卡金额',
            'promotion_agent_winnings' => '代理下級團隊晉級金额',
            'back_agent_amount' => '代理下級團隊返佣金额',
            'new_register_deposit_amount' => '新增充值金额(新注册用户总充值的金额 )',
            'new_register_deposit_num' => '新注册用户充值人数',
            'new_register_deposit_avg' => '新增人均',
            'deposit_user_num' => '总充值人数',
        ],
    ];
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];

    public function run() {
        $date_start = $this->request->getParam('date_start',date('Y-m-d'));
        $date_end = $this->request->getParam('date_end',date('Y-m-d'));
        $page = $this->request->getParam('page',1);
        $page_size = $this->request->getParam('page_size',10);
        $agent_id = (int)$this->request->getParam('agent_id');
        $user_name = $this->request->getParam('user_name');
        $proportion_status = $this->request->getParam('proportion_status');

//        $sub_sql_agent = DB::connection('slave')->table("user_agent")->join('user','user.id','=','user_agent.user_id')
//            ->selectRaw('user_agent.user_id')->whereRaw('user.created>=? and user.created<=?', [$date_start." 00:00:00", $date_end." 23:59:59"]);
//        $sub_sc_data = DB::connection('slave')->table("funds_deposit as fd")->joinSub($sub_sql_agent,'sub_sql_agent',"fd.user_id","=","sub_sql_agent.user_id")
//            ->selectRaw('sum(fd.user_id) as cz_people, sum(fd.money) as cz_amount')
//            ->whereRaw('fd.money>? and fd.status=? and FIND_IN_SET("new", fd.state) and fd.created>=? and fd.created<=?', [0,'paid',$date_start." 00:00:00",$date_end." 23:59:59"])
//            ->groupBy(['fd.user_id'])->get()->toArray();
        //下级人数agent_cnt  注册人数agent_inc_cnt    首充人数first_deposit_cnt  总充值人数deposit_user_num   新增充值金额new_register_deposit_amount  新增人均充值new_register_deposit_avg  余额balance_amount  存deposit_agent_amount 取withdrawal_agent_amount 存取差dw_drop_amount 投注金额bet_agent_amount 派彩金额prize_agent_amount  差额bs_drop_amount    活动彩金coupon_agent_amount    回水金额return_agent_amount  晋升彩金promotion_agent_winnings    转卡彩金turn_card_agent_winnings    返佣总金额back_agent_amount

        $field_id = $this->request->getParam('field_id', '');
        $sort_way = $this->request->getParam('sort_way', 'desc');
        if(!in_array($sort_way, ['asc', 'desc'])) $sort_way = 'desc';

        $sort_way = ($sort_way == 'asc') ? "ASC" : "DESC";

        switch ($field_id) {
            case 1:$field_id = 'agent_cnt';break;
            case 2:$field_id = 'agent_inc_cnt';break;
            case 3:$field_id = 'first_deposit_cnt';break;
            case 4:$field_id = 'deposit_user_num';break;
            case 5:$field_id = 'new_register_deposit_amount';break;
            case 6:$field_id = 'new_register_deposit_avg';break;
            case 7:$field_id = 'balance_amount';break;
            case 8:$field_id = 'deposit_agent_amount';break;
            case 9:$field_id = 'withdrawal_agent_amount';break;
            case 10:$field_id = 'dw_drop_amount';break;
            case 11:$field_id = 'bet_agent_amount';break;
            case 12:$field_id = 'prize_agent_amount';break;
            case 13:$field_id = 'bs_drop_amount';break;
            case 14:$field_id = 'coupon_agent_amount';break;
            case 15:$field_id = 'return_agent_amount';break;
            case 16:$field_id = 'promotion_agent_winnings';break;
            case 17:$field_id = 'turn_card_agent_winnings';break;
            case 18:$field_id = 'back_agent_amount';break;
//            case 19:$field_id = 'award';break;
            default:$field_id = 'count_date';break;
        }

        $sql = "SELECT `user`.id AS user_id,`user`.`name` as user_name,`profile`.`name` AS real_name,`user`.created as register_time,rpt_agent.*,
                user_agent.proportion_value,user_agent.proportion_type,user_agent.inferisors_all agent_cnt
                FROM `user` 
                LEFT JOIN user_agent ON `user`.id = user_agent.user_id 
                LEFT JOIN `profile` ON `user`.id = `profile`.user_id 
                LEFT JOIN (
                    SELECT 
                    agent_id,
                    sum(deposit_agent_amount) as deposit_agent_amount,
                    sum(withdrawal_agent_amount) as withdrawal_agent_amount,
                    sum(agent_inc_cnt) as agent_inc_cnt,
                    sum(first_deposit_cnt) as first_deposit_cnt,
                    sum(bet_agent_amount) as bet_agent_amount,
                    sum(prize_agent_amount) as prize_agent_amount,
                    sum(coupon_agent_amount) as coupon_agent_amount,
                    sum(return_agent_amount) as return_agent_amount,
                    sum(turn_card_agent_winnings) as turn_card_agent_winnings,
                    sum(promotion_agent_winnings) as promotion_agent_winnings,
                    sum(back_agent_amount) as back_agent_amount,
                    sum(new_register_deposit_amount) as new_register_deposit_amount,
                    sum(new_register_deposit_num) as new_register_deposit_num,
                    sum(deposit_user_num) as deposit_user_num,
                    balance_amount,
                    (sum(new_register_deposit_amount) / sum(new_register_deposit_num)) as new_register_deposit_avg,
                    (sum(deposit_agent_amount) - sum(withdrawal_agent_amount)) as dw_drop_amount,
                    (sum(bet_agent_amount) - sum(prize_agent_amount)) as bs_drop_amount                   
                    FROM rpt_agent     
                    WHERE `rpt_agent`.count_date >= '{$date_start}' AND `rpt_agent`.count_date <= '{$date_end}' GROUP BY(`rpt_agent`.agent_id) ORDER BY {$field_id} {$sort_way}
                )rpt_agent ON `user`.id = rpt_agent.agent_id
                WHERE 1=1";


        $total = "  SELECT COUNT(1) as count FROM `user` 
                    LEFT JOIN user_agent ON `user`.id = user_agent.user_id
                    LEFT JOIN (
                        SELECT         
                                            agent_id,									
                        sum(deposit_agent_amount) as deposit_agent_amount,
                        sum(withdrawal_agent_amount) as withdrawal_agent_amount,                  
                        sum(bet_agent_amount) as bet_agent_amount                   
                        FROM rpt_agent WHERE count_date >= '{$date_start}' AND count_date<= '{$date_end}' GROUP BY(agent_id) 
                    )rpt_agent ON `user`.id = rpt_agent.agent_id
                    where 1=1
                    ";

        if($user_name) {
            $sql .= " AND user.name = '{$user_name}'";
            $total .= " AND user.name = '{$user_name}'";
        }

        if($proportion_status){
            if($proportion_status == 1){
                $sql .=" and user_agent.proportion_type=1";
            }else{
                $sql .=" and user_agent.proportion_type=2";
            }
        }
        if($agent_id) {
            $sql .= " AND user_agent.uid_agent = $agent_id";
            $total .= " AND user_agent.uid_agent = $agent_id";

            //查询 下级包括自己
            $agent_self = \DB::connection('slave')->table('rpt_user')
                ->leftJoin('user','user.id','=','rpt_user.user_id')
                ->leftJoin('funds','user.wallet_id','=','funds.id')
                ->where('user_id',$agent_id)
                ->where('count_date','>=',$date_start)
                ->where('count_date','<=',$date_end)
                ->first([
                    "user_id AS id",
                    "user_id",
                    "user_name",
                    "real_name",
                    "register_time",
                    \DB::raw("sum(deposit_user_amount) as deposit_agent_amount"),
                    \DB::raw("sum(withdrawal_user_amount) as withdrawal_agent_amount"),
                    \DB::raw("sum(bet_user_amount) as bet_agent_amount"),
                    \DB::raw("sum(prize_user_amount) as prize_agent_amount"),
                    \DB::raw("sum(coupon_user_amount) as coupon_agent_amount"),
                    \DB::raw("sum(return_user_amount) as return_agent_amount"),
                    \DB::raw("sum(turn_card_user_winnings) as turn_card_agent_winnings"),
                    \DB::raw("sum(promotion_user_winnings) as promotion_agent_winnings"),
                    \DB::raw("sum(back_user_amount) as back_agent_amount"),
                    \DB::raw("sum(balance) as balance_amount"),
                ]);
            $agent_self->id = $agent_self->user_id = $agent_self->user_id  ?? $agent_id;
            $agent_self->user_name = $agent_self->user_name  ?? \Model\User::where('id',$agent_id)->value('name');
            $agent_self->agent_inc_cnt = 0;
            $agent_self->agent_cnt = 0;
            $agent_self->first_deposit_cnt = 0;
            $agent_self->new_register_deposit_amount = 0;
            $agent_self->new_register_deposit_num = 0;
            $agent_self->deposit_user_num = 0;
            $agent_self->balance_amount = bcdiv($agent_self->balance_amount, 100,2);
        }

        if(!$user_name && !$agent_id){
            $sql .=" and (rpt_agent.deposit_agent_amount > 0 OR rpt_agent.bet_agent_amount > 0 OR rpt_agent.withdrawal_agent_amount > 0)";
            $total .=" and (rpt_agent.deposit_agent_amount > 0 OR rpt_agent.bet_agent_amount > 0 OR rpt_agent.withdrawal_agent_amount > 0)";
        }

        $sc = ($page-1)*$page_size;
        $sql .=" LIMIT {$sc},{$page_size}";
        $count = (array)\DB::connection('slave')->select($total);
        $data = (array)\DB::connection('slave')->select($sql);

        if(isset($agent_self)) {
            array_unshift($data,$agent_self);
        }
        foreach ($data as &$agent){
            //$agent->award = DB::table('user_monthly_award')->where('user_id',$agent->user_id)->value(DB::raw("sum(award_money) as award_amount")) ?? 0;
            $agent->dw_drop_amount = !empty($agent->dw_drop_amount) ? bcadd($agent->dw_drop_amount, 0, 2) : 0;//存取差额
            $agent->bs_drop_amount = !empty($agent->bs_drop_amount) ? bcadd($agent->bs_drop_amount, 0, 2) : 0;//投注盈亏
//            $agent->award = isset($agent->award) && $agent->award ? $agent->award / 100 : 0;
            $agent->deposit_agent_amount = $agent->deposit_agent_amount ? : 0;
            $agent->withdrawal_agent_amount = $agent->withdrawal_agent_amount ? : 0;
            //$agent->agent_cnt = \Model\UserAgent::where('user_id',$agent->user_id)->value('inferisors_all');
            $agent->agent_cnt = ($agent->agent_cnt - 1) >= 0 ? ($agent->agent_cnt - 1) : 0;
            //带用户名查所有子孙下级
            if($user_name){
                $agent->agent_inc_cnt = \DB::connection('slave')->table('child_agent')
                    ->where('pid','=',$agent->user_id)
                    ->where('create_time','>=',$date_start.' 00:00:00')
                    ->where('create_time','<=',$date_end.' 23:59:59')
                    ->count();
            }else{
                $agent->agent_inc_cnt = $agent->agent_inc_cnt ? : 0;
            }
            if(isset($agent->proportion_type) && $agent->proportion_type == 2){
                $agent->proportion_value='';
            }
            $agent->first_deposit_cnt = $agent->first_deposit_cnt ? : 0;
            $agent->bet_agent_amount = $agent->bet_agent_amount ? : 0;
            $agent->prize_agent_amount = $agent->prize_agent_amount ? : 0;
            $agent->coupon_agent_amount = $agent->coupon_agent_amount ? : 0;
            $agent->return_agent_amount = $agent->return_agent_amount ? : 0;
            $agent->turn_card_agent_winnings = $agent->turn_card_agent_winnings ? : 0;
            $agent->promotion_agent_winnings = $agent->promotion_agent_winnings ? : 0;
            $agent->back_agent_amount = $agent->back_agent_amount ? : 0;
            $agent->new_register_deposit_amount = $agent->new_register_deposit_amount ? : 0;
            $agent->new_register_deposit_num = $agent->new_register_deposit_num ? : 0;
            $agent->new_register_deposit_avg = $agent->new_register_deposit_num ? bcdiv($agent->new_register_deposit_amount, $agent->new_register_deposit_num, 2): 0;
            $agent->deposit_user_num = $agent->deposit_user_num ?? 0;

            //直属注册人数：搜索日期内的直属下级注册人数
//            $sub_data = DB::table("user_agent")->join("user","user.id", "=","user_agent.user_id")
//                ->whereRaw('user_agent.uid_agent = ? and user.created >= ? and user.created <= ?', [$agent->user_id,$date_start." 00:00:00",$date_end." 23:59:59"])
//                ->selectRaw("user_agent.user_id")->get()->toArray();
//            $sub_users = [];
//            if ($sub_data) {
//                foreach ($sub_data as $v) {
//                    array_push($sub_users, $v->user_id);
//                }
//            }
//            $sub_people = count($sub_users);    //直属注册人数：搜索日期内的直属下级注册人数

            //统计所有时间范围内注册的直属用户列表
            $all_sub_data = \DB::connection('slave')->table("user_agent")->selectRaw("user_id")->whereRaw('uid_agent = ?', [$agent->user_id])->get()->toArray();
            $all_sub_users = [];    //所有时间范围内注册的直属用户列表
            if ($all_sub_data) {
                foreach ($all_sub_data as $v) {
                    array_push($all_sub_users, $v->user_id);
                }
            }
            //统计整个代理线，但除了自身后的总月俸禄
            $yf_sql = "SELECT SUM(award_money) award ".
            "FROM user_monthly_award as uma RIGHT JOIN (select pid, cid from child_agent where pid = {$agent->user_id}) ca ".
            "ON uma.user_id = ca.cid WHERE uma.award_date>= '{$date_start}' AND uma.award_date<= '{$date_end}'";
            $yf_data = (array)\DB::connection('slave')->select($yf_sql);
            if (isset($yf_data[0]->award)) {
                $agent->award = bcdiv($yf_data[0]->award, 100, 2);
            } else {
                $agent->award =  0;
            }

            //直属充值人数，直属充值金额
//            $sub_cz_data = DB::table("funds_deposit")->selectRaw('sum(money) as money,user_id')->whereIn('user_id', $all_sub_users)
//                ->whereRaw('status = ? and created>=? and created<=?', ['paid',$date_start." 00:00:00",$date_end." 23:59:59"])
//                ->groupBy(['user_id'])->get()->toArray();
            $sub_cz_data = \DB::connection('slave')->table("funds_deposit as fd")->selectRaw('sum(fd.money) as money,fd.user_id')->join("user_agent","fd.user_id","=","user_agent.user_id")
                ->whereRaw('fd.money>? and fd.status = ? and fd.created>=? and fd.created<=? and user_agent.uid_agent=?', [0,'paid',$date_start." 00:00:00",$date_end." 23:59:59",$agent->user_id])->groupBy(['fd.user_id'])->get()->toArray();
            $sub_cz_people = count($sub_cz_data);   //直属充值人数
            $sub_cz_money = 0;    //直属充值金额
            if ($sub_cz_data) {
                foreach ($sub_cz_data as $v) {
                    $sub_cz_money = bcadd($sub_cz_money, $v->money, 2);
                }
            }
            //直属新增人均充值：搜索日期注册的直属下级人数平均充值金额
//            $sub_cz_part_data = DB::table("funds_deposit")->selectRaw('sum(money) as money')->whereIn('user_id', $sub_users)
//                ->whereRaw('status = ? and created>=? and created<=?', ['paid',$date_start." 00:00:00",$date_end." 23:59:59"])
//                ->groupBy(['user_id'])->get()->toArray();
            //直属注册人数：搜索日期内的直属下级注册人数
            $sub_data = DB::connection('slave')->table("user_agent")->join("user","user.id", "=","user_agent.user_id")
                ->whereRaw('user_agent.uid_agent = ? and user.created >= ? and user.created <= ?', [$agent->user_id,$date_start." 00:00:00",$date_end." 23:59:59"])
                ->selectRaw("user_agent.user_id")->get()->toArray();
            $sub_zc_uid_list = array_column($sub_data, "user_id");
            $sub_people = count($sub_zc_uid_list);    //直属注册人数：搜索日期内的直属下级注册人数
            $sub_cz_part_data = DB::connection('slave')->table("funds_deposit")->selectRaw('sum(funds_deposit.money) as money')
                ->whereIn('user_id', $sub_zc_uid_list)
                ->whereRaw('funds_deposit.money>? and funds_deposit.status = ? and funds_deposit.created>=? and funds_deposit.created<=?', [0,'paid',$date_start." 00:00:00",$date_end." 23:59:59"])
                ->groupBy(['funds_deposit.user_id'])->get()->toArray();
            $sub_cz_avg = 0;
            if ($sub_cz_part_data) {
                $count_money = 0;
                foreach ($sub_cz_part_data as $v) {
                    $count_money = bcadd($count_money, $v->money, 2);
                }
                $sub_cz_avg = bcdiv($count_money, count($sub_cz_part_data), 2);
            }
            //直属兑换金额(取款金额)
//            $sub_qk_data = DB::table("funds_withdraw")->selectRaw('sum(money) as money')->whereIn('user_id', $all_sub_users)
//                ->whereRaw('status=? and created>=? and created<=?', ['paid',$date_start." 00:00:00",$date_end." 23:59:59"])->get()->toArray();
            $sub_qk_data = DB::connection('slave')->table("funds_withdraw as fw")->selectRaw('sum(fw.money) as money')->join("user_agent","fw.user_id","=","user_agent.user_id")
                ->whereRaw('fw.status=? and fw.created>=? and fw.created<=? and user_agent.uid_agent=?', ['paid',$date_start." 00:00:00",$date_end." 23:59:59",$agent->user_id])->get()->toArray();
            $sub_qk_num = 0;
            foreach ($sub_qk_data as $v) {
                $sub_qk_num = bcadd($sub_qk_num, $v->money, 2);
            }

            //直属首充人数，直属首充金额
//            $sub_sc_data = DB::table("funds_deposit")->selectRaw('id,user_id,money')->whereIn('user_id', $all_sub_users)
//                ->whereRaw('status=? and FIND_IN_SET("new", state) and created>=? and created<=?', ['paid',$date_start." 00:00:00",$date_end." 23:59:59"])
//                ->groupBy(['user_id'])->get()->toArray();
            $sub_sc_data = DB::connection('slave')->table("funds_deposit as fd")->selectRaw('fd.id,fd.user_id,fd.money')->join("user_agent","fd.user_id","=","user_agent.user_id")
                ->whereRaw('fd.money>? and fd.status=? and FIND_IN_SET("new", fd.state) and fd.created>=? and fd.created<=? and user_agent.uid_agent=?', [0,'paid',$date_start." 00:00:00",$date_end." 23:59:59",$agent->user_id])
                ->groupBy(['fd.user_id'])->get()->toArray();
            $sub_sc_people = count($sub_sc_data);    //直属首充人数
            $sub_sc_people_list = [];    //直属首充的用户uid列表
            $sub_sc_money = 0;    //直属首充金额
            if ($sub_sc_data) {
                foreach ($sub_sc_data as $v) {
                    $sub_sc_money = bcadd($sub_sc_money, $v->money, 2);
                    array_push($sub_sc_people_list, $v->user_id);
                }
            }
            //直属首充兑换金额
            $sub_sc_qk_num = 0;
//            $sub_sc_qk_data = DB::table("rpt_user")->selectRaw('sum(withdrawal_user_amount) as money')->whereIn('user_id', $all_sub_users)
//                ->whereRaw('first_deposit=? and count_date>=? and count_date<=?', [1,$date_start,$date_end])->get()->toArray();
            $sub_sc_qk_data = DB::connection('slave')->table("rpt_user as ru")->selectRaw('sum(ru.withdrawal_user_amount) as money')
                ->join("user_agent","ru.user_id","=","user_agent.user_id")
                ->whereRaw('ru.first_deposit=? and ru.count_date>=? and ru.count_date<=? and user_agent.uid_agent = ?', [1,$date_start,$date_end,$agent->user_id])->get()->toArray();
            foreach ($sub_sc_qk_data as $v) {
                $sub_sc_qk_num = bcadd($sub_sc_qk_num, $v->money, 2);
            }
            $agent->sub_people = $sub_people;          //直属注册人数
            $agent->sub_cz_people = $sub_cz_people;    //直属充值人数
            $agent->sub_cz_money = bcdiv($sub_cz_money, 100, 2);      //直属充值金额
            $agent->sub_qk_num = bcdiv($sub_qk_num, 100, 2);          //直属兑换(取款)金额
            $agent->sub_sc_people = $sub_sc_people;    //直属首充人数
            $agent->sub_sc_money = bcdiv($sub_sc_money, 100,2);      //直属首充金额
            $agent->sub_sc_qk_num = $sub_sc_qk_num;    //直属首充兑换(取款)金额
            $agent->sub_cz_avg = bcdiv($sub_cz_avg, 100, 2);          //直属新增人均充值
        }

        if($field_id != 'count_date' && !empty($agent_id))
        {
            //二维数组排序
            $volume = array_column($data, $field_id);
            if($sort_way == 'ASC')
            {
                array_multisort($volume, SORT_ASC, $data);
            }else{
                array_multisort($volume, SORT_DESC, $data);
            }

        }

        //总代理数: 历史开通的代理人数
        $total_agent_num = DB::connection('slave')->table("user")->whereRaw('agent_switch=?', [1])->count();
        //新增代理: 筛选时间内新开通的代理数;
        $new_agent_num = DB::connection('slave')->table("user")
            ->whereRaw('CASE WHEN agent_time is null THEN created >= ? and created<=? ELSE  agent_time >= ? and agent_time<=? END AND agent_switch = 1', [$date_start." 00:00:00", $date_end." 23:59:59",$date_start." 00:00:00", $date_end." 23:59:59",1])
            ->count();
        //活跃代理: 筛选时间内有下级注册的代理;
        $active_agent = DB::connection('slave')->table('child_agent')->selectRaw('COUNT(DISTINCT pid) as num')
            ->whereRaw('create_time>=? and create_time<=?', [$date_start." 00:00:00", $date_end." 23:59:59"])->get()->toArray();
        $active_agent_num = $active_agent[0]->num ?? 0;
        //下级新增注册总人数：筛选时间内有上级的注册总人数
        $sub_new = DB::connection('slave')->table('child_agent')->selectRaw('COUNT(DISTINCT cid) as num')
            ->whereRaw('create_time>=? and create_time<=?', [$date_start." 00:00:00", $date_end." 23:59:59"])->get()->toArray();
        $sub_new_num = $sub_new[0]->num ?? 0;
        //新充总人数： 筛选时间内注册的用户，在筛选时间内有首充的用户数
//        $sub_sql_agent = DB::connection('slave')->table("user_agent")->join('user','user.id','=','user_agent.user_id')
//            ->selectRaw('user_agent.user_id')->whereRaw('user.created>=? and user.created<=?', [$date_start." 00:00:00", $date_end." 23:59:59"]);
        //TODO 后面又说不需要限制 用户是筛选时间段内注册的
//        $sub_sc_data = DB::connection('slave')->table("funds_deposit as fd")->joinSub($sub_sql_agent,'sub_sql_agent',"fd.user_id","=","sub_sql_agent.user_id")
        $sub_sc_data = DB::connection('slave')->table("funds_deposit as fd")->selectRaw('fd.user_id,fd.created')
            ->whereRaw('fd.money>? and fd.status=? and FIND_IN_SET("new", fd.state) and fd.created>=? and fd.created<=?', [0,'paid',$date_start." 00:00:00",$date_end." 23:59:59"])
            ->groupBy(['fd.user_id'])->get()->toArray();
        $cz_people = 0;   //新充人数
        //新充总金额: 筛选的时间范围内，用户是首充这天整天的充值金额
        $cz_amount = 0;   //新充总金额
        if (!empty($sub_sc_data)) {
            $cz_people = count($sub_sc_data);
            foreach ($sub_sc_data as $val) {
                $tem_start = date("Y-m-d". " 00:00:00", strtotime($val->created));
                $tem_end = date("Y-m-d". " 23:59:59", strtotime($val->created));
                $tem_uid = $val->user_id;
                $tem_money = DB::connection('slave')->table('funds_deposit')->selectRaw('sum(money) as money')
                    ->whereRaw('user_id=? and created>=? and created<=?',[$tem_uid,$tem_start,$tem_end])->get()->toArray();
                $cz_amount += bcdiv($tem_money[0]->money, 100, 2);
            }
        }
        //转化率： 新充人数/注册人数 百分比
        $cr = empty($new_agent_num) ? 0 : bcdiv($cz_people, $new_agent_num, 2) * 100 . "%";
        //人均付费： 新充当日累计充值金额/新充人数
        $avg_pay = empty($cz_people) ? 0 : bcdiv($cz_amount, $cz_people, 2);

        $attr['all_agent_num'] = $total_agent_num;    //总代理数
        $attr['new_agent_num'] = $new_agent_num;    //新增代理
        $attr['active_agent_num'] = $active_agent_num;     //活跃代理
        $attr['sub_new_num'] = $sub_new_num;               //下级新增注册总人数
        $attr['cz_people'] = $cz_people;            //新充人数
        $attr['cz_amount'] = $cz_amount;            //新充总金额
        $attr['cr'] = $cr;                          //转化率
        $attr['avg_pay'] = $avg_pay;                //人均付费

        $attr['total'] = isset($count[0]) ? ((array)$count[0])['count'] : 0;
        $attr['size'] = $page_size;
        $attr['number'] = $page;
        return $this->lang->set(0, [], $data, $attr);

    }
};
