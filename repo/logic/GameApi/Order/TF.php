<?php

namespace Logic\GameApi\Order;


/**
 * TF雷火电竞
 */
class TF extends AbsOrder
{

    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_tf';
    /**
     * 类ID
     * @var int
     */
    public $game_id = 78;

    /**
     * 游戏类型
     * @var string
     */
    public $game_type = 'TF';


    public function OrderRepair()
    {
        $sdate = date('Y-m-d', strtotime("-1 day"));
        $edate = $sdate . ' 23:59:59';

        $sql = " SELECT user_id,order_id,amount,earnings,settlement_datetime FROM {$this->order_table}
WHERE date_created >= '{$sdate}' AND date_created <= '{$edate}' 
AND order_id NOT IN
( SELECT order_number FROM orders 
WHERE orders.game_type = '{$this->game_type}' AND orders.date = '{$sdate}') ;";
        echo $sql;
        echo PHP_EOL;
        $data = \DB::select($sql);
        foreach ($data as $value) {
            $value = (array)$value;
            $tmp = [
                'user_id' => $value['user_id'],
                'game' => 'ESPORTS',
                'order_number' => $value['order_id'],
                'game_type' => $this->game_type,
                'type_name' => $this->lang->text($this->game_type),
                'game_id' => $this->game_id,
                'bet' => bcmul($value['amount'],100,0),
                'profit' => bcmul($value['earnings'],100,0),
                'date' => $value['settlement_datetime'],
            ];
            \Utils\MQServer::send(\Logic\GameApi\GameApi::$synchronousOrderCallback, $tmp);
        }
        echo count($data) . PHP_EOL;
    }


    public function queryUserSumOrder($sdate, $edate,$gamecode)
    {
        $query = \DB::table($this->order_table)
            ->where('date_created', '>=', $sdate)
            ->where('date_created', '<=', $edate);
        $res = (array)$query->get([
            \DB::raw('user_id'),
            \DB::raw('sum(amount)*100 bet'),//下注金额
            \DB::raw('sum(amount)*100 valid_bet'),//有效投注金额
            \DB::raw('sum(earnings)*100 win_loss'),//派彩金额
        ])->first();

        return $res;
    }

}

