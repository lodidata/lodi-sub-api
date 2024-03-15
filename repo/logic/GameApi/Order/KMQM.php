<?php

namespace Logic\GameApi\Order;

/**
 * KMQM棋牌
 * Class KMQM
 * @package Logic\GameApi\Order
 */
class KMQM extends AbsOrder {

    public $game_id = 74;
    public $order_table = 'game_order_kmqm';
    public $game_type = 'KMQM';

    public function OrderRepair(){
        $sdate = date('Y-m-d',strtotime("-1 day"));
        $edate = $sdate . ' 23:59:59';

        $sql = " SELECT user_id,ugsbetid,riskamt,winloss,betupdatedon FROM {$this->order_table}
WHERE betupdatedon >= '{$sdate}' AND betupdatedon <= '{$edate}' 
AND ugsbetid NOT IN
( SELECT order_number FROM orders 
WHERE orders.game_type = '{$this->game_type}' AND orders.date = '{$sdate}') ;";

        echo $sql;echo PHP_EOL;
        $data = \DB::select($sql);

        $res = [];
        foreach ($data as $value){
            $value = (array)$value;
            $tmp = [
                'user_id'   => $value['user_id'],
                'game'      => 'QP',
                'order_number' => $value['ugsbetid'],
                'game_type' => $this->game_type,
                'type_name' => $this->lang->text($this->game_type),
                'game_id'   => $this->game_id,
                'bet'       => bcmul($value['riskamt'],100,0),
                'profit'    => bcmul($value['winloss'],100,0),
                'date'      => $value['betupdatedon'],
            ];
            \Utils\MQServer::send(\Logic\GameApi\GameApi::$synchronousOrderCallback, $tmp);
        }
        echo count($data);
    }

    public function queryUserSumOrder($sdate,$edate, $gameCode){

        $query = \DB::table($this->order_table)
            ->where('gameid',$gameCode)
            ->where('betupdatedon','>=',$sdate)
            ->where('betupdatedon','<=',$edate)
            ->groupBy('user_id');

        $res = (array)$query->get([
            \DB::raw('user_id'),
            \DB::raw('sum(riskamt)*100 bet'),//下注金额
            \DB::raw('sum(riskamt)*100 valid_bet'),//有效投注金额
            \DB::raw('sum(winloss)*100 win_loss'),//盈亏金额
            ])->toArray();
        return $res;
    }

}