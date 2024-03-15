<?php


use Utils\Www\Action;

return new  class() extends Action
{
    const TOKEN = true;
    const TITLE = "个人中心-我的团队列表";
    const DESCRIPTION = "个人中心-我的团队列表     \r\n返回attributes[number =>'第几页', 'size' => '记录条数'， total=>'总记录数', 'team_cnt' => '代理下级代理和用户总数', 'back_money' => '代理下级返佣总金额', 'win_loss' => '代理下级团队盈利总金额', 'deposit_withdrawal' => '代理下级团队提现总金额']";
    const TAGS = "代理返佣";
    const QUERY = [
        "search_uid"    => "string() #用户名",
        "user_name"     => "string() #用户名",
        "start_time"    => "date() #开始日期 2021-08-12",
        "end_time"      => "date() #结束日期 2021-08-20",
        'page'          => "int(,1) #第几页 默认为第1页",
        "page_size"     => "int(,10) #分页显示记录数 默认10条记录"
    ];
    const SCHEMAS = [
        [
            "id"            => "int() #用户ID",
            "user_id"       => "int() #用户ID",
            "name"          => "string() #名称",
            "balance"       => "int() #可用余额 单位:分",
            "real_name"     => "string() #真实姓名",
            "created"       => "dateTime() #注册时间 eg:2017-08-31 02:56:26",
            "register_time" => "dateTime() #注册时间 eg:2017-08-31 02:56:26",
            "agent_id"      => "int() #代理ID",
            "team_cnt"      => "int() #代理下级代理和用户数",
            "deposit_money" => "int() #代理下级团队充值总额",
            "agent_inc_cnt" => "int() #代理下级团队新增代理和用户数",
            "pay_money"     => "int() #代理下级团队投注总金额",
            "prize"         => "int() #代理下级团队派奖总金额",
            "back_money"    => "int() #代理下级团队返佣总金额",
            "withdrawal_money" => "int() #代理下级团队提现总金额",
            "win_loss"      => "int() #代理下级团队盈利总金额",
        ]
    ];

    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $date_start = $this->request->getParam('start_time');
        $date_end   = $this->request->getParam('end_time');
        $page       = $this->request->getParam('page',1);
        $page_size  = $this->request->getParam('page_size',10);
        $user_name  = $this->request->getParam('user_name');
        $search_uid = $this->request->getParam('search_uid');
        $agent_id   = $this->auth->getUserId();

        if(!$date_start || !$date_end) {
            $date_start = date('Y-m-d');
            $date_end   = date('Y-m-d H:i:s');
        }
        $tmpSql = "LEFT JOIN user_agent ON `user`.id = user_agent.user_id 
                LEFT JOIN `profile` ON `user`.id = `profile`.user_id 
                LEFT JOIN `funds` ON `user`.wallet_id = `funds`.id 
                LEFT JOIN ( SELECT 
                    user_id,
                    sum(deposit_user_amount)*100 as deposit_money1,
                    sum(withdrawal_user_amount)*100 as withdrawal_money1,
                    sum(bet_user_amount)*100 as pay_money1,
                    sum(prize_user_amount)*100 as prize1,
                    sum(back_user_amount)*100 as back_money1
                    FROM rpt_user WHERE count_date >= '{$date_start}' AND count_date<= '{$date_end}' and superior_id = {$agent_id} GROUP BY user_id
                    ) rpt_user1 on `user`.id = rpt_user1.user_id
                LEFT JOIN (
                    SELECT 
                    agent_id,
                    agent_cnt as team_cnt,
                    sum(agent_inc_cnt) as agent_inc_cnt,
                    sum(deposit_agent_amount)*100 as deposit_money,
                    sum(withdrawal_agent_amount)*100 as withdrawal_money,
                    sum(bet_agent_amount)*100 as pay_money,
                    sum(prize_agent_amount)*100 as prize,
                    sum(back_agent_amount)*100 as back_money
                    FROM rpt_agent WHERE count_date >= '{$date_start}' AND count_date<= '{$date_end}' GROUP BY(agent_id) ORDER BY count_date DESC
                )rpt_agent ON `user`.id = rpt_agent.agent_id";

        $sql = "SELECT `user`.id,`user`.id AS user_id,`user`.`name`,`funds`.`balance`,`profile`.`name` AS real_name,`user`.created,`user`.created as register_time,rpt_agent.agent_id,rpt_agent.team_cnt,rpt_agent.agent_inc_cnt,(pay_money + pay_money1) as pay_money,(prize + prize1) as prize,(deposit_money1 + deposit_money) as deposit_money,(withdrawal_money1 + withdrawal_money) as withdrawal_money,(back_money + back_money1) as back_money 
                FROM `user` 
               {$tmpSql}";
        $tsql = "SELECT count(1) as count ,sum(team_cnt) as team_cnt,sum(pay_money + pay_money1) as pay_money,sum(prize + prize1) as prize,sum(deposit_money + deposit_money1) as deposit_money,sum(withdrawal_money + withdrawal_money1) as withdrawal_money,sum(back_money + back_money1) as back_money FROM `user` 
                {$tmpSql}";

        if($search_uid) {
            $sql  .= " WHERE user_agent.uid_agent = $search_uid";
            $tsql .= " WHERE user_agent.uid_agent = $search_uid";
        }else {
            $sql  .= " WHERE user_agent.uid_agent = $agent_id";
            $tsql .= " WHERE user_agent.uid_agent = $agent_id";
        }
        if($user_name) {
            $sql  .= " AND user.name = '{$user_name}'";
            $tsql .= " AND user.name = '{$user_name}'";
        }
        $sc     = ($page-1)*$page_size;
//        $page_size = 20;
        $sql    .=" LIMIT {$sc},{$page_size}";
        $total  = (array)\DB::select($tsql);
        $total  = isset($total[0]) ? (array)$total[0] : ['team_cnt'=>0,'pay_money'=>0,'back_money'=>0,'prize'=>0];
        $data   = (array)\DB::select($sql);
        //二级列表得加上自己
        if($search_uid && $agent_id != $search_uid && $page == 1) {
            $search_data = (array)\DB::table('rpt_user')
                ->where('count_date','>=',$date_start)
                ->where('count_date','<=',$date_end)
                ->where('user_id',$search_uid)
                ->first([
                    'register_time as created',
                    \DB::raw("sum(deposit_user_amount)*100 as deposit"),
                    \DB::raw("sum(withdrawal_user_amount)*100 as withdrawal"),
                    \DB::raw("sum(bet_user_amount)*100 as bet"),
                    \DB::raw("sum(prize_user_amount)*100 as prize"),
                    ]);
            $tmp['user_id']          = $tmp['id'] = $search_uid;
            $tmp['name']             = \DB::table('user')->where('id',$search_uid)->value('name') ?? '';
            $tmp['real_name']        = \DB::table('profile')->where('user_id',$search_uid)->value('name') ?? '';
            $tmp["team_cnt"]         = \DB::table('user_agent')->where('user_id',$search_uid)->value('inferisors_all') ?? 0;
            $tmp["back_money"]       = 0;
            $tmp["deposit_money"]    = $search_data['deposit'] ?? 0;
            $tmp["pay_money"]        = $search_data['bet'] ?? 0;
            $tmp["prize"]            = $search_data['prize'] ?? 0;
            $tmp["withdrawal_money"] = $search_data['withdrawal'] ?? 0;
            array_unshift($data,$tmp);
        }

        foreach ($data as &$val) {
            $val = (array)$val;
            $val["back_money"]       = intval($val["back_money"]);
            $val["deposit_money"]    = intval($val["deposit_money"]);
            $val["pay_money"]        = intval($val["pay_money"]);
            $val["prize"]            = intval($val["prize"]);
            $val["team_cnt"]         = \Model\UserAgent::where('user_id',$val['user_id'])->value('inferisors_all');
            $val["withdrawal_money"] = intval($val["withdrawal_money"]);
            $val["win_loss"]         = $val["prize"] - $val["pay_money"];
            $bkge_json               = \DB::table('user_agent')->where('user_id', $val['user_id'])->value('bkge_json');
            $val["game_bkge_list"]   = $bkge_json ? json_decode($bkge_json, true) : [];
        }
        $t = (array)\DB::table('user_agent')->where('user_id', $agent_id)->first();

        $attr['total']              = $total['count'];
        $attr['team_cnt']           =  (int)$t['inferisors_all'] ?? 0;
        $attr['back_money']         = (int)$t['earn_money'] ?? 0;
        $attr['win_loss']           = intval($total['prize']) - intval($total['pay_money']);
        $attr['pay_money']           = intval($total['pay_money']);
        $attr['deposit_withdrawal'] = intval($total['deposit_money']) - intval($total['withdrawal_money']);
        $attr['size']               = $page_size;
        $attr['number']             = $page;

        return $this->lang->set(0,[],$data,$attr);
    }
};
