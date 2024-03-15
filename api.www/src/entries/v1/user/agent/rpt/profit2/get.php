<?php
use Utils\Www\Action;

return new class() extends Action {
    const TITLE = '盈亏返佣-代理数据展示';
    const DESCRIPTION = '代理数据展示';
    const QUERY = [
        'page' => 'int(required) #当前页',
        'page_size' => 'int(required) #每页数量',
        'cur_user_name' => 'string() #当前登录的账号',
        'user_name' => 'string() #要查询的代理账号',
        'date_type' => 'string() #查询的时间格式',        //today-今天，yesterday-昨天，this_week-本周，last_week, this_month-本月，last_month-上月,custom-自定义时间
        'date_start' => 'string() #开始日期',           //只有当date_type为custom时候才需要开始和结束时间
        'date_end' => 'string() #结束日期',
        'model' => 'int() #查询tab',         //1-代理，2-会员，3-游戏，4-占成，5-投注报表
    ];

    const PARAMS = [];
    const SCHEMAS = [
        //当前登录账号的数据
        "self_data" => [
            'total_zd' => '总注单数量',        //总投注订单数
            'total_zd_money' => '总有效注额',
            'total_yk_money' => '总输赢金额',
            'self_zzc' => '我的总占成',        //这个版本不做
            'self_zcb' => '我的总成本',
            'self_zyk' => '我的总盈余',     //投注-派奖
            'self_zfs' => '我的总反水',
        ],
        "agent_data" => [],    //代理数据
        "vip_data" => [],     //会员数据  【展示当前登录账户的下级用户数据】
        "game_data" => [],    //游戏数据 【不展示】
        //占成数据
        "zc_data" => [
            'user_name' => '用户名称',
            'loseearn_amount' => '盈亏余额',
            'dml_amount' => '打码量',
            'fee_amount' => '总成本',
            'bkge' => '返佣',
        ],
        "tz_data" => [],    //投注数据

    ];

    //feel_list json字段中特殊的4各统计字段
    protected $special_field = [
        'coupon_ratio_amount' => '优惠占比金额',       //代理成本 - 平台彩金
        'manual_ratio_amount' => '人工扣款占比金额',   //代理成本 - 平台服务
        'deposit_ratio_amount' => '充值占比金额',     //代理成本 - 充值兑换
        'revenue_ratio_amount' => '营收占比金额',     //代理成本 - 公司盈亏
    ];

    public function run()
    {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $cur_user_id = $this->auth->getUserId();
        $user = \Model\User::find($cur_user_id)->toArray();

        $page = $this->request->getParam('page', 1);
        $page_size = $this->request->getParam('page_size', 10);
        $user_name = $this->request->getParam('user_name');            //要查询的用户账号
        $date_type = $this->request->getParam('date_type', '');        //today-今天，yesterday-昨天，this_week-本周，last_week, this_month-本月，last_month-上月,custom-自定义时间
        $date_start = $this->request->getParam('date_start','');
        $date_end = $this->request->getParam('date_end','');
        $model = $this->request->getParam('model',1);       //查询tab: 1-代理，2-会员，3-游戏，4-占成，5-投注报表

        $resData = [];    //返回数据

        //如果是查询指定账号，要检测该账号是否未当前登录账号的直属下级
        if (!empty($user_name)) {
            $tagUser = (array)DB::table('user')->where('name', $user_name)->first();
            if (empty($tagUser)) {
                return createRsponse($this->response, 200, -2, '用户信息错误');
            }
            $ck = (array)DB::table('user_agent')->whereRaw('uid_agent=? and user_id=?', [$cur_user_id,$tagUser['id']])->first();
            if (empty($ck)) {
                return createRsponse($this->response, 200, -2, '只能查询直属下级用户');
            }
        }
        
        //总投注数量，总有效投注额，总输赢金额
        $total_data = DB::table("agent_loseearn_bkge")->selectRaw('sum(num) as total_zd, sum(bet_amount) as total_zd_money, sum(loseearn_amount) as total_yk_money, sum(bkge) as bkge')
            ->where('user_id',$cur_user_id)->whereRaw('date>=? and date<=?',[$date_start, $date_end])->get()->toArray();
        $resData['self_data']['self_zcb'] = $total_data[0]->bkge ?? 0;
        $resData['self_data']['total_zd'] = $total_data[0]->total_zd ?? 0;
        $resData['self_data']['total_zd_money'] = $total_data[0]->total_zd_money ?? 0;
        $resData['self_data']['total_yk_money'] = $total_data[0]->total_yk_money ?? 0;
        //我的整条代理线数据
        $self_data = DB::table("rpt_user")->selectRaw('sum(bet_user_amount-prize_user_amount) as self_zyk, sum(return_user_amount) as self_zfs')
            ->where('user_name', $user['name'])->whereRaw('count_date>=? and count_date<=?',[$date_start,$date_end])->get()->toArray();
        $resData['self_data']['self_zyk'] = $self_data[0]->self_zyk ?? 0;
        $resData['self_data']['self_zfs'] = $self_data[0]->self_zfs ?? 0;

        //获取启用中的游戏列表
        $game_menu_info = DB::table("game_menu")->whereRaw('pid=? and status=?', [0, 'enabled'])->select(['id', 'type', 'rename'])->get()->toArray();
        $game_map = [];   //游戏type映射游戏中文名称
        foreach ($game_menu_info as $item) {
            $game_map[$item->type] = $item->rename;
        }
        $resData['game_map'] = $game_map;

        if ($model == 1) {   //代理数据
            //默认查询当前账号下所有代理，搜索账号时查询搜索账号本身。
            if ($user_name) {
                $agent_data = DB::table("agent_loseearn_bkge")->where('user_name',$user_name)->whereRaw('date>=? and date<=?',[$date_start, $date_end])->groupBy(['user_name'])
                    ->paginate($page_size,[DB::raw('agent_name,user_name,max(agent_cnt) as agent_cnt')],'page',$page)->toJson();
            } else {
                $agent_data = DB::table("agent_loseearn_bkge")->where('agent_name',$user['name'])->whereRaw('date>=? and date<=?',[$date_start, $date_end])->groupBy(['user_name'])
                    ->paginate($page_size,[DB::raw('agent_name,user_name,max(agent_cnt) as agent_cnt')],'page',$page)->toJson();
            }
            $ex_agent_data = json_decode($agent_data, true);
            if ($ex_agent_data['data']) {
                $agent_names = [];
                foreach ($ex_agent_data['data'] as $v) {
                    array_push($agent_names, $v['user_name']);
                }
                $agent_team_data = DB::table("agent_loseearn_bkge")->selectRaw('user_name,sum(num) as num,sum(bet_amount) bet_amount,sum(loseearn_amount) as loseearn_amount')
                    ->whereIn('user_name',$agent_names)->whereRaw('date>=? and date<=?',[$date_start, $date_end])->groupBy(['user_name'])->get()->toArray();
                $new_agent_team_data = [];
                if (!empty($agent_team_data)) {
                    foreach ($agent_team_data as $v) {
                        $new_agent_team_data[$v->user_name] = [
                            'num' => $v->num,
                            'bet_amount' => $v->bet_amount,
                            'loseearn_amount' => $v->loseearn_amount,
                        ];
                    }
                }
                foreach ($ex_agent_data['data'] as $agt) {
                    $resData['agent_data'][] = [
                        'user_name' => $agt['user_name'],
                        'agent_cnt' => $agt['agent_cnt'],
                        'agent_zd' => $new_agent_team_data[$agt['user_name']]['num'] ?? 0,   //注单数
                        'team_tz' => $new_agent_team_data[$agt['user_name']]['bet_amount'] ?? 0,    //团队投注流水
                        'team_yk' => $new_agent_team_data[$agt['user_name']]['loseearn_amount'] ?? 0,    //团队盈亏
                    ];
                }
            } else {
                $resData['agent_data'] = [];
            }
            $attr = [
                'total' => $ex_agent_data['total'] ?? 0,
                'size' => $page_size,
                'number' => $ex_agent_data['last_page'] ?? 0,
                'current_page' => $ex_agent_data['current_page'] ?? 0,    //当前页数
                'last_page' => $ex_agent_data['last_page'] ?? 0,   //最后一页数
            ];
            return $this->lang->set(0, [], $resData, $attr);
        } elseif ($model == 2) {   //会员数据
            if ($user_name) {
                $agent_data = DB::table("agent_loseearn_bkge")->where('user_name',$user_name)->whereRaw('date>=? and date<=?',[$date_start, $date_end])->groupBy(['user_name'])
                    ->paginate($page_size,[DB::raw('user_id,user_name,agent_name,max(agent_cnt) as agent_cnt,sum(num) as num,sum(bet_amount) as bet_amount,sum(loseearn_amount) as loseearn_amount')],'page',$page)->toJson();
            } else {
                $agent_data = DB::table("agent_loseearn_bkge")->where('agent_name',$user['name'])->whereRaw('date>=? and date<=?',[$date_start, $date_end])->groupBy(['user_name'])
                    ->paginate($page_size,[DB::raw('user_id,user_name,agent_name,max(agent_cnt) as agent_cnt,sum(num) as num,sum(bet_amount) as bet_amount,sum(loseearn_amount) as loseearn_amount')],'page',$page)->toJson();
            }
            $ex_agent_data = json_decode($agent_data, true);
            if ($ex_agent_data['data']) {
                //会员报表需要查询rpt_user中的数据
                $user_list = [];
                $user_id_list = [];
                foreach ($ex_agent_data['data'] as $item) {
                    array_push($user_list, $item['user_name']);
                    array_push($user_id_list, $item['user_id']);
                }
                //统计每个用户的投注流水，盈亏
                $rptData = DB::table('rpt_user')->whereIn('user_name', $user_list)->whereRaw('count_date>=? and count_date<=?',[$date_start,$date_end])
                    ->selectRaw('user_name, sum(bet_user_amount) as bet_user_amount,sum(prize_user_amount) as prize_user_amount')->groupBy(['user_name'])->get()->toArray();
                $rpt_data = [];
                if ($rptData) {
                    foreach ($rptData as $rpt) {
                        $rpt_data[$rpt->user_name] = [
                            'agent_cnt' => 0,       //代理数量 - 会员报表中这个字段不展示了
                            'team_tz' => $rpt->bet_user_amount,   //投注流水
                            'team_yk' => bcsub($rpt->bet_user_amount, $rpt->prize_user_amount, 2),  //盈亏
                        ];
                    }
                }
                //统计每个用户的注单数
                $orderData = DB::table('orders_report')->whereIn('user_id',$user_id_list)->whereRaw('date>=? and date<=?',[$date_start, $date_end])->selectRaw('user_id,sum(num) as num')
                    ->groupBy(['user_id'])->get()->toArray();
                $order_data = [];
                if ($orderData) {
                    foreach ($orderData as $v) {
                        $order_data[$v->user_id] = $v->num;
                    }
                }

                foreach ($ex_agent_data['data'] as $agt) {
                    $resData['vip_data'][] = [
                        'user_name' => $agt['user_name'],
                        'agent_cnt' => $rpt_data[$agt['user_name']]['agent_cnt'] ?? 0,
                        'agent_zd' => $order_data[$agt['user_id']] ?? 0,
                        'team_tz' => $rpt_data[$agt['user_name']]['team_tz'] ?? 0,
                        'team_yk' => $rpt_data[$agt['user_name']]['team_yk'] ?? 0,
                    ];
                }
            } else {
                $resData['vip_data'] = [];
            }
            $attr = [
                'total' => $ex_agent_data['total'] ?? 0,
                'size' => $page_size,
                'number' => $ex_agent_data['last_page'] ?? 0,
                'current_page' => $ex_agent_data['current_page'] ?? 0,    //当前页数
                'last_page' => $ex_agent_data['last_page'] ?? 0,   //最后一页数
            ];
            return $this->lang->set(0, [], $resData, $attr);
        } elseif ($model == 3) {   //游戏数据 【这个版本不做】
            $resData['game_data'] = [];
            $attr = [
                'total' => 0,
                'size' => $page_size,
                'number' => 0,
                'current_page' => 0,    //当前页数
                'last_page' => 0,   //最后一页数
            ];
            return $this->lang->set(0, [], $resData, $attr);
        } elseif ($model == 4) {   //占成报表
            $zc_sql = "";  //盈亏占比json字段sql
            foreach ($game_map as $k=>$v) {
                $zc_sql .= "cast(proportion_list->'$.{$k}' as decimal(15,2)) as zc_{$k} ,";
            }
            //获取数组里面的情况
            $one_item = DB::table("agent_loseearn_bkge")->where('loseearn_amount_list','!=','')->orderBy('date','desc')->first();
            $game_list = [];
            if(!empty($one_item)){
                $game_list = json_decode($one_item->loseearn_amount_list,true);
                $game_list = array_keys($game_list);
            }
            $game_str = '';
            foreach($game_list as  $game_v){
                $game_str .= "SUM(CAST(`loseearn_amount_list` -> '$.{$game_v}' AS DECIMAL (18, 2))) AS `{$game_v}`,";
            }
            $game_str = rtrim($game_str,',');
            if ($user_name) {
                $agent_data = DB::table("agent_loseearn_bkge")->where('user_name',$user_name)->whereRaw('date>=? and date<=?',[$date_start, $date_end])->groupBy(['user_name'])
                    ->paginate($page_size,[DB::raw('user_name,'.$zc_sql.'sum(loseearn_amount) as loseearn_amount,sum(fee_amount) as fee_amount,sum(bkge) as bkge,sum(dml_amount) as dml_amount,proportion_list,'.$game_str)],'page',$page)->toJson();
            } else {
                $agent_data = DB::table("agent_loseearn_bkge")->where('agent_name',$user['name'])->whereRaw('date>=? and date<=?',[$date_start, $date_end])->groupBy(['user_name'])
                    ->paginate($page_size,[DB::raw('user_name,'.$zc_sql.'sum(loseearn_amount) as loseearn_amount,sum(fee_amount) as fee_amount,sum(bkge) as bkge,sum(dml_amount) as dml_amount,proportion_list,'.$game_str)],'page',$page)->toJson();
            }
            $ex_agent_data = json_decode($agent_data, true);
            foreach($ex_agent_data['data'] as $key => $agt){
                $loseearn_list = [];
                $proportion_list = [];
                if(!empty($agt['proportion_list'])){
                    $proportion_list = json_decode($agt['proportion_list'], true);
                }
                foreach($game_list as $game_v){
                    $loseearn_list[$game_v] = $agt[$game_v];
                }
                $ex_agent_data['data'][$key]['loseearn_amount_list'] = $loseearn_list;
                $ex_agent_data['data'][$key]['proportion_list'] = $proportion_list;
            }
            $resData['zc_data'] = $ex_agent_data['data'];
            $attr = [
                'total' => $ex_agent_data['total'] ?? 0,
                'size' => $page_size,
                'number' => $ex_agent_data['last_page'] ?? 0,
                'current_page' => $ex_agent_data['current_page'] ?? 0,    //当前页数
                'last_page' => $ex_agent_data['last_page'] ?? 0,   //最后一页数
            ];
            return $this->lang->set(0, [], $resData, $attr);
        } elseif ($model == 5) {    //投注报表
            $tz_sql = "";  //盈亏占比json字段sql
            foreach ($game_map as $k=>$v) {
                $tz_sql .= "cast(fee_list->'$.{$k}' as decimal(15,2)) as tz_{$k} ,";
            }
            if ($user_name) {
                $agent_data = DB::table("agent_loseearn_bkge")->where('user_name',$user_name)->whereRaw('date>=? and date<=?',[$date_start, $date_end])->groupBy(['user_name'])
                    ->paginate($page_size,[DB::raw('deal_log_no,user_name,'.$tz_sql.'sum(bet_amount) as bet_amount,sum(loseearn_amount) as loseearn_amount,date')],'page',$page)->toJson();
            } else {
                $agent_data = DB::table("agent_loseearn_bkge")->where('agent_name',$user['name'])->whereRaw('date>=? and date<=?',[$date_start, $date_end])->groupBy(['user_name'])
                    ->paginate($page_size,[DB::raw('deal_log_no,user_name,'.$tz_sql.'sum(bet_amount) as bet_amount,sum(loseearn_amount) as loseearn_amount,date')],'page',$page)->toJson();
            }
            $ex_agent_data = json_decode($agent_data, true);
            $resData['tz_data'] = $ex_agent_data['data'];
            $attr = [
                'total' => $ex_agent_data['total'] ?? 0,
                'size' => $page_size,
                'number' => $ex_agent_data['last_page'] ?? 0,
                'current_page' => $ex_agent_data['current_page'] ?? 0,    //当前页数
                'last_page' => $ex_agent_data['last_page'] ?? 0,   //最后一页数
            ];
            return $this->lang->set(0, [], $resData, $attr);
        }

        return createRsponse($this->response, 200, -2, 'model参数错误');
    }

};