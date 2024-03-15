<?php
/**
 * Created by PhpStorm.
 * User: benchan
 * Date: 2019/1/17
 * Time: 14:39
 */


use Logic\Admin\BaseController;

return new  class() extends BaseController {
    const TITLE = '出入款汇总';
    const DESCRIPTION = '';
    
    const QUERY = [
        'day_begin'   => 'datetime(required) #开始日期',
        'day_end'     => 'datetime(required) #结束日期',
        
    ];
    
    const PARAMS = [];
    const SCHEMAS = [
        [
            'offline_amount'=>'线下入款 ',
            'online_amount'=>'线上入款 ',
            'manual_deposit_amount'=>'人工入款',
            'withdrawal_amount'=> '出款金额 ',
            'coupon_amount'=>'优惠彩金 ',
            'return_amount'=> '回水金额 ',
            'promotion_winnings'=> '晋升彩金 ',
            'turn_card_winnings'=>'转卡彩金 ',
            'manual_deduction_amount'=>'人工扣款 ',
            'new_register_deposit_amount'=>'新增充值金额',
            'new_register_deposit_num'=>'新注册的玩家且有充值人数',
            'new_deposit_user_num'=>'首充人数',
            'deposit_user_num'=>'充值人数',
            'deposit_amount'=>'总充值金额',
            'new_user_num'=>'新用户数',
            'new_valid_agent_num'=>'新增有效代理数',
            'new_register_avg'=>'新增人均充值金额',
            'agent_first_deposit_num'=>'每日代理带来的新充会员数(首充用户且有上级的人数)',
            'user_num'=>'总用户数',
            'new_register_avg' => '新增人均(新增充值金额/新注册的玩家且有充值人数)',
            'confiscate_amount' => '没收资金'
        ],
    ];
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run() {
        $date_start = $this->request->getParam('date_start',date('Y-m-d'));
        $date_end = $this->request->getParam('date_end',date('Y-m-d'));

        $data = DB::connection('slave')->table('rpt_deposit_withdrawal_day')
            ->where('create_time','>=',$date_start." 00:00:00")
            ->where('create_time','<=',$date_end." 23:59:59")
            ->selectRaw("
            sum(offline_amount) as offline_amount,
            sum(offline_cnt) as offline_cnt,
            sum(online_amount) as online_amount,
            sum(online_cnt) as online_cnt,
            sum(manual_deposit_amount) as manual_deposit_amount,
            sum(manual_deposit_cnt) as manual_deposit_cnt,
            sum(income_amount) as income_amount,
            sum(withdrawal_amount) as withdrawal_amount,
            sum(withdrawal_cnt) as withdrawal_cnt,
            sum(confiscate_amount) as confiscate_amount,
            sum(confiscate_cnt) as confiscate_cnt,
            sum(coupon_amount) as coupon_amount,
            sum(coupon_cnt) as coupon_cnt,
            sum(return_amount) as return_amount,
            sum(return_cnt) as return_cnt,
            sum(promotion_winnings) as promotion_winnings,
            sum(promotion_winnings_cnt) as promotion_winnings_cnt,
            sum(turn_card_winnings) as turn_card_winnings,
            sum(turn_card_winnings_cnt) as turn_card_winnings_cnt,
            sum(manual_deduction_amount) as manual_deduction_amount,
            sum(manual_deduction_cnt) as manual_deduction_cnt,
            sum(back_amount) as back_amount,
            sum(back_cnt) as back_cnt,
            sum(out_amount) as out_amount,
            sum(game_code_amount) as game_code_amount,
            sum(game_code_cnt) as game_code_cnt,
            sum(new_register_deposit_amount) as new_register_deposit_amount,
            sum(new_register_deposit_num) as new_register_deposit_num,
            sum(new_deposit_user_num) as new_deposit_user_num,
            sum(deposit_user_num) as deposit_user_num,
            sum(new_user_num) as new_user_num,
            sum(new_valid_agent_num) as new_valid_agent_num,
            sum(agent_first_deposit_num) as agent_first_deposit_num,
            sum(shares_settle_cnt) as shares_settle_cnt,
            sum(shares_settle_amount) as shares_settle_amount,
            sum(profit_loss_cnt) as profit_loss_cnt,
            sum(profit_loss_amount) as profit_loss_amount
            ")
            ->first();

//        print_r(DB::connection('slave')->getQueryLog());

        foreach ($data as &$v) {
            $v = $v ?? 0;
         }
        $res = (array)$data;
        $res['total_Winnings'] = sprintf("%01.2f", ($res['coupon_amount'] + $res['promotion_winnings'] + $res['turn_card_winnings']));  //总彩金
        $res['total_Winnings_cnt'] = intval($res['coupon_cnt'] + $res['promotion_winnings_cnt'] + $res['turn_card_winnings_cnt']);  //总彩金笔数
        if($res){
            $res['user_num'] = \Model\User::getUserNum();
            $res['deposit_amount'] = bcadd($res['offline_amount'],$res['online_amount'],2);
            $res['deposit_amount'] = bcadd($res['deposit_amount'],$res['manual_deposit_amount'],2);
            $res['new_register_avg'] = $res['new_register_deposit_num'] == 0 ? 0: bcdiv($res['new_register_deposit_amount'],$res['new_register_deposit_num'],2);
        }

        //优惠彩金去重人数
        $coupon_user = DB::connection('slave')->table('funds_deal_log')->whereIn('deal_type',[105,114])
            ->where('created', '>=', $date_start.' 00:00:00')
            ->where('created', '<=', $date_end.' 23:59:59')
            ->selectRaw('COUNT(DISTINCT(user_id)) AS `num`')->get()->toArray();
        $res['coupon_num'] = $coupon_user[0]->num;
        //晋升彩金去重人数
        $promotion_user = DB::connection('slave')->table('funds_deal_log')->where('deal_type', 308)
            ->where('created', '>=', $date_start.' 00:00:00')
            ->where('created', '<=', $date_end.' 23:59:59')
            ->selectRaw('COUNT(DISTINCT(user_id)) AS `num`')->get()->toArray();
        $res['promotion_num'] = $promotion_user[0]->num;
        //转卡彩金去重人数
        $turn_card_user = DB::connection('slave')->table('funds_deal_log')->where('deal_type', 309)
            ->where('created', '>=', $date_start.' 00:00:00')
            ->where('created', '<=', $date_end.' 23:59:59')
            ->selectRaw('COUNT(DISTINCT(user_id)) AS `num`')->get()->toArray();
        $res['turn_card_num'] = $turn_card_user[0]->num;

        //获取代理退佣
        $res['back_amount'] = DB::connection('slave')->table('funds_deal_log')
                                    ->whereIn('deal_type',[108,704])
                                    ->where('created', '>=', $date_start." 00:00:00")
                                    ->where('created', '<=', $date_end." 23:59:59")
                                    ->sum('deal_money');
        $res['back_amount'] = bcdiv($res['back_amount'], 100, 2);
        $res['back_cnt'] = DB::connection('slave')->table('funds_deal_log')
            ->whereIn('deal_type',[108,704])
            ->where('created', '>=', $date_start." 00:00:00")
            ->where('created', '<=', $date_end." 23:59:59")
            ->count();

        //支出总计 计算方式变更
        $res['out_amount'] = sprintf("%01.2f", ($res['withdrawal_amount'] + $res['total_Winnings'] + $res['return_amount'] + $res['manual_deduction_amount'] + $res['game_code_amount'] + $res['back_amount'] + $res['shares_settle_amount'] + $res['profit_loss_amount']));



        return $res;
    }
};
