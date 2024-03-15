<?php
/**
 * Created by PhpStorm.
 * User: benchan
 * Date: 2019/1/17
 * Time: 14:39
 */


use Logic\Admin\BaseController;
use Utils\Utils;

return new  class() extends BaseController {
    const TITLE = '会员报表';
    const DESCRIPTION = '';

    const QUERY = [
        'day_begin'   => 'datetime(required) #开始日期',
        'day_end'     => 'datetime(required) #结束日期',
        'field_id'    => "int() #排序字段 默认user_id, 1=存款 2=取款 3=差额 4=投注情况 5=派彩金额 6=投注情况.差额 7=活动彩金 8=回水金额 9=晋升彩金 10=转卡彩金 11=返佣总金额 12=月俸禄",
        'sort_way'    => "string() #排序规则 desc=降序 asc=升序",
    ];

    const PARAMS = [];
    const SCHEMAS = [
        [
            'deposit_user_amount'=>'用戶充值金额',
            'withdrawal_user_amount'=>'用戶充值金额',
            'dw_drop_amount'=>'存取差额',
            'bet_user_amount'=>'用戶投注金额',
            'prize_user_amount'=>'用戶派獎金额',
            'bs_drop_amount'=>'投注派獎差额',
            'coupon_user_amount'=>'用戶彩金金额',     //活动彩金
            'return_user_amount'=>'用戶回水金额',
            'turn_card_user_winnings'=>'用戶轉卡金额',    //转卡彩金
            'promotion_user_winnings'=>'用戶晉級金额',    //晋升彩金
            'back_user_amount'=>'用戶返佣金额',
            'award_amount'=>'月俸禄',
            'total_bet_user_amount' => '历史总投注',
            'total_deposit_user_amount' => '历史总充值',
            'total_Winnings' => '历史总彩金',
            'total_withdrawal_user_amount' => '历史总兑换',    //历史总取款
            'first_deposit_user_amount' => '首充金额',
            'first_create_time' => '首充时间',
            'balance' => '账户余额',
            'ranting' => '会员等级',
        ],
    ];
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run() {

        $page = $this->request->getParam('page',1);
        $page_size = $this->request->getParam('page_size',20);
        $date_start = $this->request->getParam('date_start',date('Y-m-d'));
        $date_end = $this->request->getParam('date_end',date('Y-m-d'));
        $user_name = $this->request->getParam('user_name');
        $agent_name = $this->request->getParam('agent_name');    //按上级代理账号过滤查询
        $channel_id = $this->request->getParam('channel_id', '');    //渠道号
        $amount_start = intval($this->request->getParam('amount_start', 0));    //充值金额最小值
        $amount_end = intval($this->request->getParam('amount_end', 0));    //充值金额最大值
        $last_login_start = $this->request->getParam('last_login_start');
        $last_login_end   = $this->request->getParam('last_login_end');
        $register_start   = $this->request->getParam('register_start');
        $register_end     = $this->request->getParam('register_end');
        $is_recharge      = $this->request->getParam('is_recharge');
        $user_id          = $this->request->getParam('user_id');
        //新增排序 默认user_id, 1=存款 2=取款 3=差额 4=投注情况 5=派彩金额 6=投注情况.差额 7=活动彩金 8=回水金额 9=晋升彩金 10=转卡彩金 11=返佣总金额 12=月俸禄
        $field_id = $this->request->getParam('field_id', '');
        $sort_way = $this->request->getParam('sort_way', 'asc');
        if(!in_array($sort_way, ['asc', 'desc'])) $sort_way = 'asc';

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
//            case 13:
//                $field_id = 'total_bet_user_amount';    //历史总投注
//                break;
            case 14:
                $field_id = 'balance';    //余额
                break;
//            case 15:
//                $field_id = 'total_deposit_user_amount';    //历史总充值
//                break;
//            case 16:
//                $field_id = 'total_withdrawal_user_amount';    //历史总兑换
//                break;
//            case 17:
//                $field_id = 'first_deposit_user_amount';    //首充金额
//                break;
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

//        $user_agent = DB::table('user_agent')->where('user_id',$user->id)->selectRaw('uid_agent as agent_id,uid_agent_name as agent_name')->first();

//        $real_name = DB::table('profile')->where('user_id',$user->id)->value('name');
        //月俸禄
//        $award = DB::table('user_monthly_award')->where('user_id',$user->id)->value(DB::raw("sum(award_money) as award_amount"));

        if($user_name){
            $query = DB::connection('slave')->table('user')->where('user.name',$user_name);
        }else{
            $from_query = DB::connection('slave')->table('rpt_user')->select('user_id')->where('count_date','>=',$date_start)
                ->where('count_date','<=',$date_end)
                ->UNION(DB::connection('slave')->table('user')->where([['created','>=',$date_start.' 00:00:00'],['created','<=',$date_end.' 23:59:59']])->select('id as user_id'));
            $query = DB::connection('slave')->table('user')->joinSub($from_query,'user_tmp','user.id','=','user_tmp.user_id');
            //按上级代理过滤
            if ($agent_name) {
                $by_agent_name = DB::connection('slave')->table('user_agent')->where('uid_agent_name', $agent_name)->select('user_id');
                $query = DB::connection('slave')->table('user')->joinSub($by_agent_name, 'by_agent_name', 'user.id','=','by_agent_name.user_id');
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

        $user_monthly_award_query = DB::connection('slave')->table('user_monthly_award')->selectRaw("ifnull(award_money,0) as award_money,user_id")->where('created','>=',$date_start)
            ->where('created','<=',$date_end.' 23:59:59');
        $query = $query->joinSub($rpt_user_query,'rptUser','user.id','=','rptUser.user_id','left')
            ->joinSub($user_monthly_award_query,'user_monthly_award','user_monthly_award.user_id','=','user.id','left')
            ->leftJoin('user_agent','user_agent.user_id','=','user.id')
            ->leftJoin('profile','profile.user_id','=','user.id')
            ->leftJoin('funds','user.wallet_id','=','funds.id')
            ->leftJoin('user_level', 'user.ranting', '=', 'user_level.id');
            //->leftJoin('user_monthly_award','user_monthly_award.user_id','=','user.id');
        if ($amount_start>0) {
            $query->where('rptUser.deposit_user_amount','>=', $amount_start);
        }
        if ($amount_end>0) {
            $query->where('rptUser.deposit_user_amount','<=', $amount_end);
        }
        if (!empty($user_id)) $query->where('user.id', $user_id);

        !empty($register_start) && $query->where('user.created', '>=', $register_start);
        !empty($register_end) && $query->where('user.created', '<=', $register_end . ' 23:59:59');
        if (isset($is_recharge)) {

            if ($is_recharge == 1){
                $query->where('user.first_recharge_time','!=', '');
            }else{
                $query->where('user.first_recharge_time','=', null);
            }
        }


        if ($last_login_start) {
            $last_login_start      =  strtotime($last_login_start);
            $last_login_end        =  strtotime($last_login_end);
            $query->whereBetween('last_login', [$last_login_start, $last_login_end]);
        }
        $query->groupBy(['user.id']);
        $total = clone $query;
        $data = $query->forPage($page,$page_size)->selectRaw("
            user.id user_id,
            user.channel_id,
            user.mobile,
            user_level.name as ranting,
            inet6_ntoa(user.login_ip) AS login_ip,
            user.last_login,
            user.name user_name,
            profile.name AS real_name,
            user_agent.uid_agent AS agent_id,
            user_agent.uid_agent_name AS agent_name,
            user.created AS register_time,
            user.first_recharge_time as first_create_time,
            TRUNCATE(funds.balance/100, 2) as balance,
            sum(user_monthly_award.award_money) as award_amount,
            rptUser.deposit_user_amount,
            rptUser.withdrawal_user_amount,
            rptUser.bet_user_amount,
            rptUser.prize_user_amount,
            rptUser.coupon_user_amount,
            rptUser.return_user_amount,
            rptUser.turn_card_user_winnings,
            rptUser.promotion_user_winnings,
            rptUser.back_user_amount,
            rptUser.recharge_range_count,
            rptUser.draw_range_count,
            rptUser.deal_money_num,
            rptUser.dw,
            rptUser.dp
            ")
            ->orderBy($field_id, $sort_way)
            ->get()->toArray();

        //补充每个用户的游戏盈亏数据
        $user_list = array_column($data,'user_id');
        $fmt_yk_data = [];
        $game_menu_info = DB::connection('slave')->table("game_menu")->whereRaw('pid=? and status=?',  [0,'enabled'])->select(['id','type','rename'])->get()->toArray();
        $game_map = [];
        if ($user_list) {
            foreach ($game_menu_info as $item) {
                $game_map[$item->type] = $item->rename;
            }
            $yk_sql = "";
            foreach ($game_map as $k=>$v) {
                $yk_sql .= "TRUNCATE(cast(sum(lose_earn_list->'$.$k') as decimal(18,2)), 2) as yk_$k ,";
            }
            $yk_data = DB::connection('slave')->table("orders_report")->selectRaw($yk_sql.' user_id')
                ->whereIn('user_id', $user_list)->whereRaw('date>=? and date<=?', [$date_start,$date_end])
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
        //处理会员报表充值数据
        $user_ids = Utils::arrayChangeKey($data, 'user_id');
        $user_data = DB::connection('slave')->table("user_data")
            ->whereIn('user_id', $user_ids)->get(['deposit_num', 'withdraw_num', 'user_id', 'withdraw_cj_num']);
        $deal_log = [];
        if ($user_data) {
            foreach ($user_data as $item) {
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

            //$val['award_amount'] = \DB::table('user_monthly_award')->where('user_id',$val['user_id'])->value(DB::raw("sum(award_money) as award_amount")) / 100;
            $val['award_amount'] =  bcdiv($val['award_amount'],100,2);
            $val['dw_drop_amount'] = bcmul($val['dw'],1,2);
            $val['bs_drop_amount'] = bcmul($val['dp'],1,2);
            foreach ($val as $k => &$v) {
                if (!in_array($k, $notInKeys)) {
                    $v = (float)$v ?? 0;
                }
            }
            $val['recharge_time']='';
            $info = DB::connection('slave')->table('funds_deposit')->where('user_id',$val['user_id'])->where('money', '>', 0)->limit(1)->orderBy('recharge_time','desc')->pluck('recharge_time');
            if (isset($info[0])) $val['recharge_time'] = $info[0];
            //历史总投注，历史总充值,历史总彩金，历史总取款, 首充金额，首充时间
            $ls_rpt_user = DB::connection('slave')->table('rpt_user')->selectRaw('user_id,sum(bet_user_amount) as lsztz,sum(deposit_user_amount) as lszcz,'.
                'sum(coupon_user_amount)+sum(promotion_user_winnings)+sum(turn_card_user_winnings) as lszcj,sum(withdrawal_user_amount) as lszqk, sum(if(first_deposit=1,deposit_user_amount,0)) as first_deposit_user_amount')
            ->where('user_id', $val['user_id'])->get()->toArray();
            $val['total_bet_user_amount'] = $ls_rpt_user[0]->lsztz ?? 0;
            $val['total_deposit_user_amount'] = $ls_rpt_user[0]->lszcz ?? 0;
            $val['total_Winnings'] = $ls_rpt_user[0]->lszcj ?? 0;
            $val['total_withdrawal_user_amount'] = $ls_rpt_user[0]->lszqk ?? 0;
            $val['first_deposit_user_amount'] = $ls_rpt_user[0]->first_deposit_user_amount ?? 0;
            //盈亏数据
            $val['yk_data'] = $fmt_yk_data[$val['user_id']] ?? [];
            $val['game_map'] = $game_map;
            //充值总次数 取款次数
            $val['recharge_count'] = 0;
            $val['draw_count'] = 0;
            if (!empty($deal_log[$val['user_id']])){
                $val['recharge_count']        = $deal_log[$val['user_id']]->deposit_num;
                $val['draw_count']            = $deal_log[$val['user_id']]->withdraw_num - $deal_log[$val['user_id']]->withdraw_cj_num;
            }
            $val['last_login']     = $val['last_login'] ? date('Y-m-d H:i:s',$val['last_login']) : '';

        }
        $attr['total'] = $total->pluck('rptUser.user_id')->count();
        $attr['size'] = $page_size;
        $attr['count'] = $page;
        return $this->lang->set(0,[],$data,$attr);
    }
};
