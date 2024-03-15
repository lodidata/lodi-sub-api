<?php
use Utils\Www\Action;

return new class() extends Action
{
    const TITLE = '代理报表';
    const DESCRIPTION = '获取代理报表，包括所有下级的数据';
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
            //平台详情
            'platform_info' => [
                'game_type' => '游戏类型',
                'game_bet_amount' => '投注额',
                'game_prize_amount' => '派奖额',
                'profit' => '盈亏',
            ],
            //成本明细 (游戏详情)
            'game_info' => [
                'bet_ratio_amount' => '流水占比',
                'deposit_ratio_amount' => '充值占比',
                'revenue_ratio_amount' => '营收占比',
                'coupon_ratio_amount' => '优惠占比',
                'manual_ratio_amount' => '人工扣款',
            ],
            //代理详情
            'agent_details' => [
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
            ],
            //总计信息
            'total_tz' => '总投注',
            'total_fj' => '总返奖',
            'total_yk' => '总盈亏(总营收)',
            'total_kk' => '总扣款(总成本)',
            'total_yl' => '总盈利(总营收-总扣款)',
        ],

    ];

    //feel_list json字段中特殊的4各统计字段
    protected $special_field = [
        'coupon_ratio_amount' => '优惠占比金额',
        'manual_ratio_amount' => '人工扣款占比金额',
        'deposit_ratio_amount' => '充值占比金额',
        'revenue_ratio_amount' => '营收占比金额',
    ];

    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $uid = $this->auth->getUserId();
        $user_name = $this->request->getParam('user_name');
        $agent_name = $this->request->getParam('agent_name');    //上级代理名词
        $date_start = $this->request->getParam('date_start');
        $date_end = $this->request->getParam('date_end');
        $month = $this->request->getParam('month');   //查询月结时需要传递的月份，格式：2022-06
        $page = $this->request->getParam('page',1);
        $page_size = $this->request->getParam('page_size',10);
        $model = $this->request->getParam('model',1);  //1-日结，2-月结
        $export = $this->request->getParam('export',0);  //是否导出数据：1-是

        $resData = [
            'game_info'=>[],
            'platform_info'=> ['bet_ratio_amount'=>0,'deposit_ratio_amount'=>0,'revenue_ratio_amount'=>0,'coupon_ratio_amount'=>0,'deposit_coupon_ratio_amount'=>0,'manual_ratio_amount'=>0],
            'agent_details'=>[],
            'total_tz' => 0,    //总投注
            'total_fj' => 0,    //总返奖
            'total_yk' => 0,    //总盈亏
            'total_kk' => 0,    //总扣款
            'total_yl' => 0,    //总盈利(净盈利)
        ];

        //获取启用中的一级游戏类目信息
        $game_menu_info = DB::table("game_menu")->whereRaw('pid=? and status=?',  [0,'enabled'])->select(['id','type','rename'])->get()->toArray();
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
            $tz_sql .= "TRUNCATE(cast(sum(bet_amount_list->'$.{$k}') as decimal(15,2)), 2) as tz_{$k} ,";
            $fj_sql .= "TRUNCATE(cast(sum(lose_earn_list->'$.{$k}') as decimal(15,2)), 2) as fj_{$k} ,";
        }
        if ($model == 1) {
            $gameInfo = DB::table('plat_earnlose')->select([DB::raw($tz_sql.$fj_sql.'sum(bet_amount) as z_tz, sum(lose_earn) as z_yk')])->whereRaw('date>=? and date<=? ', [$date_start,$date_end])->get()->toArray();
        } else {
            $gameInfo = DB::table('plat_earnlose')->select([DB::raw($tz_sql.$fj_sql.'sum(bet_amount) as z_tz, sum(lose_earn) as z_yk')])->whereRaw('date_format(date, "%Y-%m") = ? ', [$month])->get()->toArray();
        }

        if (isset($gameInfo) && !empty($gameInfo)) {
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

        if ($model == 1) {
            //平台详情： 默认展示前一天数统计数据，如有传递时间范围则根据指定时间范围统计
            if (isset($date_start) && !empty($date_start) && isset($date_end) && !empty($date_end)) {
                $date_end = date("Y-m-d", strtotime("+1 day", strtotime($date_end)));   //结束日期+1天，SQL用小于符号比较结束时间
            } else {
                $date_start = date("Y-m-d",strtotime("-1 days"));
                $date_end = date("Y-m-d");
            }
            $tz_sql = "";  //投注json字段sql
            $zc_sql = "";  //占成json字段sql
            $cb_sql = "";  //成本(扣款)json字段sql
            foreach ($game_map as $k=>$v) {
                $tz_sql .= "TRUNCATE(cast(bet_amount_list->'$.{$k}' as decimal(15,2)), 2) as tz_{$k} ,";
                $zc_sql .= "TRUNCATE(cast(proportion_list->'$.{$k}' as decimal(15,2)), 2) as zc_{$k} ,";
            }
            foreach ($game_map_ext as $k=>$v) {
                $cb_sql .= "TRUNCATE(cast(fee_list->'$.{$k}' as decimal(15,2)), 2) as cb_{$k} ,";
            }
            if ($export == 1) {
                //代理详情: 结算金额就是指返佣金额
                if (isset($user_name) && !empty($user_name)) {
                    $agentInfo = DB::table('unlimited_agent_bkge')->orderBy('date','desc')->whereRaw('user_name=? and date>=? and date<?', [$user_name,$date_start,$date_end])
                        ->select(DB::raw('deal_log_no,user_id,user_name,agent_name,agent_cnt,bet_amount,bkge,settle_amount,fee_amount,bet_amount_list,proportion_list,'.$tz_sql.$zc_sql.$cb_sql.'date'))->get()->toArray();
                } else {
                    //没有传递指定账号名时，代理详情要展示自身以及整个代理线的详情
                    $sub_uid = DB::table('user_agent')->where('uid_agents','like','%'.$uid.'%')->select(['user_id'])->get()->toArray();
                    $subUid = [];
                    foreach ($sub_uid as $item) {
                        array_push($subUid, $item->user_id);
                    }
                    $agentInfo = DB::table('unlimited_agent_bkge')->orderBy('date','desc')
                        ->whereIn('user_id', $subUid)->whereRaw('date>=? and date<?', [$date_start,$date_end])
                        ->select(DB::raw('deal_log_no,user_id,user_name,agent_name,agent_cnt,bet_amount,bkge,settle_amount,fee_amount,bet_amount_list,proportion_list,'.$tz_sql.$zc_sql.$cb_sql.'date'))->get()->toArray();
                }
            } else {
                //代理详情: 结算金额就是指返佣金额
                if (isset($user_name) && !empty($user_name)) {
                    $agentInfo = DB::table('unlimited_agent_bkge')->orderBy('date','desc')
                        ->whereRaw('user_name=? and date>=? and date<?', [$user_name,$date_start,$date_end])
                        ->paginate($page_size,[DB::raw('deal_log_no,user_id,user_name,agent_name,agent_cnt,bet_amount,bkge,settle_amount,fee_amount,bet_amount_list,proportion_list,'.$tz_sql.$zc_sql.$cb_sql.'date')],'page',$page)->toJson();
                } else {
                    //没有传递指定账号名时，代理详情要展示自身以及整个代理线的详情
                    $sub_uid = DB::table('user_agent')->where('uid_agents','like','%'.$uid.'%')->select(['user_id'])->get()->toArray();
                    $subUid = [];
                    foreach ($sub_uid as $item) {
                        array_push($subUid, $item->user_id);
                    }
                    $agentInfo = DB::table('unlimited_agent_bkge')->orderBy('date','desc')
                        ->whereIn('user_id', $subUid)
                        ->whereRaw('date>=? and date<?', [$date_start,$date_end])
                        ->paginate($page_size,[DB::raw('deal_log_no,user_id,user_name,agent_name,agent_cnt,bet_amount,bkge,settle_amount,fee_amount,bet_amount_list,proportion_list,'.$tz_sql.$zc_sql.$cb_sql.'date')],'page',$page)->toJson();
                }
            }
        } else {
            //月报表-平台详情： 默认展示本月数据，如有传递时间范围则根据指定时间范围统计
            $date_start = date("Y-m-01", strtotime(date("Y-m")));
            $date_end = date("Y-m-01", strtotime("+1 month",strtotime($date_start)));
            if ($month) {   //如果传入了指定月份，则查询指定月份数据
                $date_start = date("Y-m-01", strtotime($month));
                $date_end = date("Y-m-01", strtotime("+1 month",strtotime($date_start)));
            }
            $tz_sql = "";  //投注json字段sql
            $zc_sql = "";  //占成json字段sql
            $cb_sql = "";  //成本(扣款)json字段sql
            foreach ($game_map as $k=>$v) {
                $tz_sql .= "TRUNCATE(cast(sum(bet_amount_list->'$.{$k}') as decimal(15,2)), 2) as tz_{$k} ,";
                $zc_sql .= "TRUNCATE(cast(sum(proportion_list->'$.{$k}') as decimal(15,2)), 2) as zc_{$k} ,";
            }
            foreach ($game_map_ext as $k=>$v) {
                $cb_sql .= "TRUNCATE(cast(fee_list->'$.{$k}' as decimal(15,2)), 2) as cb_{$k} ,";
            }
            if ($export == 1) {
                //代理详情: 结算金额就是指返佣金额
                if (isset($user_name) && !empty($user_name)) {
                    $agentInfo = DB::table('unlimited_agent_bkge')->orderBy('date','desc')
                        ->whereRaw('user_name=? and date>=? and date<?', [$user_name,$date_start,$date_end])->groupBy("user_id")
                        ->select([DB::raw('deal_log_no,user_id,user_name,agent_name,agent_cnt,sum(bet_amount) as bet_amount,sum(fee_amount) as fee_amount,sum(bkge) as bkge,sum(settle_amount) as settle_amount,bet_amount_list,proportion_list,'.$tz_sql.$zc_sql.$cb_sql.'DATE_FORMAT(date,"%Y-%m") as date')])->get()->toArray();
                } else {
                    //没有传递指定账号名时，代理详情要展示自身以及整个代理线的详情
                    $sub_uid = DB::table('user_agent')->where('uid_agents','like','%'.$uid.'%')->select(['user_id'])->get()->toArray();
                    $subUid = [];
                    foreach ($sub_uid as $item) {
                        array_push($subUid, $item->user_id);
                    }
                    $agentInfo = DB::table('unlimited_agent_bkge')->orderBy('date','desc')
                        ->whereIn('user_id', $subUid)->whereRaw('date>=? and date<?', [$date_start,$date_end])->groupBy("user_id")
                        ->select([DB::raw('deal_log_no,user_id,user_name,agent_name,agent_cnt,sum(bet_amount) as bet_amount,sum(fee_amount) as fee_amount,sum(bkge) as bkge, sum(settle_amount) as settle_amount,bet_amount_list,proportion_list,'.$tz_sql.$zc_sql.$cb_sql.'DATE_FORMAT(date,"%Y-%m") as date')])->get()->toArray();
                }
            } else {
                //代理详情: 结算金额就是指返佣金额
                if (isset($user_name) && !empty($user_name)) {
                    $agentInfo = DB::table('unlimited_agent_bkge')->orderBy('date','desc')
                        ->whereRaw('user_name=? and date>=? and date<?', [$user_name,$date_start,$date_end])->groupBy("user_id")
                        ->paginate($page_size,[DB::raw('deal_log_no,user_id,user_name,agent_name,agent_cnt,sum(bet_amount) as bet_amount,sum(fee_amount) as fee_amount,sum(bkge) as bkge, sum(settle_amount) as settle_amount,bet_amount_list,proportion_list,'.$tz_sql.$zc_sql.$cb_sql.'DATE_FORMAT(date,"%Y-%m") as date')],'page',$page)->toJson();
                } else {
                    //没有传递指定账号名时，代理详情要展示自身以及整个代理线的详情
                    $sub_uid = DB::table('user_agent')->where('uid_agents','like','%'.$uid.'%')->select(['user_id'])->get()->toArray();
                    $subUid = [];
                    foreach ($sub_uid as $item) {
                        array_push($subUid, $item->user_id);
                    }
                    $agentInfo = DB::table('unlimited_agent_bkge')->orderBy('date','desc')
                        ->whereIn('user_id', $subUid)->whereRaw('date>=? and date<?', [$date_start,$date_end])->groupBy("user_id")
                        ->paginate($page_size,[DB::raw('deal_log_no,user_id,user_name,agent_name,agent_cnt,sum(bet_amount) as bet_amount,sum(fee_amount) as fee_amount,sum(bkge) as bkge,sum(settle_amount) as settle_amount,bet_amount_list,proportion_list,'.$tz_sql.$zc_sql.$cb_sql.'DATE_FORMAT(date,"%Y-%m") as date')],'page',$page)->toJson();
                }
            }
        }

        if ($export==1) {
            foreach ($agentInfo as $item) {
                $resData['agent_details'][] = [
                    'deal_log_no' => $item->deal_log_no,
                    'user_id' => $item->user_id,
                    'user_name' => $item->user_name,
                    'agent_name' => $item->agent_name,
                    'agent_cnt' => $item->agent_cnt,
                    'bet_amount' => $item->bet_amount,
                    'bkge' => $item->bkge,
                    'settle_amount' => $item->settle_amount,
                    'fee_amount' => $item->fee_amount,
                    'date' => $item->date,
                    //投注列表
                    'BY' => $item->tz_BY ?? 0,
                    'CP' => $item->tz_CP ?? 0,
                    'QP' => $item->tz_QP ?? 0,
                    'GAME' => $item->tz_GAME ?? 0,
                    'LIVE' => $item->tz_LIVE ?? 0,
                    'SPORT' => $item->tz_SPORT ?? 0,
                    'TABLE' => $item->tz_TABLE ?? 0,
                    'ARCADE' => $item->tz_ARCADE ?? 0,
                    'SABONG' => $item->tz_SABONG ?? 0,
                    'ESPORTS' => $item->tz_ESPORTS ?? 0,
                    //占成列表
                    'BY2' => $item->zc_BY ?? 0,
                    'CP2' => $item->zc_CP ?? 0,
                    'QP2' => $item->zc_QP ?? 0,
                    'GAME2' => $item->zc_GAME ?? 0,
                    'LIVE2' => $item->zc_LIVE ?? 0,
                    'SPORT2' => $item->zc_SPORT ?? 0,
                    'TABLE2' => $item->zc_TABLE ?? 0,
                    'ARCADE2' => $item->zc_ARCADE ?? 0,
                    'SABONG2' => $item->zc_SABONG ?? 0,
                    'ESPORTS2' => $item->zc_ESPORTS ?? 0,
                    //扣款列表(成本明细)
                    'BY3' => $item->cb_BY ?? 0,
                    'CP3' => $item->cb_CP ?? 0,
                    'QP3' => $item->cb_QP ?? 0,
                    'GAME3' => $item->cb_GAME ?? 0,
                    'LIVE3' => $item->cb_LIVE ?? 0,
                    'SPORT3' => $item->cb_SPORT ?? 0,
                    'TABLE3' =>  $item->cb_TABLE ?? 0,
                    'ARCADE3' => $item->cb_ARCADE ?? 0,
                    'SABONG3' => $item->cb_SABONG ?? 0,
                    'ESPORTS3' => $item->cb_ESPORTS ?? 0,
                    //其他成本
                    'cb_other' => sprintf("%01.2f",($item->cb_coupon_ratio_amount + $item->cb_manual_ratio_amount + $item->cb_deposit_ratio_amount + $item->cb_revenue_ratio_amount)),
                ];
            }
        } else {
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
        }

        $platformInfo = DB::table('plat_earnlose')->whereRaw('date >= :star and date < :end', ['start'=>$date_start,'end'=>$date_end])
            ->select(DB::raw('sum(bet_ratio_amount) as bet_ratio_amount, sum(deposit_ratio_amount) as deposit_ratio_amount, sum(revenue_ratio_amount) as revenue_ratio_amount,
                sum(coupon_ratio_amount) as coupon_ratio_amount, sum(manual_ratio_amount) as manual_ratio_amount, sum(fee_amount) as fee_amount'))
            ->get()->toArray();
        if (isset($platformInfo) && !empty($platformInfo)) {
            foreach ($platformInfo as $ptm) {
                $resData['platform_info']['bet_ratio_amount'] = bcadd($resData['platform_info']['bet_ratio_amount'],$ptm->bet_ratio_amount, 2);
                $resData['platform_info']['deposit_ratio_amount'] = bcadd($resData['platform_info']['deposit_ratio_amount'],$ptm->deposit_ratio_amount, 2);
                $resData['platform_info']['revenue_ratio_amount'] = bcadd($resData['platform_info']['revenue_ratio_amount'],$ptm->revenue_ratio_amount, 2);
                $resData['platform_info']['coupon_ratio_amount'] = bcadd($resData['platform_info']['coupon_ratio_amount'],$ptm->coupon_ratio_amount, 2);
                $resData['platform_info']['manual_ratio_amount'] = bcadd($resData['platform_info']['manual_ratio_amount'],$ptm->manual_ratio_amount, 2);
                $resData['total_kk'] = bcadd($resData['total_kk'], $ptm->fee_amount, 2);    //新的总成本计算方式
            }
        }
        $resData['total_yl'] = sprintf("%01.2f", ($resData['total_yk'] - $resData['total_kk']));
        $attr = [
            'total' => isset($deAgentInfo['total']) ? $deAgentInfo['total'] : 0,
            'size' => $page_size,
            'number' => isset($deAgentInfo['last_page']) ? $deAgentInfo['last_page'] : 0,
            'current_page' => isset($deAgentInfo['current_page']) ? $deAgentInfo['current_page'] : 0,    //当前页数
            'last_page' => isset($deAgentInfo['last_page']) ? $deAgentInfo['last_page'] : 0,   //最后一页数
        ];

        //导出数据
        if ($export == 1) {
            $title1 = [
                'game_type'=>'游戏分类',
                'game_bet_amount'=>'总投注',
                'game_prize_amount'=>'总返奖',
                'profit'=>'盈亏',
                //平台成本明细
                'bet_ratio_amount'=>'流水占比金额',
                'deposit_ratio_amount' => '充值占比金额',
                'revenue_ratio_amount' => '营收占比金额',
                'coupon_ratio_amount' => '优惠占比金额',
                'manual_ratio_amount' => '人工扣款占比金额',
            ];
            $title2 = [
                //代理详情
                'user_name' => '账号',
                'agent_name' => '上级',
                'agent_cnt' => '下级代理人数',
                'bet_amount' => '总流水',
                'bkge' => '结算金额',
                'settle_amount' => '股东分红',
                'fee_amount' => '总成本',
                'date' => '结算时间',
                'BY' => '捕鱼-投注',
                'CP' => '彩票-投注',
                'QP' => '棋牌-投注',
                'GAME' => '电子-投注',
                'LIVE' => '真人-投注',
                'SPORT' => '体育-投注',
                'TABLE' => '桌面游戏-投注',
                'ARCADE' => '街机-投注',
                'SABONG' => '斗鸡-投注',
                'ESPORTS' => '电竞-投注',
                //占成列表
                'BY2' => '捕鱼-占成',
                'CP2' => '彩票-占成',
                'QP2' => '棋牌-占成',
                'GAME2' => '电子-占成',
                'LIVE2' => '真人-占成',
                'SPORT2' => '体育-占成',
                'TABLE2' => '桌面游戏-占成',
                'ARCADE2' => '街机-占成',
                'SABONG2' => '斗鸡-占成',
                'ESPORTS2' => '电竞-占成',
                //成本列表
                'BY3' => '捕鱼-成本',
                'CP3' => '彩票-成本',
                'QP3' => '棋牌-成本',
                'GAME3' => '电子-成本',
                'LIVE3' => '真人-成本',
                'SPORT3' => '体育-成本',
                'TABLE3' => '桌面游戏-成本',
                'ARCADE3' => '街机-成本',
                'SABONG3' => '斗鸡-成本',
                'ESPORTS3' => '电竞-成本',
                //其他成本
                'cb_other' => '其它成本',
            ];
            $title = array_merge($title1, $title2);
            $exp = [];
            if (!empty($resData['game_info'])) {
                foreach ($resData['game_info'] as $itm) {
                    $tmp = [
                        'game_type' => $itm['game_type'],
                        'game_bet_amount' => $itm['game_bet_amount'],
                        'game_prize_amount' => $itm['game_prize_amount'],
                        'profit' => $itm['profit'],
                        'bet_ratio_amount' => $resData['platform_info']['bet_ratio_amount'] ?? 0,
                        'deposit_ratio_amount' => $resData['platform_info']['deposit_ratio_amount'] ?? 0,
                        'revenue_ratio_amount' => $resData['platform_info']['revenue_ratio_amount'] ?? 0,
                        'coupon_ratio_amount' => $resData['platform_info']['coupon_ratio_amount'] ?? 0,
                        'manual_ratio_amount' => $resData['platform_info']['manual_ratio_amount'] ?? 0,
                    ];
                    foreach ($title2 as $k=>$v) {
                        $tmp[$k] = "";
                    }
                    array_push($exp, $tmp);
                }
            }
            //拼接代理详情数据进去
            if (!empty($resData['agent_details'])) {
                foreach ($resData['agent_details'] as $k=>$v) {
                    if (!isset($exp[$k])) {
                        $exp[$k]['game_type'] = '';
                        $exp[$k]['game_bet_amount'] = '';
                        $exp[$k]['game_prize_amount'] = '';
                        $exp[$k]['profit'] = '';
                        $exp[$k]['bet_ratio_amount'] = '';
                        $exp[$k]['deposit_ratio_amount'] = '';
                        $exp[$k]['revenue_ratio_amount'] = '';
                        $exp[$k]['coupon_ratio_amount'] = '';
                        $exp[$k]['manual_ratio_amount'] = '';
                    }
                    $exp[$k]['user_name'] = $v['user_name'];
                    $exp[$k]['agent_name'] = $v['agent_name'];
                    $exp[$k]['agent_cnt'] = $v['agent_cnt'];
                    $exp[$k]['bet_amount'] = $v['bet_amount'];
                    $exp[$k]['bkge'] = $v['bkge'];
                    $exp[$k]['settle_amount'] = $v['settle_amount'];
                    $exp[$k]['fee_amount'] = $v['fee_amount'];
                    $exp[$k]['date'] = $v['date'];
                    $exp[$k]['BY'] = $v['BY'];
                    $exp[$k]['CP'] = $v['CP'];
                    $exp[$k]['QP'] = $v['QP'];
                    $exp[$k]['GAME'] = $v['GAME'];
                    $exp[$k]['LIVE'] = $v['LIVE'];
                    $exp[$k]['SPORT'] = $v['SPORT'];
                    $exp[$k]['TABLE'] = $v['TABLE'];
                    $exp[$k]['ARCADE'] = $v['ARCADE'];
                    $exp[$k]['SABONG'] = $v['SABONG'];
                    $exp[$k]['ESPORTS'] = $v['ESPORTS'];
                    $exp[$k]['BY2'] = $v['BY2'];
                    $exp[$k]['CP2'] = $v['CP2'];
                    $exp[$k]['QP2'] = $v['QP2'];
                    $exp[$k]['GAME2'] = $v['GAME2'];
                    $exp[$k]['LIVE2'] = $v['LIVE2'];
                    $exp[$k]['SPORT2'] = $v['SPORT2'];
                    $exp[$k]['TABLE2'] = $v['TABLE2'];
                    $exp[$k]['ARCADE2'] = $v['ARCADE2'];
                    $exp[$k]['SABONG2'] = $v['SABONG2'];
                    $exp[$k]['ESPORTS2'] = $v['ESPORTS2'];
                    $exp[$k]['BY3'] = $v['BY3'];
                    $exp[$k]['CP3'] = $v['CP3'];
                    $exp[$k]['QP3'] = $v['QP3'];
                    $exp[$k]['GAME3'] = $v['GAME3'];
                    $exp[$k]['LIVE3'] = $v['LIVE3'];
                    $exp[$k]['SPORT3'] = $v['SPORT3'];
                    $exp[$k]['TABLE3'] = $v['TABLE3'];
                    $exp[$k]['ARCADE3'] = $v['ARCADE3'];
                    $exp[$k]['SABONG3'] = $v['SABONG3'];
                    $exp[$k]['ESPORTS3'] = $v['ESPORTS3'];
                    $exp[$k]['cb_other'] = $v['cb_other'];
                }
            }
            $this->exportExcel("代理结算报表",$title, $exp);
            exit();
        }
        return $this->lang->set(0, [], $resData, $attr);
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