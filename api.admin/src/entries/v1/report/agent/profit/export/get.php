<?php

use Logic\Admin\BaseController;

return new class() extends BaseController {
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
            "withdrawal_ratio_amount" => "兑换",    //兑换
            "revenue_ratio_amount" => "营收",    //营收
        ],
        //各代理详情 (盈亏结算详情)
        "agent_details" => [
            'deal_log_no' => '流水号',
            'user_id' => '用户账号',
            'user_name' => '用户名称',
            'agent_name' => '上级代理名称',
            'agent_cnt' => '下级代理人数',
            'bet_amount' => '总流水',     //投注额
            'loseearn_amount' => '总盈亏',
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
        ],
    ];

    //feel_list json字段中特殊的4各统计字段
    protected $special_field = [
        'coupon_ratio_amount' => '优惠占比金额',       //代理成本 - 平台彩金
        'manual_ratio_amount' => '人工扣款占比金额',   //代理成本 - 平台服务
        'deposit_ratio_amount' => '充值占比金额',     //代理成本 - 充值兑换
        'revenue_ratio_amount' => '营收占比金额',     //代理成本 - 营收
        'loseearn_ratio_amount' => '盈亏',        //盈亏
        'withdrawal_ratio_amount' => '兑换',     //兑换-取款
    ];
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run()
    {
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $date_start = $this->request->getParam('date_start', $yesterday);
        $date_end = $this->request->getParam('date_end', $yesterday);
        $user_name = $this->request->getParam('user_name');      //用户账号
        $agent_name = $this->request->getParam('agent_name');    //上级代理名称
        $model = $this->request->getParam('model', 1);            //1-日结，2-月结
        $month = date("Y-m", strtotime($this->request->getParam('month')));   //查询月结时需要传递的月份，格式：2022-06

        $resData = [];    //返回数据

        //获取启用中的游戏列表
        $game_menu_info = DB::connection('slave')->table("game_menu")->whereRaw('pid=? and status=?', [0, 'enabled'])->select(['id', 'type', 'rename'])->get()->toArray();
        $game_map = [];   //游戏type映射游戏中文名称
        foreach ($game_menu_info as $item) {
            $game_map[$item->type] = $item->rename;
        }

        //平台详情数据：总投注、总营收、总返奖、总成本、净盈利
        $tz_sql = "";
        $yk_sql = "";
        foreach ($game_map as $k => $v) {
            $tz_sql .= "cast(sum(bet_amount_list->'$.{$k}') as decimal(15,2)) as tz_{$k} ,";
            $yk_sql .= "cast(sum(lose_earn_list->'$.{$k}') as decimal(15,2)) as fj_{$k} ,";
        }
        if ($model == 1 || $model == 2) {
            $plat_data = DB::connection('slave')->table('agent_plat_earnlose')->select([DB::raw($tz_sql . $yk_sql . 'sum(bet_amount) as z_tz, sum(revenue_amount) as z_ys,sum(deposit_ratio_amount) as deposit_ratio_amount,
             sum(loseearn_ratio_amount) as lose_earn, sum(coupon_ratio_amount) as coupon_ratio_amount, sum(manual_ratio_amount) as manual_ratio_amount, sum(fee_amount) as fee_amount,
             sum(withdrawal_ratio_amount) as withdrawal_ratio_amount, sum(revenue_ratio_amount) as revenue_ratio_amount')])
                ->whereRaw('date>=? and date<=? ', [$date_start, $date_end])->get()->toArray();
        } else {
            $plat_data = DB::connection('slave')->table('agent_plat_earnlose')->select([DB::raw($tz_sql . $yk_sql . 'sum(bet_amount) as z_tz, sum(revenue_amount) as z_ys, sum(deposit_ratio_amount) as deposit_ratio_amount, 
            sum(loseearn_ratio_amount) as lose_earn, sum(coupon_ratio_amount) as coupon_ratio_amount, sum(manual_ratio_amount) as manual_ratio_amount, sum(fee_amount) as fee_amount,
            sum(withdrawal_ratio_amount) as withdrawal_ratio_amount, sum(revenue_ratio_amount) as revenue_ratio_amount')])
                ->whereRaw('date_format(date,"%Y-%m") = ?', [$month])->get()->toArray();
        }
        $resData['total_tz'] = $plat_data[0]->z_tz ?? 0;    //总投注
        $resData['total_ys'] = $plat_data[0]->z_ys ?? 0;    //总营收
        $resData['total_fj'] = bcsub($resData['total_tz'], $resData['total_ys'], 2);    //总返奖 = 总投注 - 总营收
        $resData['total_cb'] = $plat_data[0]->fee_amount ?? 0;    //总成本(总扣款)
        $resData['total_jyl'] = bcsub($resData['total_ys'], $resData['total_cb'], 2);   //净盈利 = 总营收 - 总成本(总扣款)
        //平台数据：游戏分类数据
        foreach ($game_map as $t => $n) {
            $str_tz = 'tz_' . $t;
            $str_yk = 'fj_' . $t;
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
        $resData['cb_details']['coupon_ratio_amount'] = bcadd(($plat_data[0]->coupon_ratio_amount ?? 0), 0, 2);       //平台彩金
        $resData['cb_details']['manual_ratio_amount'] = bcadd(($plat_data[0]->manual_ratio_amount ?? 0), 0, 2);      //平台服务(人工扣款)
        $resData['cb_details']['withdrawal_ratio_amount'] = bcadd(($plat_data[0]->withdrawal_ratio_amount ?? 0), 0, 2);      //兑换
        $resData['cb_details']['revenue_ratio_amount'] = bcadd(($plat_data[0]->revenue_ratio_amount ?? 0), 0, 2);      //营收

        //代理结算详情
        $game_map_ext = array_merge($game_map, $this->special_field);    //游戏type映射图中补充4各特殊统计字段
        $cb_sql = "";  //成本json字段sql
        $yk_sql = "";  //盈亏json字段sql
        $zb_sql = "";  //盈亏占比json字段sql
        foreach ($game_map as $k => $v) {
            $yk_sql .= "cast(loseearn_amount_list->'$.{$k}' as decimal(15,2)) as yk_{$k} ,";
            $zb_sql .= "cast(proportion_list->'$.{$k}' as decimal(15,2)) as zb_{$k} ,";
        }
        foreach ($game_map_ext as $k => $v) {
            $cb_sql .= "cast(fee_list->'$.{$k}' as decimal(15,2)) as cb_{$k} ,";
        }
        if ($model == 1) {
            if (!empty($agent_name) && !empty($user_name)) {
                $agentSql = DB::connection('slave')->table('agent_loseearn_bkge')->whereRaw('user_name = ? and agent_name = ? and date >= ? and date <= ?', [$user_name, $agent_name, $date_start, $date_end]);
            } elseif (!empty($agent_name)) {    //查询目标代理下所有子级代理数据
                $agentSql = DB::connection('slave')->table('agent_loseearn_bkge')->whereRaw('agent_name = ? and date >= ? and date <= ?', [$agent_name, $date_start, $date_end]);
            } elseif (!empty($user_name)) {   //查询指定账号的数据
                $agentSql = DB::connection('slave')->table('agent_loseearn_bkge')->whereRaw('user_name = ? and date >= ? and date <= ?', [$user_name, $date_start, $date_end]);
            } else {
                $agentSql = DB::connection('slave')->table('agent_loseearn_bkge')->whereRaw('date >= :start and date <= :end', ['start' => $date_start, 'end' => $date_end]);
            }
            $agentInfo = $agentSql->orderBy('date', 'desc')->selectRaw('deal_log_no,user_id,user_name,agent_name,agent_cnt,bet_amount,loseearn_amount,bkge,fee_amount,'.$cb_sql.$yk_sql.$zb_sql.'date')->get()->toArray();
        } elseif ($model == 3) {
            if (!empty($agent_name) && !empty($user_name)) {
                $agentSql = DB::connection('slave')->table('agent_loseearn_bkge')->whereRaw('user_name=? and agent_name=? and date>=? and date<=?', [$user_name, $agent_name, $date_start,$date_end])->groupBy(['user_id']);
            } elseif (!empty($agent_name)) {    //查询目标代理下所有子级代理数据
                $agentSql = DB::connection('slave')->table('agent_loseearn_bkge')->whereRaw('agent_name = ? and date>=? and date<=?', [$agent_name, $date_start,$date_end])->groupBy(['user_id']);
            } elseif (!empty($user_name)) {   //查询指定账号的数据
                $agentSql = DB::connection('slave')->table('agent_loseearn_bkge')->whereRaw('user_name = ? and date>=? and date<=?', [$user_name, $date_start,$date_end])->groupBy(['user_id']);
            } else {
                $agentSql = DB::connection('slave')->table('agent_loseearn_bkge')->whereRaw('date>=? and date<=?', [$date_start,$date_end])->groupBy(['user_id']);
            }
            $agentInfo = $agentSql->orderBy('date', 'desc')->selectRaw('deal_log_no,user_id,user_name,agent_name,agent_cnt,sum(bet_amount) as bet_amount, 
                    sum(loseearn_amount) as loseearn_amount,sum(bkge) as bkge, sum(fee_amount) as fee_amount,' . $yk_sql . $zb_sql . $cb_sql . 'DATE_FORMAT(date,"%Y-%m-%d") as date')->get()->toArray();
        } else {
            if (!empty($agent_name) && !empty($user_name)) {
                $agentSql = DB::connection('slave')->table('agent_loseearn_bkge')->whereRaw('user_name = ? and agent_name = ? and date_format(date,"%Y-%m") = ?', [$user_name, $agent_name, $month]);
            } elseif (!empty($agent_name)) {    //查询目标代理下所有子级代理数据
                $agentSql = DB::connection('slave')->table('agent_loseearn_bkge')->whereRaw('agent_name = ? and date_format(date,"%Y-%m") = ?', [$agent_name, $month]);
            } elseif (!empty($user_name)) {   //查询指定账号的数据
                $agentSql = DB::connection('slave')->table('agent_loseearn_bkge')->whereRaw('user_name = ? and date_format(date,"%Y-%m") = ?', [$user_name, $month]);
            } else {
                $agentSql = DB::connection('slave')->table('agent_loseearn_bkge')->whereRaw('date_format(date,"%Y-%m") = ?', [$month]);
            }
            $agentInfo = $agentSql->orderBy('date', 'desc')->groupBy(['user_id'])->selectRaw('deal_log_no,user_id,user_name,agent_name,agent_cnt,sum(bet_amount) as bet_amount, 
                    sum(loseearn_amount) as loseearn_amount,sum(bkge) as bkge, sum(fee_amount) as fee_amount,' . $yk_sql . $zb_sql . $cb_sql . 'DATE_FORMAT(date,"%Y-%m") as date')->get()->toArray();
        }
        $resData['agent_details'] = $agentInfo;    //$agentInfo是个数组对象
        $resData['game_map'] = $game_map;

        $title = [
            'total_tz'=>'总投注',
            'total_fj'=>'总返奖',
            'total_ys'=>'总盈收',
            'total_cb'=>'总成本',
            'total_jyl'=>'净盈利',
            //游戏分类数据
            'game_type' => '游戏分类',
            'game_bet_amount' => '总投注',
            'game_prize_amount' => '总返奖',
            'game_yk' => '盈亏',
            //成本明细
            'lose_earn' => '公司盈亏',
            'deposit_ratio_amount' => '充值兑换',
            'coupon_ratio_amount' => '平台彩金',
            'manual_ratio_amount' => '平台服务',
            //代理盈亏结算
            'deal_log_no' => '流水号',
            'user_name' => '账号',
            'agent_name' => '上级',
            'agent_cnt' => '下级代理人数',
            'bet_amount' => '总流水',
            'loseearn_amount' => '总盈亏',
            'fee_amount' => '总成本',
            'bkge' => '盈亏返佣',
            'date' => '结算时间',
        ];
        $en_title = [
            'total_tz'          =>'totalBet',
            'total_fj'          =>'Total rebates',
            'total_ys'          =>'Total benefit',
            'total_cb'          =>'Total cost',
            'total_jyl'         =>'Net profit',
            //游戏分类数据
            'game_type'         => 'GameType',
            'game_bet_amount'   => 'Total bet',
            'game_prize_amount' => 'Total rebates',
            'game_yk'           => 'Profit',
            //成本明细
            'lose_earn'             => 'Company profit',
            'deposit_ratio_amount'  => 'Deposit withdrawal',
            'coupon_ratio_amount'   => 'platform bonus',
            'manual_ratio_amount'   => 'platform service',
            //代理盈亏结算
            'deal_log_no'           => 'Turnover number',
            'user_name'             => 'account',
            'agent_name'            => 'upline agent',
            'agent_cnt'             => 'downline number',
            'bet_amount'            => 'total turnover',
            'loseearn_amount'       => 'total profit',
            'fee_amount'            => 'total cost',
            'bkge'                  => 'rebate profit',
            'date'                  => 'Settlement time',
        ];
        //游戏盈亏列表
        foreach ($game_map as $t=>$n) {
            $title['yk_'.$t] = '盈亏_'.$n;
            $en_title['yk_'.$t] = $this->lang->text('profit_'.$t);
        }
        //游戏占成比例
        foreach ($game_map as $t=>$n) {
            $title['zb_'.$t] = '占比_'.$n;
            $en_title['zb_'.$t] = $this->lang->text('percentage_'.$t);
        }
        $exp = [];
        //游戏分类
        foreach ($resData['game_info'] as $v) {
            $exp[] = [
                'game_type' => $v['game_type_name'],
                'game_bet_amount' => $v['game_bet_amount'],
                'game_prize_amount' => $v['game_prize_amount'],
                'game_yk' => $v['game_profit'],
            ];
        }
        //平台详情
        $exp[0]['total_tz'] = $resData['total_tz'];
        $exp[0]['total_fj'] = $resData['total_fj'];
        $exp[0]['total_ys'] = $resData['total_ys'];
        $exp[0]['total_cb'] = $resData['total_cb'];
        $exp[0]['total_jyl'] = $resData['total_jyl'];
        //成本明细
        $exp[0]['lose_earn'] = $resData['cb_details']['lose_earn'];
        $exp[0]['deposit_ratio_amount'] = $resData['cb_details']['deposit_ratio_amount'];
        $exp[0]['coupon_ratio_amount'] = $resData['cb_details']['coupon_ratio_amount'];
        $exp[0]['manual_ratio_amount'] = $resData['cb_details']['manual_ratio_amount'];
        //代理盈亏数据
        if (!empty($resData['agent_details'])) {
            foreach ($resData['agent_details'] as $i=>$v) {
                $exp[$i]['game_type'] = $exp[$i]['game_type'] ?? "";
                $exp[$i]['game_bet_amount'] = $exp[$i]['game_bet_amount'] ?? "";
                $exp[$i]['game_prize_amount'] = $exp[$i]['game_prize_amount'] ?? "";
                $exp[$i]['game_yk'] = $exp[$i]['game_yk'] ?? "";

                $exp[$i]['total_tz'] = $exp[$i]['total_tz'] ?? "";
                $exp[$i]['total_fj'] = $exp[$i]['total_fj'] ?? "";
                $exp[$i]['total_ys'] = $exp[$i]['total_ys'] ?? "";
                $exp[$i]['total_cb'] = $exp[$i]['total_cb'] ?? "";
                $exp[$i]['total_jyl'] = $exp[$i]['total_jyl'] ?? "";

                $exp[$i]['lose_earn'] = $exp[$i]['lose_earn'] ?? "";
                $exp[$i]['deposit_ratio_amount'] = $exp[$i]['deposit_ratio_amount'] ?? "";
                $exp[$i]['coupon_ratio_amount'] = $exp[$i]['coupon_ratio_amount'] ?? "";
                $exp[$i]['manual_ratio_amount'] = $exp[$i]['manual_ratio_amount'] ?? "";

                $exp[$i]['deal_log_no'] = $v->deal_log_no ?? "";
                $exp[$i]['user_name'] = $v->user_name ?? "";
                $exp[$i]['agent_name'] = $v->agent_name ?? "";
                $exp[$i]['agent_cnt'] = $v->agent_cnt ?? "";
                $exp[$i]['bet_amount'] = $v->bet_amount ?? "";
                $exp[$i]['loseearn_amount'] = $v->loseearn_amount ?? "";
                //游戏盈亏列表
                foreach ($game_map as $t=>$n) {
                    $tmp_yk = 'yk_'.$t;
                    $exp[$i]['yk_'.$t] = $v->$tmp_yk ?? "";
                }
                //游戏占成比例
                foreach ($game_map as $t=>$n) {
                    $tmp_zb = 'zb_'.$t;
                    $exp[$i]['zb_'.$t] = $v->$tmp_zb ?? "";
                }
                $exp[$i]['fee_amount'] = $v->fee_amount ?? "";
                $exp[$i]['bkge'] = $v->bkge ?? "";
                $exp[$i]['date'] = $v->date ?? "";
            }
        } else {
            foreach ($exp as &$item) {
                foreach ($title as $ti=>$tv) {
                    $item[$ti] = $item[$ti] ?? 0;
                }
            }
            unset($item);
        }
        foreach ($en_title as &$value){
            $value = $this->lang->text($value);
        }
        array_unshift($exp,$en_title);
        $this->exportExcel("盈亏结算报表",$title, $exp);
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