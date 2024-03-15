<?php

use Utils\Www\Action;

return new class extends Action
{
    const HIDDEN = true;
    const TITLE = '用户投注统计';
    const TAGS = "会员统计";
    const QUERY = [
    ];
    const SCHEMAS = [
        'data' => [
            'first_deposit'   => '首充会员数',
            'deposit_num'     => '存款笔数',
            'deposit_money'   => '存款金额',
            'get_money'       => '取款金额',
            'bet_num'         => '下注笔数',
            'valid_bet_money' => '下注金额',
            'win_prize'       => '中奖金额',
            'return_money'    => '返水金额',
            'coupon_money'    => '活动彩金',
            'lose_earn'       => '盈亏',
        ],
    ];

    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $db = \DB::connection('default');

        $user_id = $this->auth->getUserId();

        $sql = <<<SQL
SELECT 
  SUM(`first_deposit`) AS `first_deposit`,
  SUM(`deposit_num`) AS `deposit_num`,
  SUM(`deposit_money`) AS `deposit_money`,
  SUM(`get_money`) AS `get_money`,
  SUM(`bet_num`) AS `bet_num`,
  SUM(`valid_bet_money`) AS `valid_bet_money`,
  SUM(`win_prize`) AS `win_prize`,
  SUM(`return_money`) AS `return_money`,
  SUM(`coupon_money`) AS `coupon_money`,
  SUM(`lose_earn`) AS `lose_earn`
FROM
  `rpt_userdata`
WHERE
  `user_id` = '{$user_id}'
SQL;

        $report = $db->selectOne($sql);

        return $this->lang->set(0, [], $report);
    }
};