<?php
use Logic\Admin\BaseController;

return new class() extends BaseController
{
    const TITLE = '股东会员报表';
    const DESCRIPTION = '股东会员报表';
    const QUERY = [
        'page' => 'int(required) #当前页',
        'page_size' => 'int(required) #每页数量',
        'date_start' => 'string() #开始日期',
        'date_end' => 'string() #结束日期',
        'agent_id' => 'int() #用户账号',
    ];

    const PARAMS = [];
    const SCHEMAS = [
        [
            //平台详情 - 总投注、总返奖、总盈亏、总成本、总盈利
            'total_tz' => '总投注',
            'total_fj' => '总返奖',
            'total_yk' => '总盈亏(总营收)',
            'total_kk' => '总成本(总扣款)',
            'total_yl' => '总盈利(总营收-总扣款)',
            //平台详情 - 各游戏分类数据统计表
            'game_info' => [
                [
                    'game_type' => '游戏type',
                    'game_type_name' => '游戏中文名称',
                    'game_bet_amount' => '总投注',
                    'game_prize_amount' => '总返奖',
                    'profit' => '盈亏'
                ]
            ],
            //成本明细 - 流水金额、充值兑换、营收金额、平台彩金赠送、API成本
            'platform_info' => [
                'bet_ratio_amount' => '流水金额',
                'deposit_ratio_amount' => '充值兑换',
                'revenue_ratio_amount' => '营收金额',
                'coupon_ratio_amount' => '平台彩金赠送',
                'manual_ratio_amount' => 'API成本',
            ],
            //代理详情 - 代理数据表格
            'agent_details' => [
                [
                    'deal_log_no' => '流水号',
                    'user_id'=>'用户账号',
                    'agent_name'=>'上级代理名称',
                    'agent_cnt'=>'下级代理人数',
                    'bet_amount' => '总流水',
                    'proportion' => '占成',
                    'bkge' => '返佣金额(结算金额)',
                    'date' => '结算时间',
                    'proportion_list',    //游戏占成列表
                    'bet_amount_list',    //游戏投注列表
                ]
            ],
        ],
    ];

    //feel_list json字段中特殊的4各统计字段
    protected $special_field = [
        'coupon_ratio_amount' => '优惠占比金额',
        'manual_ratio_amount' => '人工扣款占比金额',
        'deposit_ratio_amount' => '充值占比金额',
        'revenue_ratio_amount' => '营收占比金额',
    ];
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];

    public function run() {
        $user_name = $this->request->getParam('user_name');
        $agent_name = $this->request->getParam('agent_name');    //上级代理名称
        $date_start = $this->request->getParam('date_start');
        $date_end = $this->request->getParam('date_end');
        $month = date("Y-m", strtotime($this->request->getParam('month'))) ;   //查询月结时需要传递的月份，格式：2022-06
        $page = $this->request->getParam('page',1);
        $page_size = $this->request->getParam('page_size',10);
        $model = $this->request->getParam('model',1);  //1-日结，2-月结

        $resData = [
            'game_info'=>[],
            'platform_info'=> ['bet_ratio_amount'=>0,'deposit_ratio_amount'=>0,'revenue_ratio_amount'=>0,'coupon_ratio_amount'=>0,'manual_ratio_amount'=>0],
            'agent_details'=>[],
            'total_tz' => 0,    //总投注
            'total_fj' => 0,    //总返奖
            'total_yk' => 0,    //总盈亏
            'total_kk' => 0,    //总成本
            'total_yl' => 0,    //总盈利
        ];

        //获取启用中的一级游戏类目信息
        $game_menu_info = DB::connection('slave')->table("game_menu")->whereRaw('pid=? and status=?',  [0,'enabled'])->select(['id','type','rename'])->get()->toArray();
        $game_map = [];      //初始化一个游戏type与其中文名称的映射图谱
        $game_type_data = [];    //先初始化一下每个游戏类型的数据，因为当数据库没有查询到某个游戏分类数据时也要展示
        foreach ($game_menu_info as $item) {
            $game_map[$item->type] = $item->rename;
            $game_type_data[] = [
                'game_type' => $item->type,
                'game_type_name' => $this->lang->text($item->type),
                'game_bet_amount' => 0,    //投注金额，初始化为0
                'game_prize_amount' => 0,    //派奖金额，初始化为0
                'profit' => 0,  //盈亏，初始化为0
                'id' => $item->id
            ];
        }
        //游戏type映射图中补充4各特殊统计字段
        $game_map_ext = array_merge($game_map, $this->special_field);

        //统计平台详情数据
        $tz_sql = "";  //投注json字段sql
        $fj_sql = "";  //返奖json字段sql
        foreach ($game_map as $k=>$v) {
            $tz_sql .= "TRUNCATE(IFNULL(cast(sum(bet_amount_list->'$.{$k}') as decimal(15,2)),0), 2) as tz_{$k} ,";
            $fj_sql .= "TRUNCATE(IFNULL(cast(sum(lose_earn_list->'$.{$k}') as decimal(15,2)),0), 2) as fj_{$k} ,";
        }
        if ($model == 1) {
            $gameInfo = DB::connection('slave')->table('plat_earnlose')->select([DB::raw($tz_sql.$fj_sql.'sum(bet_amount) as z_tz, sum(lose_earn) as z_yk')])->whereRaw('date>=? and date<=? ', [$date_start,$date_end])->get()->toArray();
        } else {
            $gameInfo = DB::connection('slave')->table('plat_earnlose')->select([DB::raw($tz_sql.$fj_sql.'sum(bet_amount) as z_tz, sum(lose_earn) as z_yk')])->whereRaw('date_format(date,"%Y-%m") = ?', [$month])->get()->toArray();
        }

        if (!empty($gameInfo)) {
            $resData['total_tz'] = $gameInfo[0]->z_tz;    //总投注
            $resData['total_yk'] = $gameInfo[0]->z_yk;   //总盈亏
            $resData['total_fj'] = bcsub($gameInfo[0]->z_tz, $gameInfo[0]->z_yk, 2);    //总返奖
            foreach ($game_type_data as $k=>$v) {
                $str_tz = 'tz_'.$v['game_type'];
                $str_fj = 'fj_'.$v['game_type'];
                $game_type_data[$k]['game_bet_amount'] = $gameInfo[0]->$str_tz;     //投注
                $game_type_data[$k]['profit'] = $gameInfo[0]->$str_fj;    //盈亏
                $game_type_data[$k]['game_prize_amount'] = bcsub($game_type_data[$k]['game_bet_amount'], $game_type_data[$k]['profit'], 2);   //返奖(总派奖)
            }
            $resData['game_info'] = $game_type_data;
        }

        $tz_sql = "";  //投注json字段sql
        $zc_sql = "";  //占成json字段sql
        $cb_sql = "";  //成本(扣款)json字段sql
        $self_bet_amount_list_sql = "";  //自身投注json字段sql
        $agent_info_query = DB::connection('slave')->table('unlimited_agent_bkge');
        $agent_field = 'deal_log_no,user_id,user_name,agent_name,agent_cnt,';
        if ($model == 1) {
            //平台详情： 默认展示前一天数统计数据，如有传递时间范围则根据指定时间范围统计
            if (!empty($date_start) && !empty($date_end)) {
                $date_end = date("Y-m-d", strtotime("+1 day", strtotime($date_end)));   //结束日期+1天，SQL用小于符号比较结束时间
            } else {
                $date_start = date("Y-m-d",strtotime("-1 days"));
                $date_end   = date("Y-m-d");
            }

            foreach ($game_map as $k=>$v) {
                $tz_sql .= "TRUNCATE(IFNULL(cast(bet_amount_list->'$.{$k}' as decimal(15,2)),0), 2) as tz_{$k} ,";
                $zc_sql .= "TRUNCATE(IFNULL(cast(proportion_list->'$.{$k}' as decimal(15, 2)),0), 2) as zc_{$k} ,";
                $self_bet_amount_list_sql .= "TRUNCATE(IFNULL(cast(self_bet_amount_list->'$.{$k}' as decimal(15,2)),0), 2) as self_bet_{$k} ,";
            }
            foreach ($game_map_ext as $k=>$v) {
                $cb_sql .= "TRUNCATE(IFNULL(cast(fee_list->'$.{$k}' as decimal(15,2)),0), 2) as cb_{$k} ,";
            }
            $agent_field .= 'bet_amount,self_bet_amount,bkge,settle_amount,fee_amount,'.$tz_sql.$self_bet_amount_list_sql.$zc_sql.$cb_sql.'date';

        } else {
            //月报表-平台详情： 默认展示本月数据，如有传递时间范围则根据指定时间范围统计  注意： 需要按照每个用户每月数据合计的方式计算，即每个用户一条数据。
            $date_start = date("Y-m-01", strtotime(date("Y-m")));
            $date_end = date("Y-m-01", strtotime("+1 month",strtotime($date_start)));
            if ($month) {   //如果传入了指定月份，则查询指定月份数据
                $date_start = date("Y-m-01", strtotime($month));
                $date_end = date("Y-m-01", strtotime("+1 month",strtotime($date_start)));
            }

            foreach ($game_map as $k=>$v) {
                $tz_sql .= "TRUNCATE(IFNULL(cast(sum(bet_amount_list->'$.{$k}') as decimal(15,2)),0), 2) as tz_{$k} ,";
                $zc_sql .= "TRUNCATE(IFNULL(cast(sum(proportion_list->'$.{$k}') as decimal(15,2)),0), 2) as zc_{$k} ,";
                $self_bet_amount_list_sql .= "TRUNCATE(IFNULL(cast(sum(self_bet_amount_list->'$.{$k}') as decimal(15,2)),0), 2) as self_bet_{$k} ,";
            }
            foreach ($game_map_ext as $k=>$v) {
                $cb_sql .= "TRUNCATE(IFNULL(cast(fee_list->'$.{$k}' as decimal(15,2)),0), 2) as cb_{$k} ,";
            }
            $agent_field .= 'sum(bet_amount) as bet_amount,sum(self_bet_amount) as self_bet_amount,sum(bkge) as bkge,sum(settle_amount) as settle_amount,sum(fee_amount) as fee_amount,'.$tz_sql.$self_bet_amount_list_sql.$zc_sql.$cb_sql.'DATE_FORMAT(date,"%Y-%m") as date';
            $agent_info_query ->groupBy(['user_id']);
        }
        $agent_info_query->where('date','>=',$date_start)
            ->where('date','<',$date_end)
            ->orderBy('date','desc');

        if (!empty($agent_name) && !empty($user_name)) {
            $agent_info_query->where('user_name',$user_name)->where('agent_name',$agent_name);  //这里去掉 proportion_list,bet_amount_list两个json字段
        } elseif (!empty($agent_name)) {
            $agent_info_query->where('agent_name',$agent_name);  //这里去掉 proportion_list,bet_amount_list两个json字段
        } elseif (!empty($user_name)) {
            $agent_info_query->where('user_name',$user_name);  //这里去掉 proportion_list,bet_amount_list两个json字段
        }

        $agentInfo = $agent_info_query->paginate($page_size,[DB::raw($agent_field)],'page',$page)->toJson();

        $deAgentInfo = json_decode($agentInfo, true);
        foreach ($deAgentInfo['data'] as $k=>$v) {
            $deAgentInfo['data'][$k]['cb_other'] = sprintf("%01.2f",($v['cb_coupon_ratio_amount'] + $v['cb_manual_ratio_amount'] + $v['cb_deposit_ratio_amount'] + $v['cb_revenue_ratio_amount']));
            $deAgentInfo['data'][$k]['cb_all_games'] = 0;  //游戏流水成本： 将成本明细栏目下每项游戏成本加起来

            foreach ($game_map as $i=>$z) {
                $tag = 'cb_'.$i;
                $deAgentInfo['data'][$k]['cb_all_games'] = bcadd($deAgentInfo['data'][$k]['cb_all_games'], $v[$tag], 2);
            }
        }
        $resData['agent_details'] = $deAgentInfo['data'];

        $platformInfo = DB::connection('slave')->table('plat_earnlose')->whereRaw('date >= :star and date < :end', ['start'=>$date_start,'end'=>$date_end])
            ->select(DB::raw('sum(bet_ratio_amount) as bet_ratio_amount, sum(deposit_ratio_amount) as deposit_ratio_amount, sum(revenue_ratio_amount) as revenue_ratio_amount,
                sum(coupon_ratio_amount) as coupon_ratio_amount, sum(manual_ratio_amount) as manual_ratio_amount, sum(fee_amount) as fee_amount'))
            ->get()->toArray();
        if (!empty($platformInfo)) {
            foreach ($platformInfo as $ptm) {
                $resData['platform_info']['bet_ratio_amount']       = bcadd($resData['platform_info']['bet_ratio_amount'],$ptm->bet_ratio_amount, 2);
                $resData['platform_info']['deposit_ratio_amount']   = bcadd($resData['platform_info']['deposit_ratio_amount'],$ptm->deposit_ratio_amount, 2);
                $resData['platform_info']['revenue_ratio_amount']   = bcadd($resData['platform_info']['revenue_ratio_amount'],$ptm->revenue_ratio_amount, 2);
                $resData['platform_info']['coupon_ratio_amount']    = bcadd($resData['platform_info']['coupon_ratio_amount'],$ptm->coupon_ratio_amount, 2);
                $resData['platform_info']['manual_ratio_amount']    = bcadd($resData['platform_info']['manual_ratio_amount'],$ptm->manual_ratio_amount, 2);
                $resData['total_kk']                                = bcadd($resData['total_kk'], $ptm->fee_amount, 2);    //新的总成本计算方式
            }
        }

        //计算总盈利： 总营收 - 总扣款(总成本)
        $resData['total_yl'] = sprintf("%01.2f", ($resData['total_yk'] - $resData['total_kk']));
        $attr = [
            'total' => isset($deAgentInfo['total']) ? $deAgentInfo['total'] : 0,
            'size' => $page_size,
            'number' => isset($deAgentInfo['last_page']) ? $deAgentInfo['last_page'] : 0,
            'current_page' => isset($deAgentInfo['current_page']) ? $deAgentInfo['current_page'] : 0,    //当前页数
            'last_page' => isset($deAgentInfo['last_page']) ? $deAgentInfo['last_page'] : 0,   //最后一页数
        ];

        return $this->lang->set(0, [], $resData, $attr);
    }

};