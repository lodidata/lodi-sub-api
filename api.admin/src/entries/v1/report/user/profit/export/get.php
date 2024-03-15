<?php

use Logic\Admin\BaseController;

use Utils\Utils;

return new class() extends BaseController
{
    const STATE = '';
    const TITLE = '导出会员信息';
    const DESCRIPTION = '导出会员信息';
    const LANG = [
        'th' => 'th',
        'es-mx' => 'es_mx_title'
    ];
    const QUERY = [];
    const SCHEMAS = [
        'user_name'=>'会员账号', 'real_name'=>'会员姓名', 'agent_name'=>'上级代理', 'deposit_user_amount'=>'存款(元)',
        'withdrawal_user_amount'=>'取款(元)','dw_drop_amount'=>'存取款差额', 'bet_user_amount'=>'投注金额', 'prize_user_amount'=>'派彩金额',
        'bs_drop_amount'=>'盈亏','coupon_user_amount'=>'活动彩金','return_user_amount'=>'回水金额','promotion_user_winnings'=>'晋升彩金',
        'turn_card_user_winnings'=>'转卡彩金','back_user_amount'=>'返佣总金额', 'award_amount'=>'月俸禄', 'register_time'=>'注册时间',
        'mobile'=>'手机号','ranting'=>'VIP等级','login_ip'=>'最后登录ip','last_login'=>'最后登录时间','agent_id'=>'代理id'
        ,'recharge_range_count'=>'充值总次数','recharge_count'=>'充值次数','draw_range_count'=>'提取总次数','draw_count'=>'历史提取总次数'
        ,'deal_money_num'=>'返佣总金额'
    ];

    protected $title = [
        'user_id'=>'用户ID','user_name'=>'会员账号', 'real_name'=>'会员姓名', 'agent_name'=>'上级代理', 'balance' => '余额', 'deposit_user_amount'=>'存款(元)',
        'withdrawal_user_amount'=>'取款(元)','dw_drop_amount'=>'存取款差额', 'total_deposit_user_amount' => '历史总充值', 'total_withdrawal_user_amount' => '历史总兑换', 'bet_user_amount'=>'投注金额',
        'first_deposit_user_amount' => '首充金额', 'first_create_time' => '首充时间','recharge_time' => '最后充值时间', 'prize_user_amount'=>'派彩金额',
        'bs_drop_amount'=>'盈亏', 'total_bet_user_amount' => '历史总投注', 'coupon_user_amount'=>'活动彩金','return_user_amount'=>'回水金额','promotion_user_winnings'=>'晋升彩金',
        'turn_card_user_winnings'=>'转卡彩金','back_user_amount'=>'返佣总金额', 'award_amount'=>'月俸禄', 'total_Winnings' => '历史总彩金','channel_id'=>'注册渠道', 'register_time'=>'注册时间',
        'mobile'=>'手机号','ranting'=>'VIP等级','login_ip'=>'最后登录ip','last_login'=>'最后登录时间','agent_id'=>'代理id'
        ,'recharge_range_count'=>'充值总次数','recharge_count'=>'充值次数','draw_range_count'=>'提取总次数','draw_count'=>'历史提取总次数'
        ,'deal_money_num'=>'返佣总金额'
        ];
    protected $en_title = [
        'user_id'=>'MemberID','user_name'=>'Username', 'real_name'=>'RealName', 'agent_name'=>'Upline', 'balance' => 'Balance', 'deposit_user_amount'=>'Deposit',
        'withdrawal_user_amount'=>'Withdraw','dw_drop_amount'=>'DifferenceDp/Wd', 'total_deposit_user_amount' => 'TotalDeposit', 'total_withdrawal_user_amount' => 'TotalWithdrawal', 'bet_user_amount'=>'TotalBet',
        'first_deposit_user_amount' => '1stDepAmount', 'first_create_time' => '1stDepTime','recharge_time' => 'LastDepTime', 'prize_user_amount'=>'Payoutamount',
        'bs_drop_amount'=>'Profit&Loss', 'total_bet_user_amount' => 'TotalBet', 'coupon_user_amount'=>'PromoBonus','return_user_amount'=>'Rebate','promotion_user_winnings'=>'UpgradeBonus',
        'turn_card_user_winnings'=>'OfflineBonus','back_user_amount'=>'Commission', 'award_amount'=>'MonthlySalary', 'total_Winnings' => 'TotalBonus','channel_id'=>'Channel', 'register_time'=>'Registrationtime',
        'mobile'=>'Tel','ranting'=>'Ranting','login_ip'=>'LastLoginIp','last_login'=>'LastLoginTime','agent_id'=>'AgentId'
        ,'recharge_range_count'=>'HistoryRechargeCount','recharge_count'=>'RechargeCount','draw_range_count'=>'DrawCount','draw_count'=>'HistoryDrawCount'
        ,'DealMoneyNum'=>'返佣总金额'
    ];
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];
    public function run()
    {
        $export_field = $this->request->getParam('export_field');     //需要导出的字段，多个字段之间英文逗号隔开
        $date_start = $this->request->getParam('date_start');
        $date_end = $this->request->getParam('date_end');
        $user_name = $this->request->getParam('user_name');
        $agent_name = $this->request->getParam('agent_name');    //按上级代理账号过滤查询
        $channel_id = $this->request->getParam('channel_id', '');    //渠道号
        $amount_start = intval($this->request->getParam('amount_start', 0));    //充值金额最小值
        $amount_end = intval($this->request->getParam('amount_end', 0));    //充值金额最大值
        //新增排序 默认user_id, 1=存款 2=取款 3=差额 4=投注情况 5=派彩金额 6=投注情况.差额 7=活动彩金 8=回水金额 9=晋升彩金 10=转卡彩金 11=返佣总金额 12=月俸禄
        $field_id = $this->request->getParam('field_id', '');
        $sort_way = $this->request->getParam('sort_way', 'asc');
        if (!in_array($sort_way, ['asc', 'desc'])) $sort_way = 'asc';

        switch ($field_id) {
            case 1:
                $field_id = 'deposit_user_amount';
                break;
            case 2:
                $field_id = 'withdrawal_user_amount';
                break;
            case 3:
                $field_id = 'dw';
                break;
            case 4:
                $field_id = 'bet_user_amount';
                break;
            case 5:
                $field_id = 'prize_user_amount';
                break;
            case 6:
                $field_id = 'dp';
                break;
            case 7:
                $field_id = 'coupon_user_amount';
                break;
            case 8:
                $field_id = 'return_user_amount';
                break;
            case 9:
                $field_id = 'promotion_user_winnings';
                break;
            case 10:
                $field_id = 'turn_card_user_winnings';
                break;
            case 11:
                $field_id = 'back_user_amount';
                break;
            case 12:
                $field_id = 'award_amount';
                break;
            case 18:
                $field_id = 'rptUser.recharge_range_count'; // 存款次数
                break;
            case 19:
                $field_id = 'rptUser.draw_range_count'; // 取款次数
                break;

            default:
                $field_id = 'user.id';
                break;
        }

        if ($user_name) {
            $query = DB::connection('slave')->table('user')->where('user.name', $user_name);
        } else {
            $from_query = DB::connection('slave')->table('rpt_user')->select('user_id')->where('count_date', '>=', $date_start)
                ->where('count_date', '<=', $date_end)
                ->UNION(DB::connection('slave')->table('user')->where([['created', '>=', $date_start . ' 00:00:00'], ['created', '<=', $date_end . ' 23:59:59']])->select('id as user_id'));

            $query = DB::connection('slave')->table('user')->joinSub($from_query, 'user_tmp', 'user.id', '=', 'user_tmp.user_id');
            //按上级代理过滤
            if ($agent_name) {
                $by_agent_name = DB::connection('slave')->table('user_agent')->where('uid_agent_name', $agent_name)->select('user_id');
                $query = DB::connection('slave')->table('user')->joinSub($by_agent_name, 'by_agent_name', 'user.id', '=', 'by_agent_name.user_id');
            }
        }
        if (!empty($channel_id)) {
            $query->where('channel_id', $channel_id);
        }
        $rpt_user_query = DB::connection('slave')->table('rpt_user')
            ->selectRaw('
                sum(deposit_user_amount) as deposit_user_amount,
                sum(withdrawal_user_amount) as withdrawal_user_amount,
                sum(bet_user_amount) as bet_user_amount,
                sum(prize_user_amount) as prize_user_amount,
                sum(coupon_user_amount) as coupon_user_amount,
                sum(return_user_amount) as return_user_amount,
                sum(turn_card_user_winnings) as turn_card_user_winnings,
                sum(promotion_user_winnings) as promotion_user_winnings,
                sum(back_user_amount) as back_user_amount,
                sum(deposit_user_cnt) as recharge_range_count,
                sum(withdrawal_user_cnt) as draw_range_count,
                sum(rebate_withdraw_amount) as deal_money_num,
                IFNULL((sum(deposit_user_amount) - sum(withdrawal_user_amount)),0) as dw,
                IFNULL((sum(bet_user_amount) - sum(prize_user_amount)),0) as dp,
                user_id')
            ->where('count_date','>=',$date_start)
            ->where('count_date','<=',$date_end)->groupBy(['user_id']);

        //历史总投注，历史总充值,历史总彩金，历史总取款
        $ls_rpt_user = DB::connection('slave')->table('rpt_user')->selectRaw('user_id,sum(bet_user_amount) as lsztz,sum(deposit_user_amount) as lszcz,' .
            'sum(coupon_user_amount)+sum(promotion_user_winnings)+sum(turn_card_user_winnings) as lszcj,sum(withdrawal_user_amount) as lszqk')->groupBy(['user_id']);
        //首充金额，首充时间
        $first_rpt_user = DB::connection('slave')->table('rpt_user')->selectRaw('user_id,deposit_user_amount as first_deposit_user_amount')->where('first_deposit', 1)->groupBy(['user_id']);
        $user_monthly_award_query = \DB::connection('slave')->table('user_monthly_award')->selectRaw("ifnull(award_money,0) as award_money,user_id")->where('created', '>=', $date_start)
            ->where('created', '<=', $date_end . ' 23:59:59');
        $query = $query->joinSub($rpt_user_query, 'rpt_user', 'user.id', '=', 'rpt_user.user_id', 'left')
            ->joinSub($ls_rpt_user, 'ls_rpt_user', 'user.id', '=', 'ls_rpt_user.user_id', 'left')
            ->joinSub($first_rpt_user, 'first_rpt_user', 'user.id', '=', 'first_rpt_user.user_id', 'left')
            ->joinSub($user_monthly_award_query, 'user_monthly_award', 'user_monthly_award.user_id', '=', 'user.id', 'left')
            ->leftJoin('user_agent', 'user_agent.user_id', '=', 'user.id')
            ->leftJoin('profile', 'profile.user_id', '=', 'user.id')
            ->leftJoin('funds', 'user.wallet_id', '=', 'funds.id')
            ->leftJoin('user_level', 'user.ranting', '=', 'user_level.id');
        #->leftJoin('user_monthly_award','user_monthly_award.user_id','=','user.id');
        if ($amount_start > 0) {
            $query->where('rpt_user.deposit_user_amount', '>=', $amount_start);
        }
        if ($amount_end > 0) {
            $query->where('rpt_user.deposit_user_amount', '<=', $amount_end);
        }
        $query->groupBy(['user.id']);
        $data = $query->selectRaw("
            user.id user_id,
            user.channel_id,
            user.mobile,
            user_level.name as ranting,
            inet6_ntoa(user.login_ip) AS login_ip,
            user.last_login,
            user_agent.uid_agent AS agent_id,
            user_agent.uid_agent_name AS agent_name,
            user.name user_name,
            profile.name AS real_name,
            user_agent.uid_agent_name AS agent_name,
            user.created AS register_time,
            user.first_recharge_time as first_create_time,
            ls_rpt_user.lsztz as total_bet_user_amount,
            ls_rpt_user.lszcz as total_deposit_user_amount,
            ls_rpt_user.lszcj as total_Winnings,
            ls_rpt_user.lszqk as total_withdrawal_user_amount,
            first_rpt_user.first_deposit_user_amount as first_deposit_user_amount,
            TRUNCATE(funds.balance/100, 2) as balance,
            sum(user_monthly_award.award_money) as award_amount,
            rpt_user.*
            ")
            ->orderBy($field_id, $sort_way)
            ->get()->toArray();

        //补充每个用户的游戏盈亏数据
        $user_list = array_column($data, 'user_id');
        $fmt_yk_data = [];
        $game_menu_info = DB::connection('slave')->table("game_menu")->whereRaw('pid=? and status=?',  [0, 'enabled'])->select(['id', 'type', 'rename'])->get()->toArray();
        $game_map = [];
        if ($user_list) {
            foreach ($game_menu_info as $item) {
                $game_map[$item->type] = $item->rename;
            }
            $yk_sql = "";
            foreach ($game_map as $k => $v) {
                $yk_sql .= "TRUNCATE(cast(sum(lose_earn_list->'$.{$k}') as decimal(18,2)), 2) as yk_{$k} ,";
            }
            //            $yk_data = DB::connection('slave')->table("orders_report")->selectRaw($yk_sql.' user_id')
            //                ->whereIn('user_id', $user_list)->whereRaw('date>=? and date<=?', [$date_start,$date_end])
            //                ->groupBy(['user_id'])->get()->toArray();
            //            if ($yk_data) {
            //                foreach ($yk_data as $yk) {
            //                    $yk = (array)$yk;
            //                    $cur_uid = $yk['user_id'];
            //                    unset($yk['user_id']);
            //                    $fmt_yk_data[$cur_uid] = $yk;
            //                }
            //            }
            //分块查询，一次查询一个月数据sql占位符太多会报错
            if (count($user_list) > 1000) {
                $chunk_user = array_chunk($user_list, intval(count($user_list) / 3));
                foreach ($chunk_user as $cu) {
                    $yk_data = DB::connection('slave')->table("orders_report")->selectRaw($yk_sql . ' user_id')
                        ->whereIn('user_id', $cu)->whereRaw('date>=? and date<=?', [$date_start, $date_end])
                        ->groupBy(['user_id'])->get()->toArray();
                    if ($yk_data) {
                        foreach ($yk_data as $yk) {
                            $yk = (array)$yk;
                            $cur_uid = $yk['user_id'];
                            unset($yk['user_id']);
                            $fmt_yk_data[$cur_uid] = $yk;
                        }
                    }
                }
            } else {
                $yk_data = DB::connection('slave')->table("orders_report")->selectRaw($yk_sql . ' user_id')
                    ->whereIn('user_id', $user_list)->whereRaw('date>=? and date<=?', [$date_start, $date_end])
                    ->groupBy(['user_id'])->get()->toArray();
                if ($yk_data) {
                    foreach ($yk_data as $yk) {
                        $yk = (array)$yk;
                        $cur_uid = $yk['user_id'];
                        unset($yk['user_id']);
                        $fmt_yk_data[$cur_uid] = $yk;
                    }
                }
            }
        }
        //处理会员报表充值数据
        $user_ids       =  Utils::arrayChangeKey($data,'user_id');
        $user_data      = DB::connection('slave')->table("user_data")
            ->whereIn('user_id',$user_ids)->get(['deposit_num','withdraw_num','user_id','withdraw_cj_num']);
        $deal_log = [];
        if ($user_data){
            foreach ($user_data as $item){
                $deal_log[$item->user_id] = $item;
            }
        }

        //获取会员权限
        $rid = $this->playLoad['rid'];
        $memberControl = DB::table('admin_user_role')->where('id', $rid)->value('member_control');
        $addressBook = json_decode($memberControl, true);
        $addressBook = $addressBook['address_book'] ?? false;
        $notInKeys = ['login_ip', 'user_name', 'channel_id', 'recharge_time', 'real_name', 'agent_name', 'register_time', 'first_create_time', 'ranting', 'mobile'];
        foreach ($data as &$val) {
            $val = (array)$val;
            $val['mobile'] = mobileEncrypt(Utils::RSADecrypt($val['mobile']), $addressBook);

            //$val['award_amount'] = \DB::connection('slave')->table('user_monthly_award')->where('user_id',$val['user_id'])->value(DB::connection('slave')->raw("sum(award_money) as award_amount")) / 100;
            $val['award_amount'] =  bcdiv($val['award_amount'],100,2);
            $val['dw_drop_amount'] = bcmul($val['dw'],1,2);
            $val['bs_drop_amount'] = bcmul($val['dp'],1,2);;
            
            $val['recharge_count'] = 0;
            $val['draw_count'] = 0;
            if (!empty($deal_log[$val['user_id']])){
                $val['recharge_count']        = $deal_log[$val['user_id']]->deposit_num;
                $val['draw_count']            = $deal_log[$val['user_id']]->withdraw_num - $deal_log[$val['user_id']]->withdraw_cj_num;
            }

            foreach ($val as $k => &$v) {
                if (!in_array($k, $notInKeys)) {
                    $v = (float)$v ?? 0;
                }
            }
            $val['recharge_time'] = '';
            $rechargeExists=DB::connection('slave')->table('funds_deposit')
                        ->where('user_id', $val['user_id'])->where('money', '>', 0)
                        ->limit(1)->orderBy('recharge_time', 'desc')->exists();
            if ($rechargeExists){
                $info = DB::connection('slave')->table('funds_deposit')
                    ->where('user_id', $val['user_id'])->where('money', '>', 0)
                    ->limit(1)->orderBy('recharge_time', 'desc')
                    ->pluck('recharge_time');
                if (isset($info[0])) $val['recharge_time'] = $info[0];
            }
            //导出时不需要这两个字段
            unset($val['dw']);
            unset($val['dp']);

            //盈亏数据
            //            $val['yk_data'] = $fmt_yk_data[$val['user_id']] ?? [];
            if (isset($fmt_yk_data[$val['user_id']])) {
                foreach ($fmt_yk_data[$val['user_id']] as $yk_key => $yk_val) {
                    $val[$yk_key] = $yk_val;
                }
            } else {
                foreach ($game_map as $gk => $gv) {
                    $val["yk_" . $gk] = "";
                }
            }
            $val['game_map'] = $game_map;
            $val['user_name'] = "=\"{$val['user_name']}\"";
            $val['real_name'] = str_replace([" ", "　", "\t", "\n", "\r"], '', $val['real_name']);
            $val['last_login']     = $val['last_login'] ? date('Y-m-d H:i:s',$val['last_login']) : '';
        }

        //页面勾选要导出的字段
        $export_field = explode(",", $export_field);
        $export_title = [];
        $en_export_field = [];
        foreach ($export_field as $k) {
            if (isset($this->title[$k])) {
                $export_title[$k] = $this->title[$k];
                $en_export_field[$k] = $this->lang->text($this->en_title[$k]);
            }
            if (isset($this->en_title[$k])) {
                $en_export_field[$k] = $this->en_title[$k];
                $en_export_field[$k] = $this->lang->text($this->en_title[$k]);
            }
        }
        array_unshift($data, $en_export_field);

        $this->exportExcel('MemberReport', $export_title, $data);
    }

    public function exportExcel($file, $title, $data)
    {
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
