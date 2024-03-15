<?php
/**
 * PB体育订单报表
 */

namespace Logic\GameApi\Order;


class PB extends AbsOrder
{
    /**
     * 分类ID
     * @var int
     */
    public $game_id = 114;
    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_pb';

    //将拉取的第三方订单表与oders同步
    public function OrderRepair()
    {
        $sdate = date('Y-m-d', strtotime("-1 day"));
//        $sdate = '2019-04-23';
        $edate = $sdate . ' 23:59:59';

        $sql = "SELECT user_id,wagerId,loginId,stake,profit,settleDateFm FROM {$this->order_table}
WHERE settleDateFm >= '{$sdate}' AND settleDateFm <= '{$edate}' 
AND wagerId NOT IN
( SELECT order_number FROM orders 
WHERE orders.game_type =  'PB' AND orders.date = '{$sdate}')";
        $data = \DB::select($sql);
        echo $sql . PHP_EOL;
        foreach ($data as $val) {
            $val = (array)$val;
            $tmp = [
                'user_id' => $val['user_id'],
                'game' => 'SPORT',
                'order_number' => $val['wagerId'],
                'game_type' => 'PB',
                'type_name' => $this->lang->text('PB'),
                'game_id' => $this->game_id,
                'server_id' => 0,
                'account' => $val['loginId'],
                'bet' => bcmul($val['stake'], 100, 0),
                'profit' => bcmul($val['profit'], 100, 0),
                'date' => $val['settleDateFm'],
            ];
            \Utils\MQServer::send(\Logic\GameApi\GameApi::$synchronousOrderCallback, $tmp);
        }
        echo count($data) . PHP_EOL;
    }

    public function queryUserSumOrder($sdate, $edate, $gameCode)
    {
        $query = \DB::table($this->order_table)
            ->where('createdTime', '>=', $sdate)
            ->where('createdTime', '<=', $edate)
            ->groupBy('user_id');
        $res = $query->get([
            \DB::raw('user_id'),
            \DB::raw('sum(stake)*100 bet'),//下注金额
            \DB::raw('sum(stake)*100 valid_bet'),//有效投注金额
            \DB::raw('sum(profit)*100 win_loss'),//派彩金额
        ])->toArray();

        return $res;
    }
}

