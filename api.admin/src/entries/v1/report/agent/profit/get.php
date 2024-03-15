<?php
use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const TITLE = '代理盈亏报表';
    const DESCRIPTION = '代理盈亏报表';
    const QUERY = [
        'page' => 'int(required) #当前页',
        'page_size' => 'int(required) #每页数量',
        'date_start' => 'string() #开始日期',
        'date_end' => 'string() #结束日期',
        'user_name' => 'string() #用户账号',
        'agent_name' => 'string() #上级代理名称',
        'model' => 'int() #日结算与月结算',        //1-日结，2-月结
    ];

    const PARAMS = [];
    const SCHEMAS = [
        //平台详情
        "total_tz" => "总投注",
        "total_fj" => "总返奖",    //总返奖 = 总投注 - 总盈亏
        "total_ys" => "总营收",    //总营收
        "total_cb" => "总成本",    //总成本(总扣款)
        "total_jyl" => "净盈利",   //净盈利(总盈利)
        "game_info" => [
            [
                'game_type' => "游戏type",
                'game_type_name' => "游戏中文名称",
                'game_bet_amount' => "总投注",
                'game_prize_amount' => "总返奖",
                'game_profit' => "盈亏"
            ]
        ],
        //成本明细
        "cb_details" => [
            "lose_earn" => "公司盈亏",
            "deposit_ratio_amount" => "充值兑换",
            "coupon_ratio_amount" => "平台彩金",    //平台彩金赠送
            "manual_ratio_amount" => "平台服务",    //股东分红的人工扣款
            "withdrawal_ratio_amount" => "兑换",    //兑换-取款
            "revenue_ratio_amount" => "营收",    //营收
        ],
        //各代理详情 (盈亏结算详情)
        "agent_details" => [
            'deal_log_no' => '流水号',
            'user_id'=>'用户账号',
            'user_name' => '用户名称',
            'agent_name'=>'上级代理名称',
            'agent_cnt'=>'下级代理人数',
            'bet_amount' => '总流水',     //投注额
            'loseearn_amount' =>  '总盈亏',
            //各游戏盈亏列表
            'yk_GAME' => 'GAME盈亏',
            'yk_CP' => 'CP盈亏',
            //各游戏盈亏占比
            'zb_GAME' => 'GAME占比',
            'zb_CP' => 'CP占比',
            //游戏成本明细
            'cb_manual_ratio_amount' => '平台服务',
            'cb_deposit_ratio_amount' => '充值兑换',
            'cb_revenue_ratio_amount' => '公司盈亏',
            'cb_coupon_ratio_amount' => '平台彩金',
            'fee_amount' => '总成本',
            'bkge' => '盈亏返佣',
            'date' => '结算时间'
        ]
    ];

    //feel_list json字段中特殊的4各统计字段
    protected $special_field = [
        'coupon_ratio_amount' => '优惠占比金额',       //代理成本 - 平台彩金
        'manual_ratio_amount' => '人工扣款占比金额',   //代理成本 - 平台服务
        'deposit_ratio_amount' => '充值占比金额',     //代理成本 - 充值兑换
        'revenue_ratio_amount' => '营收占比金额',     //代理成本 - 营收
        'loseearn_ratio_amount' => '盈亏',           //盈亏
        'withdrawal_ratio_amount' => '兑换',         //兑换-取款
    ];
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run() {
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $page = $this->request->getParam('page',1);
        $page_size = $this->request->getParam('page_size',10);
        $date_start = $this->request->getParam('date_start', $yesterday);
        $date_end = $this->request->getParam('date_end', $yesterday);
        $user_name = $this->request->getParam('user_name');      //用户账号
        $agent_name = $this->request->getParam('agent_name');    //上级代理名称
        $model = $this->request->getParam('model',1);            //1-日结，2-月结，3-周结算
        $month = date("Y-m", strtotime($this->request->getParam('month', date('Y-m')))) ;   //查询月结时需要传递的月份，格式：2022-06

        $resData = [];    //返回数据
        if($model == 1 || $model == 3){
            $date_end .= ' 23:59:59';
        }

        //获取启用中的游戏列表
        $game_menu_info = DB::connection('slave')->table("game_menu")->whereRaw('pid=? and status=?',  [0,'enabled'])->select(['id','type','rename'])->get()->toArray();
        $game_map = [];   //游戏type映射游戏中文名称
        foreach ($game_menu_info as $item) {
            $game_map[$item->type] = $item->rename;
        }

        //平台详情数据：总投注、总营收、总返奖、总成本、净盈利
        $tz_sql = "";
        $yk_sql = "";
        foreach ($game_map as $k=>$v) {
            $tz_sql .= "cast(sum(bet_amount_list->'$.{$k}') as decimal(15,2)) as tz_{$k} ,";
            $yk_sql .= "cast(sum(lose_earn_list->'$.{$k}') as decimal(15,2)) as fj_{$k} ,";
        }
        if ($model == 1) {   //日结算
            $plat_data = DB::connection('slave')->table('agent_plat_earnlose')->select([DB::raw($tz_sql.$yk_sql.'sum(bet_amount) as z_tz, sum(revenue_amount) as z_ys,sum(deposit_ratio_amount) as deposit_ratio_amount,
             sum(loseearn_ratio_amount) as lose_earn, sum(coupon_ratio_amount) as coupon_ratio_amount, sum(manual_ratio_amount) as manual_ratio_amount, sum(fee_amount) as fee_amount,
             sum(withdrawal_ratio_amount) as withdrawal_ratio_amount, sum(revenue_ratio_amount) as revenue_ratio_amount')])
                ->whereRaw('created>=? and created<=? ', [$date_start,$date_end])->get()->toArray();
        } elseif ($model == 3) {    //周结算
            $plat_data = DB::connection('slave')->table('agent_plat_week_earnlose')->select([DB::raw($tz_sql.$yk_sql.'sum(bet_amount) as z_tz, sum(revenue_amount) as z_ys, sum(deposit_ratio_amount) as deposit_ratio_amount, 
            sum(loseearn_ratio_amount) as lose_earn, sum(coupon_ratio_amount) as coupon_ratio_amount, sum(manual_ratio_amount) as manual_ratio_amount, sum(fee_amount) as fee_amount,
            sum(withdrawal_ratio_amount) as withdrawal_ratio_amount, sum(revenue_ratio_amount) as revenue_ratio_amount')])
                ->whereRaw('created>=? and created<=? ', [$date_start,$date_end])->get()->toArray();
        } else {    //月结算
            $plat_data = DB::connection('slave')->table('agent_plat_month_earnlose')->select([DB::raw($tz_sql.$yk_sql.'sum(bet_amount) as z_tz, sum(revenue_amount) as z_ys, sum(deposit_ratio_amount) as deposit_ratio_amount,
            sum(loseearn_ratio_amount) as lose_earn, sum(coupon_ratio_amount) as coupon_ratio_amount, sum(manual_ratio_amount) as manual_ratio_amount, sum(fee_amount) as fee_amount,
            sum(withdrawal_ratio_amount) as withdrawal_ratio_amount, sum(revenue_ratio_amount) as revenue_ratio_amount')])
                ->whereRaw('date = ?', [$month])->get()->toArray();
        }
        $resData['total_tz'] = $plat_data[0]->z_tz ?? 0;    //总投注
        $resData['total_ys'] = $plat_data[0]->z_ys ?? 0;    //总营收
        $resData['total_fj'] = bcsub($resData['total_tz'], $resData['total_ys'], 2);    //总返奖 = 总投注 - 总营收
        $resData['total_cb'] = $plat_data[0]->fee_amount ?? 0;    //总成本(总扣款)
        $resData['total_jyl'] = bcsub($resData['total_ys'], $resData['total_cb'], 2);   //净盈利 = 总营收 - 总成本(总扣款)
        //平台数据：游戏分类数据
        foreach ($game_map as $t => $n) {
            $str_tz = 'tz_'.$t;
            $str_yk = 'fj_'.$t;
            $resData['game_info'][] = [
                'game_type' => $t,
                'game_type_name' => $n,
                'game_bet_amount' => $plat_data[0]->$str_tz ?? 0,    //投注
                'game_profit' => $plat_data[0]->$str_yk ?? 0,        //盈亏
                'game_prize_amount' => bcsub(($plat_data[0]->$str_tz ?? 0), ($plat_data[0]->$str_yk ?? 0), 2)   //返奖(总派奖) = 投注 - 盈亏
            ];
        }
        //成本明细
        $resData['cb_details']['lose_earn'] = bcadd(($plat_data[0]->lose_earn ?? 0), 0, 2);    //公司盈亏
        $resData['cb_details']['deposit_ratio_amount'] = bcadd(($plat_data[0]->deposit_ratio_amount ?? 0), 0, 2);    //充值兑换
        $resData['cb_details']['coupon_ratio_amount'] = bcadd(($plat_data[0]->coupon_ratio_amount ?? 0), 0,2);       //平台彩金
        $resData['cb_details']['manual_ratio_amount'] = bcadd(($plat_data[0]->manual_ratio_amount ?? 0), 0, 2);      //平台服务(人工扣款)
        $resData['cb_details']['withdrawal_ratio_amount'] = bcadd(($plat_data[0]->withdrawal_ratio_amount ?? 0), 0, 2);      //兑换
        $resData['cb_details']['revenue_ratio_amount'] = bcadd(($plat_data[0]->revenue_ratio_amount ?? 0), 0, 2);      //营收

        //代理结算详情
        $game_map_ext = array_merge($game_map, $this->special_field);    //游戏type映射图中补充4各特殊统计字段
        $cb_sql = "";  //成本json字段sql
        $yk_sql = "";  //盈亏json字段sql
        $zb_sql = "";  //盈亏占比json字段sql
        $bet_sql = "";
        foreach ($game_map as $k=>$v) {
            $yk_sql .= "cast(loseearn_amount_list->'$.{$k}' as decimal(15,2)) as yk_{$k} ,";
            $zb_sql .= "cast(proportion_list->'$.{$k}' as decimal(15,2)) as zb_{$k} ,";
            $bet_sql .= "cast(bet_amount_list->'$.{$k}' as decimal(15,2)) as bet_{$k} ,";
        }
        foreach ($game_map_ext as $k=>$v) {
            $cb_sql .= "cast(fee_list->'$.{$k}' as decimal(15,2)) as cb_{$k} ,";
        }
        if ($model == 1) {   //日结算
            if (!empty($agent_name) && !empty($user_name)) {
                $agentSql = DB::connection('slave')->table('agent_loseearn_bkge')->whereRaw('user_name = ? and agent_name = ? and created >= ? and created <= ?', [$user_name, $agent_name, $date_start, $date_end]);
            } elseif (!empty($agent_name)) {    //查询目标代理下所有子级代理数据
                $agentSql = DB::connection('slave')->table('agent_loseearn_bkge')->whereRaw('agent_name = ? and created >= ? and created <= ?', [$agent_name, $date_start, $date_end]);
            } elseif (!empty($user_name)) {   //查询指定账号的数据
                $agentSql = DB::connection('slave')->table('agent_loseearn_bkge')->whereRaw('user_name = ? and created >= ? and created <= ?', [$user_name, $date_start, $date_end]);
            } else {
                $agentSql = DB::connection('slave')->table('agent_loseearn_bkge')->whereRaw('created >= :start and created <= :end', ['start'=>$date_start,'end'=>$date_end]);
            }
            $agentInfo = $agentSql->orderBy('date','desc')->paginate($page_size,[DB::raw('deal_log_no,user_id,user_name,agent_name,agent_cnt,bet_amount,loseearn_amount,bkge,fee_amount,'.$cb_sql.$yk_sql.$zb_sql.$bet_sql.'DATE_FORMAT(created,"%Y-%m-%d") as date')], 'page',$page)->toJson();
        } elseif ($model == 3) {   //周结算
//            $week_start = date("Y-m-d", (time() - ((date('w') == 0 ? 7 : date('w')) -1) * 24 * 3600));   //本周一
//            $week_end = date("Y-m-d", (time() + (7 - (date('w') == 0 ? 7 : date('w'))) * 24 * 3600));    //本周日
            if (!empty($agent_name) && !empty($user_name)) {
                $agentSql = DB::connection('slave')->table('agent_loseearn_week_bkge')->whereRaw('user_name=? and agent_name=? and created>=? and created<=?', [$user_name, $agent_name, $date_start,$date_end])->groupBy(['user_id']);
            } elseif (!empty($agent_name)) {    //查询目标代理下所有子级代理数据
                $agentSql = DB::connection('slave')->table('agent_loseearn_week_bkge')->whereRaw('agent_name = ? and created>=? and created<=?', [$agent_name, $date_start,$date_end])->groupBy(['user_id']);
            } elseif (!empty($user_name)) {   //查询指定账号的数据
                $agentSql = DB::connection('slave')->table('agent_loseearn_week_bkge')->whereRaw('user_name = ? and created>=? and created<=?', [$user_name, $date_start,$date_end])->groupBy(['user_id']);
            } else {
                $agentSql = DB::connection('slave')->table('agent_loseearn_week_bkge')->whereRaw('created>=? and created<=?', [$date_start,$date_end])->groupBy(['user_id']);
            }
            $agentInfo = $agentSql->orderBy('date','desc')->paginate($page_size,[DB::raw('deal_log_no,user_id,user_name,agent_name,agent_cnt,sum(bet_amount) as bet_amount,'.
                'sum(loseearn_amount) as loseearn_amount,sum(bkge) as bkge, sum(fee_amount) as fee_amount,'.$yk_sql.$zb_sql.$cb_sql.$bet_sql.'DATE_FORMAT(created,"%Y-%m-%d") as date')], 'page',$page)->toJson();
        } else {    //月结算
            if (!empty($agent_name) && !empty($user_name)) {
                $agentSql = DB::connection('slave')->table('agent_loseearn_month_bkge')->whereRaw('user_name = ? and agent_name = ? and date = ?', [$user_name, $agent_name, $month])->groupBy(['user_id']);
            } elseif (!empty($agent_name)) {    //查询目标代理下所有子级代理数据
                $agentSql = DB::connection('slave')->table('agent_loseearn_month_bkge')->whereRaw('agent_name = ? and date = ?', [$agent_name, $month])->groupBy(['user_id']);
            } elseif (!empty($user_name)) {   //查询指定账号的数据
                $agentSql = DB::connection('slave')->table('agent_loseearn_month_bkge')->whereRaw('user_name = ? and date = ?', [$user_name, $month])->groupBy(['user_id']);
            } else {
                $agentSql = DB::connection('slave')->table('agent_loseearn_month_bkge')->whereRaw('date = ?', [$month])->groupBy(['user_id']);
            }
            $agentInfo = $agentSql->orderBy('date','desc')->paginate($page_size,[DB::raw('deal_log_no,user_id,user_name,agent_name,agent_cnt,sum(bet_amount) as bet_amount,'.
             'sum(loseearn_amount) as loseearn_amount,sum(bkge) as bkge, sum(fee_amount) as fee_amount,'.$yk_sql.$zb_sql.$cb_sql.$bet_sql.'DATE_FORMAT(created,"%Y-%m-%d") as date')], 'page',$page)->toJson();
        }
        $deAgentInfo = json_decode($agentInfo, true);
        //查询用户直属下级代理数
        if (!empty($deAgentInfo['data'])) {
            $uid_list = array_column($deAgentInfo['data'], 'user_id');
            $userAgentData = DB::connection('slave')->table('user_agent')->selectRaw('uid_agent,count(*) as num')->whereIn('uid_agent', $uid_list)->groupBy(['uid_agent'])->get()->toArray();
            $fmt_userAgentData = [];
            foreach ($userAgentData as $uad) {
                $fmt_userAgentData[$uad->uid_agent] = $uad->num;
            }
            foreach ($deAgentInfo['data'] as &$agt) {
                $agt['agent_cnt'] = $fmt_userAgentData[$agt['user_id']] ?? 0;    //将原来的所有下级代理数量替换为该用户的直属下级代理数
            }
        }
        $resData['agent_details'] = $deAgentInfo['data'];
        $resData['game_map'] = $game_map;

        $attr = [
            'total' => $deAgentInfo['total'] ?? 0,
            'size' => $page_size,
            'number' => $deAgentInfo['last_page'] ?? 0,
            'current_page' => $deAgentInfo['current_page'] ?? 0,    //当前页数
            'last_page' => $deAgentInfo['last_page'] ?? 0,   //最后一页数
        ];
        return $this->lang->set(0, [], $resData, $attr);
    }

};