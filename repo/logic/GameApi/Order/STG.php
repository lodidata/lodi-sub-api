<?php
/**
 * 东亚体育订单报表
 */

namespace Logic\GameApi\Order;


class STG extends AbsOrder
{
    /**
     * 分类ID
     * @var int
     */
    public $game_id = 115;
    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_stg';

    public $game_type = 'STG';

    //将拉取的第三方订单表与oders同步
    public function OrderRepair()
    {
        $sdate = date('Y-m-d', strtotime("-1 day"));
//        $sdate = '2019-04-23';
        $edate = $sdate . ' 23:59:59';

        $sql = "SELECT user_id,OrderNumber,ClientID,Amount,profit,DateUpdated FROM {$this->order_table}
WHERE DateUpdated >= '{$sdate}' AND DateUpdated <= '{$edate}' 
AND OrderNumber NOT IN
( SELECT order_number FROM orders 
WHERE orders.game_type =  '{$this->game_type}' AND orders.date = '{$sdate}')";
        $data = \DB::select($sql);
        echo $sql . PHP_EOL;
        foreach ($data as $val) {
            $val = (array)$val;
            $tmp = [
                'user_id' => $val['user_id'],
                'game' => 'SPORT',
                'order_number' => $val['OrderNumber'],
                'game_type' => $this->game_type,
                'type_name' => $this->lang->text($this->game_type),
                'game_id' => $this->game_id,
                'server_id' => 0,
                'account' => $val['ClientID'],
                'bet' => bcmul($val['Amount'], 100, 0),
                'profit' => bcmul($val['profit'], 100, 0),
                'date' => $val['DateUpdated'],
            ];
            \Utils\MQServer::send(\Logic\GameApi\GameApi::$synchronousOrderCallback, $tmp);
        }
        echo count($data) . PHP_EOL;
    }


    public function queryUserSumOrder($sdate, $edate,$gamecode)
    {
        $query = \DB::table($this->order_table)
            ->where('DateUpdated', '>=', $sdate)
            ->where('DateUpdated', '<=', $edate)
            ->groupBy('user_id');

        $res = (array)$query->get([
            \DB::raw('user_id'),
            \DB::raw('sum(Amount)*100 bet'),//下注金额
            \DB::raw('sum(Amount)*100 valid_bet'),//有效投注金额
            \DB::raw('sum(profit)*100 win_loss'),//盈亏金额
        ])->toArray();
        return $res;
    }

}

