<?php
/**
 * Created by PhpStorm.
 * User: simons
 * Date: 2022/4/11
 * Time:
 */

use Logic\Admin\BaseController;
use Logic\Set\SystemConfig;

return new  class() extends BaseController {
    const TITLE = '出入款汇总2';
    const DESCRIPTION = '';

    const QUERY = [
        'day_begin' => 'datetime(required) #开始日期',
        'day_end'   => 'datetime(required) #结束日期'
    ];

    const PARAMS = [];
    const SCHEMAS = [
        [
            'offline_amount'          => '线下入款 ',
            'online_amount'           => '线上入款 ',
            'manual_deposit_amount'   => '人工入款',
            'withdrawal_amount'       => '出款金额 ',
            'coupon_amount'           => '优惠彩金 ',
            'return_amount'           => '回水金额 ',
            'promotion_winnings'      => '晋升彩金 ',
            'turn_card_winnings'      => '转卡彩金 ',
            'manual_deduction_amount' => '人工扣款 ',
            'back_amount'             => '代理退佣',
            'game_code_amount'        => '游戏洗码',
        ]
    ];
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run() {
        $date_start = $this->request->getParam('date_start');
        $date_end   = $this->request->getParam('date_end');

        //是否有完整的time
        if(empty($date_start) || empty($date_end))
            return $this->lang->set(0, [], '');

        //线下入款[金额，总笔数]
        $offline_amount_arr = $this->getFoundsDepsitInfo($date_start, $date_end, 1, 102);

        //线上支付[金额，总笔数]
        $online_amount_arr = $this->getFoundsDepsitInfo($date_start, $date_end, 1, 101);

        //人工入款[金额，总笔数]
        $manual_deposit_amount_arr = $this->getFoundsDepsitInfo($date_start, $date_end, 1, 106);

        //出款金额[金额，总笔数]
        $withdrawal_amount_arr = $this->getFoundsDepsitInfo($date_start, $date_end, 2, [201,218]);

        //优惠彩金+手动发放优惠[金额，总笔数]
        $coupon_amount_arr1 = $this->getFoundsDepsitInfo($date_start, $date_end, 1, 105);
        $coupon_amount_arr2 = $this->getFoundsDepsitInfo($date_start, $date_end, 1, 114);

        //没收资金[金额，总笔数]
        $confiscate_amount_arr = $this->getFoundsDepsitInfo($date_start, $date_end, 2, [213,219]);

        $coupon_amount_arr = [
            'money'   => bcadd($coupon_amount_arr1['money'], $coupon_amount_arr2['money'], 2),
            'numbers' => bcadd($coupon_amount_arr1['numbers'], $coupon_amount_arr2['numbers'], 2),
        ];

        //回水金额[金额，总笔数]
//        $return_amount_arr = $this->getFoundsDepsitInfo($date_start, $date_end, 1, 107);
        $return_amount_arr = $this->getFoundsDepsitInfo($date_start, $date_end, 1, [701,702,703,109,113]);

        //晋升彩金[金额，总笔数]
        $promotion_winnings_arr = $this->getFoundsDepsitInfo($date_start, $date_end, 1, 308);

        //转卡彩金[金额，总笔数]
        $turn_card_winnings_arr = $this->getFoundsDepsitInfo($date_start, $date_end, 1, 309);

        //人工扣款[金额，总笔数]
        $manual_deduction_amount_arr = $this->getFoundsDepsitInfo($date_start, $date_end, 2, 204);

        //代理退佣[金额，总笔数]
        $agency_amount_arr = $this->getFoundsDepsitInfo($date_start, $date_end, 1, [108,704]);

        //游戏洗码[金额，总笔数]
        $wash_code_arr = $this->getFoundsDepsitInfo($date_start, $date_end, 1, 501);

        //股东结算
        $shares_arr = DB::connection('slave')->table('unlimited_agent_bkge')
                        ->selectRaw("count(DISTINCT(user_id)) as cnt,IFNULL(sum(settle_amount),0) as amount")
                        ->where("date", '>=', date('Y-m-d',strtotime($date_start)))
                        ->where("date", '<=', date('Y-m-d', strtotime($date_end)))
                        ->where("status",1)
                        ->where("settle_amount",'>',0)
                        ->get()[0];

        $profitStartTime = date('Y-m-d 00:00:00',strtotime($date_start));
        $profitEndTime = date('Y-m-d 23:59:59',strtotime($date_end));
        $sql = "SELECT
	sum( a1.amount ) AS amount,
	sum(a1.num) as cnt	
FROM
	(
SELECT
	count( DISTINCT ( user_id ) ) AS num,
	IFNULL( sum( settle_amount ), 0 ) AS amount 
FROM
	agent_loseearn_bkge 
WHERE 
	 bkge_time >= '{$profitStartTime}'
	and bkge_time <= '{$profitEndTime}' 
	UNION
SELECT
	count( DISTINCT ( user_id ) ) AS num,
	IFNULL( sum( settle_amount ), 0 ) AS amount 
FROM
	agent_loseearn_week_bkge 
WHERE
	 bkge_time >= '{$profitStartTime}'
	AND bkge_time <= '{$profitEndTime}' 
	UNION
SELECT
	count( DISTINCT ( user_id ) ) AS num,
	IFNULL( sum( settle_amount ), 0 ) AS amount 
FROM
	agent_loseearn_week_bkge 
WHERE
	 bkge_time >= '{$profitStartTime}' 
	AND bkge_time <= '{$profitEndTime}'
	) a1";
        $profit_loss = DB::connection('slave')->select($sql)[0];

        $income_amount = bcadd(bcadd($offline_amount_arr['money'], $online_amount_arr['money'], 2), $manual_deposit_amount_arr['money'], 2);
        $out_amount    = bcmul($withdrawal_amount_arr['money'] + $coupon_amount_arr['money'] + $return_amount_arr['money'] + $promotion_winnings_arr['money'] + $turn_card_winnings_arr['money'] + $manual_deduction_amount_arr['money'] + $agency_amount_arr['money'] + $wash_code_arr['money'] + $shares_arr->amount + $profit_loss->amount, 1, 2);

        //新增注册人数
        $new_user_num = DB::table('user')->where('created', '>=', $date_start)->where('created', '<=', $date_end)->count();

        //总充值人数\首充人数\总充值金额
        $depositSql = "SELECT
	ifnull( count( DISTINCT `user_id` ), 0) deposit_user_num,
	ifnull( sum( IF ( FIND_IN_SET('new', `state`), 1, 0 )), 0) new_deposit_user_num,
	ifnull( sum( IF ( FIND_IN_SET('offline', `state`), money, 0 ))/ 100, 0 ) offline_amount,
	ifnull( sum( IF ( FIND_IN_SET('online', `state`), money, 0 ))/ 100, 0 ) online_amount,
	ifnull( sum( IF ( FIND_IN_SET('tz', `state`), money, 0 ))/ 100, 0 ) manual_deposit_amount
FROM
	`funds_deposit`
WHERE
	`created` >= '".$date_start."' 
	AND `created` < '".$date_end."'
	AND `money` > 0 
	AND FIND_IN_SET('paid', `status`)";
        $depositNum = DB::connection('slave')->select($depositSql)[0];

        //新增充值金额
        $regNumSql = "SELECT
	ifnull( count( DISTINCT `user_id` ), 0 ) new_register_deposit_num,
	ifnull( sum( `money` ), 0 ) / 100 new_register_deposit_amount 
FROM
	`funds_deposit`
WHERE
	created >= '".$date_start."'
	AND created < '".$date_end."'
	AND money > 0 
    AND FIND_IN_SET('paid', `status`)
	AND user_id IN (
	SELECT
	    `id`
	FROM
		`user` 
	WHERE
	    `first_recharge_time` >= '".$date_start."'
	AND `first_recharge_time` < '".$date_end."')";
        $regNum = DB::connection('slave')->select($regNumSql)[0];

        //新增有效代理数、每日代理带来的新充会员数(首充用户且有上级的人数)
        $data = DB::connection('slave')
                    ->table('rpt_deposit_withdrawal_day')
                    ->where('create_time','>=',$date_start)
                    ->where('create_time','<=',$date_end)
                    ->selectRaw("sum(new_valid_agent_num) as new_valid_agent_num,sum(agent_first_deposit_num) as agent_first_deposit_num")
                    ->first();
        $res = (array)$data;

        $data = [
            'offline_amount'         => $offline_amount_arr['money'],
            'offline_cnt'            => $offline_amount_arr['numbers'],
            'online_amount'          => $online_amount_arr['money'],
            'online_cnt'             => $online_amount_arr['numbers'],
            'manual_deposit_amount'  => $manual_deposit_amount_arr['money'],
            'manual_deposit_cnt'     => $manual_deposit_amount_arr['numbers'],
            'withdrawal_amount'      => $withdrawal_amount_arr['money'],
            'withdrawal_cnt'         => $withdrawal_amount_arr['numbers'],
            'coupon_amount'          => $coupon_amount_arr['money'],
            'coupon_cnt'             => intval($coupon_amount_arr['numbers']),
            'return_amount'          => $return_amount_arr['money'],
            'return_cnt'             => $return_amount_arr['numbers'],
            'promotion_winnings'     => $promotion_winnings_arr['money'],
            'promotion_winnings_cnt' => $promotion_winnings_arr['numbers'],
            'turn_card_winnings'     => $turn_card_winnings_arr['money'],
            'turn_card_winnings_cnt' => $turn_card_winnings_arr['numbers'],

            'manual_deduction_amount' => $manual_deduction_amount_arr['money'],
            'manual_deduction_cnt'    => $manual_deduction_amount_arr['numbers'],

            'back_amount' => $agency_amount_arr['money'],
            'back_cnt'    => $agency_amount_arr['numbers'],

            'game_code_amount' => $wash_code_arr['money'],
            'game_code_cnt'    => $wash_code_arr['numbers'],

            'income_amount' => $income_amount,
            'out_amount'    => $out_amount,

            'total_Winnings'     => sprintf("%01.2f", ($coupon_amount_arr['money'] + $promotion_winnings_arr['money'] + $turn_card_winnings_arr['money'])),
            'total_Winnings_cnt' => $coupon_amount_arr['numbers'] + $promotion_winnings_arr['numbers'] + $turn_card_winnings_arr['numbers'],
            'shares_settle_cnt'          => $shares_arr->cnt,
            'shares_settle_amount'       => $shares_arr->amount,
            'profit_loss_cnt'            => $profit_loss->cnt,
            'profit_loss_amount'         => $profit_loss->amount,
            'user_num'                   => \Model\User::getUserNum(),            //总注册人数
            'new_user_num'               => $new_user_num,                        //新增注册人数
            'deposit_user_num'           => $depositNum->deposit_user_num,        //总充值人数
            'new_deposit_user_num'       => $depositNum->new_deposit_user_num,    //首充人数
            'deposit_amount'             => $depositNum->offline_amount + $depositNum->online_amount + $depositNum->manual_deposit_amount,   //总充值金额
            'new_register_deposit_amount'=> sprintf("%01.2f", $regNum->new_register_deposit_amount), //新增充值金额
            'new_register_avg'           => $regNum->new_register_deposit_num == 0 ? 0: bcdiv($regNum->new_register_deposit_amount,$regNum->new_register_deposit_num,2),  //新增人均充值金额
            'confiscate_amount'          => $confiscate_amount_arr['money'],      //没收资金
            'confiscate_cnt'             => $confiscate_amount_arr['numbers'],    //没收资金人数
            'new_valid_agent_num'        => $res['new_valid_agent_num'],          //新增有效代理数
            'agent_first_deposit_num'    => $res['agent_first_deposit_num'],      //每日代理带来的新充会员数
        ];

        //优惠彩金去重人数
        $coupon_user        = DB::connection('slave')->table('funds_deal_log')->whereIn('deal_type', [105, 114])->where('created', '>=', $date_start . ' 00:00:00')->where('created', '<=', $date_end . ' 23:59:59')->selectRaw('COUNT(DISTINCT(user_id)) AS `num`')->get()->toArray();
        $data['coupon_num'] = $coupon_user[0]->num;
        //晋升彩金去重人数
        $promotion_user        = DB::connection('slave')->table('funds_deal_log')->where('deal_type', 308)->where('created', '>=', $date_start . ' 00:00:00')->where('created', '<=', $date_end . ' 23:59:59')->selectRaw('COUNT(DISTINCT(user_id)) AS `num`')->get()->toArray();
        $data['promotion_num'] = $promotion_user[0]->num;
        //转卡彩金去重人数
        $turn_card_user        = DB::connection('slave')->table('funds_deal_log')->where('deal_type', 309)->where('created', '>=', $date_start . ' 00:00:00')->where('created', '<=', $date_end . ' 23:59:59')->selectRaw('COUNT(DISTINCT(user_id)) AS `num`')->get()->toArray();
        $data['turn_card_num'] = $turn_card_user[0]->num;
        return $this->lang->set(0, [], $data);
    }

    //查询
    public function getFoundsDepsitInfo($date_start, $date_end, $deal_category, $deal_type) {
        if(!is_array($deal_type)){
            $deal_type=[$deal_type];
        }
        $info = DB::connection('slave')->table('funds_deal_log')->selectRaw("COALESCE(SUM(deal_money), 0) as money, COUNT(*) as numbers")->where('created', '>=', $date_start)->where('created', '<=', $date_end)->where('deal_category', '=', $deal_category)->whereIn('deal_type',  $deal_type)->first();

        $return = get_object_vars($info);

        if($return['money'] > 0)
            $return['money'] = $return['money'] / 100;
        return $return;
    }
};
