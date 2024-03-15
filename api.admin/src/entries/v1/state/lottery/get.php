<?php
/**
 * vegas2.0
 * 玩家统计
 *
 * @auth *^-^*<dm>
 * @copyright XLZ CO.
 * @package
 * @date 2017/4/13 16:44
 */

use Logic\Admin\BaseController;

return new class() extends BaseController {

    const TITLE = '玩家统计';

    const DESCRIPTION = '获取指定条件内的玩家统计信息';

    

    const QUERY = [
        'user_name' => 'string()  #用户名',
        //        'rebet_money_from'   => 'int()    #投注金额',
        //        'rebet_money_to'     => 'int()    #投注金额',
        //        'deposit_money_from' => 'int()    #存款金额',
        //        'deposit_money_to'   => 'int()    #存款金额',
        //        'level'              => 'int() #会员等级',
        //        'flag'               => 'enum[0,1](required) #游戏标识，默认为0。如果返回值有标记值则取返回的值',
        //        'type'               => 'enum[user,agent](required) # user 会员，agent 代理',
        //        'games'              => 'array(required) #游戏,[{"game_id":"-1","game_type":"lottery"}]，参见接口 admin.las.me/games?debug=1',
        'sort'      => 'string() #排序字段 betNumber, betAmount, yk, winAmount',
        'sortType'  => 'string() #排序规则 asc, desc',
        'date_from' => 'date()   #日期',
        'date_to'   => 'date()   #日期',
        'page'      => 'int()   #页码',
        'page_size' => 'int()    #每页大小',
    ];

    

    const PARAMS = [];

    const SCHEMAS = [
            [
                'type'  => 'enum[rowset, row, dataset]',
                'size'  => 'unsigned',
                'total' => 'unsigned',
                'data'  => 'rows[user_name:string,ranting:int,rebet_money:int,coupon_money:int,deposit_money:int,withdraw_money:int,bet_times:int,bet_money:int,valid_bet:int,lose_earn:int,send_prize:int,balance:int,contri:int,bonus:int]',
            ]
    ];

    private $reportDB;

    public function run() {
        $this->reportDB =  \DB::connection('default');
        $date_from = $this->request->getParam('date_from') ?: date('Y-m-d');
        $date_to = $this->request->getParam('date_to') ?: date('Y-m-d');
        $sort = $this->request->getParam('order_by') ?: 'yk';
        $sortType = $this->request->getParam('order_rule') ?: 'desc';

        $sql = <<<SQL
SELECT
  b.`name` name,
  SUM(bet_num) betNumber,
  SUM(bet_money) / 100 betAmount,
  SUM(send_money) / 100 winAmount,
  -SUM(total_earnlose) / 100 yk
FROM
  rpt_lottery_earnlose a
JOIN lottery b ON a.lottery_id=b.id
WHERE
  count_date >= '{$date_from}'
AND
  count_date <= '{$date_to}'
GROUP BY lottery_id
ORDER BY {$sort} {$sortType}
SQL;

        $data = $this->reportDB->select($sql);

        $data = array_map(function ($record) {
            $record->betAmount = round($record->betAmount, 2);
            $record->winAmount = round($record->winAmount, 2);
            $record->yk = round($record->yk, 2);

            return $record;
        }, $data);

        return $data;
    }
};
