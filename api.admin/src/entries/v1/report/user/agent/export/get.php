<?php

use Logic\Admin\BaseController;

use lib\validate\BaseValidate;
return new class() extends BaseController
{
    const STATE = '';
    const TITLE = '导出代理用户信息';
    const DESCRIPTION = '导出代理用户信息';
    
    const QUERY = [
        'date_start'   => 'datetime(required) #开始日期 默认为当前日期',
        'date_end'     => 'datetime(required) #结束日期 默认为当前日期',
        'agent_id'     => "int() #代理ID号",
        'user_name'     => 'string() #会员账号',
    ];

    const SCHEMAS = [
        'user_name'=>'代理账号', 'real_name'=>'代理姓名','proportion_value'=>'占成', 'agent_cnt'=>'下级人数', 'agent_inc_cnt'=>'注册人数',
        'first_deposit_cnt'=>'首充人数','balance_amount'=>'余额', 'deposit_agent_amount'=>'存款(元)', 'withdrawal_agent_amount'=>'取款(元)',
        'dw_drop_amount'=>'存取差额(元)','bet_agent_amount'=>'投注金额','prize_agent_amount'=>'派彩金额','bs_drop_amount'=>'差额',
        'coupon_agent_amount'=>'活动彩金','return_agent_amount'=>'回水金额', 'promotion_agent_winnings'=>'晋升彩金', 'turn_card_agent_winnings'=>'转卡彩金',
        'back_agent_amount'=>'返佣总金额','award'=>'月俸禄','register_time'=> '注册时间'
    ];

    protected $title = [
        'user_name'=>'代理账号', 'real_name'=>'代理姓名', 'agent_cnt'=>'下级人数', 'agent_inc_cnt'=>'注册人数',
        'first_deposit_cnt'=>'首充人数','deposit_user_num'=>'总充值人数','new_register_deposit_amount'=>'新增充值金额','balance_amount'=>'余额', 'deposit_agent_amount'=>'存款(元)', 'withdrawal_agent_amount'=>'取款(元)',
        'dw_drop_amount'=>'存取差额(元)','bet_agent_amount'=>'投注金额','prize_agent_amount'=>'派彩金额','bs_drop_amount'=>'差额',
        'coupon_agent_amount'=>'活动彩金','return_agent_amount'=>'回水金额', 'promotion_agent_winnings'=>'晋升彩金', 'turn_card_agent_winnings'=>'转卡彩金',
        'back_agent_amount'=>'返佣总金额','award'=>'月俸禄','register_time'=> '注册时间'
    ];
    protected $en_title = [
        'user_name'=>'AgentUsername', 'real_name'=>'AgentName', 'agent_cnt'=>'DownlinesMembers', 'agent_inc_cnt'=>'RegisterMembers',
        'first_deposit_cnt'=>'1stDepMembers','deposit_user_num'=>'TotalDepositDownlines','new_register_deposit_amount'=>'NewDepAmount','balance_amount'=>'Balance', 'deposit_agent_amount'=>'Deposit', 'withdrawal_agent_amount'=>'Withdraw',
        'dw_drop_amount'=>'DifferenceDp/Wd','bet_agent_amount'=>'TotalBet','prize_agent_amount'=>'TotalPayout','bs_drop_amount'=>'Dif.Bet/Payout',
        'coupon_agent_amount'=>'PromoBonus','return_agent_amount'=>'Rebate', 'promotion_agent_winnings'=>'UpgradeBonus', 'turn_card_agent_winnings'=>'OfflineBonus',
        'back_agent_amount'=>'Commission','award'=>'MonthlySalary','register_time'=> 'RegistrationTime'
    ];

    protected $beforeActionList = [
        'verifyToken','authorize'
    ];
    public function run() {

        $date_start = $this->request->getParam('date_start');
        $date_end = $this->request->getParam('date_end');
        $agent_id = (int)$this->request->getParam('agent_id');
        $user_name = $this->request->getParam('user_name');
        $proportion_status = $this->request->getParam('proportion_status');

        // 参数为空默认导出所有数据
        $where  = "WHERE count_date >= '{$date_start}' AND count_date<= '{$date_end}'";
        if (empty($date_start) && empty($date_end)) {
            $where =null;
        }
        $sql = "SELECT `user`.id AS user_id,`user`.`name` as user_name,`profile`.`name` AS real_name,`user`.created as register_time,rpt_agent.*,
                user_agent.proportion_value,user_agent.inferisors_all agent_cnt
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
                    sum(back_agent_amount) as back_agent_amount,balance_amount,
                    sum(deposit_user_num) as deposit_user_num,
                    sum(new_register_deposit_amount) as new_register_deposit_amount
                    FROM rpt_agent                   
                    $where GROUP BY(agent_id) ORDER BY count_date DESC
                )rpt_agent ON `user`.id = rpt_agent.agent_id
                
                WHERE (rpt_agent.deposit_agent_amount > 0 OR rpt_agent.bet_agent_amount > 0 OR rpt_agent.withdrawal_agent_amount > 0)";
        if($user_name) {
            $sql .= " AND user.name = '{$user_name}'";
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
            //查询 下级包括自己
            $agent_self = DB::connection('slave')->table('rpt_user')
                ->leftJoin('user','user.id','=','rpt_user.user_id')
                ->leftJoin('funds','user.wallet_id','=','funds.id')
                ->where('user_id',$agent_id)
                ->where('count_date','>=',$date_start)
                ->where('count_date','<=',$date_end)->first([
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
            $agent_self->deposit_user_num = 0;
            $agent_self->new_register_deposit_amount = 0;
        }
        $data = (array)\DB::connection('slave')->select($sql);
        if(isset($agent_self)) {
            array_unshift($data,$agent_self);
        }
        foreach ($data as &$agent){
            //$agent->award = DB::table('user_monthly_award')->where('user_id',$agent->user_id)->value(DB::raw("sum(award_money) as award_amount")) ?? 0;
            $agent->dw_drop_amount = sprintf("%0.2f",$agent->deposit_agent_amount - $agent->withdrawal_agent_amount);//存取差额
            $agent->bs_drop_amount = sprintf("%0.2f",$agent->bet_agent_amount - $agent->prize_agent_amount);//投注盈亏
//            $agent->award = $agent->award / 100;
            $agent->deposit_agent_amount = $agent->deposit_agent_amount ? : 0;
            $agent->withdrawal_agent_amount = $agent->withdrawal_agent_amount ? : 0;
            //$agent->agent_cnt = \Model\UserAgent::where('user_id',$agent->user_id)->value('inferisors_all');
            $agent->agent_cnt = ($agent->agent_cnt - 1) >= 0 ? ($agent->agent_cnt - 1) : 0;
            if($user_name){
                $agent->agent_inc_cnt = \DB::connection('slave')->table('child_agent')
                                           ->where('pid','=',$agent->user_id)
                                           ->where('create_time','>=',$date_start.' 00:00:00')
                                           ->where('create_time','<=',$date_end.' 23:59:59')
                                           ->count();
            }else{
                $agent->agent_inc_cnt = $agent->agent_inc_cnt ? : 0;
            }
            $agent->first_deposit_cnt = $agent->first_deposit_cnt ? : 0;
            $agent->bet_agent_amount = $agent->bet_agent_amount ? : 0;
            $agent->prize_agent_amount = $agent->prize_agent_amount ? : 0;
            $agent->coupon_agent_amount = $agent->coupon_agent_amount ? : 0;
            $agent->return_agent_amount = $agent->return_agent_amount ? : 0;
            $agent->turn_card_agent_winnings = $agent->turn_card_agent_winnings ? : 0;
            $agent->promotion_agent_winnings = $agent->promotion_agent_winnings ? : 0;
            $agent->back_agent_amount = $agent->back_agent_amount ? : 0;
            $agent->deposit_user_num = $agent->deposit_user_num ?? 0;
            $agent->new_register_deposit_amount = $agent->new_register_deposit_amount ? : 0;

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
        }
        foreach ($this->en_title as &$value){
            $value = $this->lang->text($value);
        }
        array_unshift($data,$this->en_title);
        return $this->exportExcel('AgentReport',$this->title,$data);

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
            foreach ($data as $ke => $val) {
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
