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
        $user_name = $this->request->getParam('user_name');
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

        //代理结算详情
        $game_map_ext = array_merge($game_map, $this->special_field);    //游戏type映射图中补充4各特殊统计字段
        $cb_sql = "";  //成本json字段sql
        $yk_sql = "";  //盈亏json字段sql
        $zb_sql = "";  //盈亏占比json字段sql
        foreach ($game_map as $k=>$v) {
            $yk_sql .= "cast(loseearn_amount_list->'$.{$k}' as decimal(15,2)) as yk_{$k} ,";
            $zb_sql .= "cast(proportion_list->'$.{$k}' as decimal(15,2)) as zb_{$k} ,";
        }
        foreach ($game_map_ext as $k=>$v) {
            $cb_sql .= "cast(fee_list->'$.{$k}' as decimal(15,2)) as cb_{$k} ,";
        }
        if ($model == 1) {   //日结算
            $agentSql = DB::connection('slave')->table('agent_loseearn_bkge')->whereRaw('agent_name = ? and created >= ? and created <= ?', [$user_name, $date_start, $date_end]);
            $agentInfo = $agentSql->orderBy('date','desc')->paginate($page_size,[DB::raw('deal_log_no,user_id,user_name,agent_name,agent_cnt,bet_amount,loseearn_amount,bkge,fee_amount,'.
                $cb_sql.$yk_sql.$zb_sql.'DATE_FORMAT(created,"%Y-%m-%d") as date')], 'page',$page)->toJson();
        } elseif ($model == 3) {   //周结算
            $agentSql = DB::connection('slave')->table('agent_loseearn_week_bkge')->whereRaw('agent_name = ? and created>=? and created<=?', [$user_name, $date_start,$date_end])->groupBy(['user_id']);
            $agentInfo = $agentSql->orderBy('date','desc')->paginate($page_size,[DB::raw('deal_log_no,user_id,user_name,agent_name,agent_cnt,sum(bet_amount) as bet_amount,'.
                'sum(loseearn_amount) as loseearn_amount,sum(bkge) as bkge, sum(fee_amount) as fee_amount,'.$yk_sql.$zb_sql.$cb_sql.'DATE_FORMAT(created,"%Y-%m-%d") as date')], 'page',$page)->toJson();
        } else {    //月结算
            $agentSql = DB::connection('slave')->table('agent_loseearn_month_bkge')->whereRaw('agent_name = ? and date = ?', [$user_name, $month])->groupBy(['user_id']);
            $agentInfo = $agentSql->orderBy('date','desc')->paginate($page_size,[DB::raw('deal_log_no,user_id,user_name,agent_name,agent_cnt,sum(bet_amount) as bet_amount,'.
                'sum(loseearn_amount) as loseearn_amount,sum(bkge) as bkge, sum(fee_amount) as fee_amount,'.$yk_sql.$zb_sql.$cb_sql.'DATE_FORMAT(created,"%Y-%m-%d") as date')], 'page',$page)->toJson();
        }
        $deAgentInfo = json_decode($agentInfo, true);
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