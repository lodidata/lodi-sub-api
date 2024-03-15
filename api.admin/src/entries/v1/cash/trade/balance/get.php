<?php
/**
 * vegas2.0
 *
 * @auth *^-^*<dm>
 * @copyright XLZ CO.
 * @package
 * @date 2017/4/12 21:14
 */

use Logic\Admin\BaseController;

return new  class() extends BaseController {
//    const STATE       = \API::DRAFT;
    const TITLE = '出入款汇总';
    const DESCRIPTION = '';
    
    const QUERY = [
        'type'        => 'int #1 会员，2 代理',
        'member_name' => 'string #用户或代理的名称',
        'day_begin'   => 'datetime(required) #开始日期',
        'day_end'     => 'datetime(required) #结束日期',
    ];
    
    const PARAMS = [];
    const SCHEMAS = [];
    private $reportDB;

    public function run() {
        $this->reportDB = \DB::connection('slave');
        $params = $this->request->getParams();

        if (isset($params['user'])) {
            $params['member_name'] = $params['user'];
        }

        $params = [
            'start_time'  => isset($params['day_begin']) ? ($params['day_begin']) : date('Y-m-d'),
            'end_time'    => isset($params['day_end']) ? ($params['day_end']) : date('Y-m-d'),
            'user_type'   => isset($params['type']) ? $params['type'] : 3,
            'member_name' => isset($params['member_name']) ? $params['member_name'] : '',
        ];

        /**
         * TODO
         * 表 rpt_funds_outAndIncome_day 数据由数据库定时任务获取插入
         * 代理返佣暂时没有写入到该表中，所以使用子查询获取数据
         * 后续需要把代理返佣数据也通过定时任务写入到该表，直接查询
         */

        $sql = "SELECT 
                    SUM(offline_income_money) AS offline_income_money,
                    SUM(offline_income_count) AS offline_income_count,
                    SUM(online_income_money) AS online_income_money,
                    SUM(online_income_count) AS online_income_count,
                    SUM(manual_income_money) AS manual_income_money,
                    SUM(manual_income_count) AS manual_income_count,
                    SUM(back_money_sum) AS back_money,
                    SUM(back_money_count) AS back_count,
                    SUM(poundage_money) AS poundage_money,
                    SUM(poundage_count) AS poundage_count,
                    SUM(income_amount) AS income_amount,
                    SUM(winprize_out_money) AS  winprize_out_money,
                    SUM(winprize_out_count) AS winprize_out_count,
                    SUM(favorable_out_money) AS favorable_out_money,
                    SUM(favorable_out_count) AS favorable_out_count,
                    SUM(return_out_money) AS return_out_money,
                    SUM(return_out_count) AS return_out_count,
                    SUM(manual_out_money) AS manual_out_money,
                    SUM(manual_out_count) AS manual_out_count,
                    SUM(out_amount) AS out_amount,
                    SUM(reg_count) AS reg_count,
                    SUM(new_charge_money) AS new_charge_money,
                    SUM(new_charge_count) AS new_charge_count,
                    SUM(login_count) AS login_count,
                    SUM(play_user_count) as play_user_count,
                    SUM(order_count) as order_count,
                    SUM(turn_card_winnings) as turn_card_winnings,
                    SUM(promotion_winnings) as promotion_winnings,
                    SUM(turn_card_winnings_count) as turn_card_winnings_count,
                    SUM(promotion_winnings_count) as promotion_winnings_count
                FROM
                    rpt_funds_outAndIncome_day
                WHERE
                    count_date >= '{$params['start_time']}'
                AND
                    count_date <= '{$params['end_time']}'";

        $data = (array)$this->reportDB->select($sql);

        foreach ($data as &$val) {
            foreach ($val as &$v) {
                $v = $v ?? 0;
            }
        }

        //月俸禄
        $monthly = $this->reportDB->table('user_monthly_award')->whereBetween(DB::raw('DATE_ADD(award_date, INTERVAL 1 MONTH)'), [$params['start_time'], $params['end_time']])->first([DB::raw('SUM(award_money) as monthly_award_money'), DB::raw('COUNT(id) as monthly_award_money_count')]);
        $data[0]->monthly_award_money = $monthly->monthly_award_money/100 ?? 0;
        $data[0]->monthly_award_money_count = $monthly->monthly_award_money_count ?? 0;

        return $data;
    }
};
