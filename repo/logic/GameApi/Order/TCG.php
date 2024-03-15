<?php

namespace Logic\GameApi\Order;


/**
 * TCG彩票
 */
class TCG extends AbsOrder
{

    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_tcg';
    /**
     * 类ID
     * @var int
     */
    public $game_id = 144;

    /**
     * 游戏类型
     * @var string
     */
    public $game_type = 'TCG';


    public function OrderRepair()
    {
        $sdate = date('Y-m-d', strtotime("-1 day"));
        $edate = $sdate . ' 23:59:59';

        $sql = " SELECT user_id,orderNum,betAmount,netPNL,settlementTime FROM {$this->order_table}
WHERE settlementTime >= '{$sdate}' AND settlementTime <= '{$edate}' 
AND orderNum NOT IN
( SELECT order_number FROM orders 
WHERE orders.game_type = '{$this->game_type}' AND orders.date = '{$sdate}') ;";
        echo $sql;
        echo PHP_EOL;
        $data = \DB::select($sql);
        foreach ($data as $value) {
            $value = (array)$value;
            $tmp = [
                'user_id' => $value['user_id'],
                'game' => 'TCP',
                'order_number' => $value['orderNum'],
                'game_type' => $this->game_type,
                'type_name' => $this->game_type,
                'game_id' => $this->game_id,
                'bet' => bcmul($value['betAmount'],100,0),
                'profit' => bcmul($value['netPNL'],100,0),
                'date' => $value['settlementTime'],
            ];
            \Utils\MQServer::send(\Logic\GameApi\GameApi::$synchronousOrderCallback, $tmp);
        }
        echo count($data) . PHP_EOL;
    }


    public function queryUserSumOrder($sdate, $edate,$gamecode)
    {
        $query = \DB::table($this->order_table)
            ->where('settlementTime', '>=', $sdate)
            ->where('settlementTime', '<=', $edate);
        $res = (array)$query->get([
            \DB::raw('user_id'),
            \DB::raw('sum(betAmount)*100 bet'),//下注金额
            \DB::raw('sum(actualBetAmount)*100 valid_bet'),//有效投注金额
            \DB::raw('sum(netPNL)*100 win_loss'),//派彩金额
        ])->first();

        return $res;
    }

}

