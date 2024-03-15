<?php
namespace Logic\GameApi\Order;

/**
 * BNG电子
 * Class BNG
 * @package Logic\GameApi\Order
 */
class BNG extends AbsOrder {
    public $game_id = 99;
    public $order_table = "game_order_bng";
    public $game_type = 'BNG';

    public function OrderRepair(){
        $sdate = date('Y-m-d',strtotime("-1 day"));
        $edate = $sdate . ' 23:59:59';

        $sql = " SELECT user_id,round,betAmount,winAmount,gameDate FROM {$this->order_table}
WHERE gameDate >= '{$sdate}' AND gameDate <= '{$edate}' 
AND round NOT IN
( SELECT order_number FROM orders 
WHERE orders.game_type = '".$this->game_type."' AND orders.date = '{$sdate}') ;";

        echo $sql;echo PHP_EOL;
        $data = \DB::select($sql);

        $res = [];
        foreach ($data as $value){
            $value = (array)$value;
            $tmp = [
                'user_id' => $value['user_id'],
                'game' => 'GAME',
                'order_number' => $value['round'],
                'game_type' => $this->game_type,
                'type_name' => $this->lang->text($this->game_type),
                'game_id' => $this->game_id,
                'bet' => $value['betAmount'],
                'profit' => $value['winAmount'] - $value['betAmount'],
                'date' => $value['gameDate'],
            ];
            \Utils\MQServer::send(\Logic\GameApi\GameApi::$synchronousOrderCallback, $tmp);
        }
        echo count($data);
    }

    public function queryUserSumOrder($sdate, $edate, $gameCode)
    {
        $query = \DB::table($this->order_table)
                    ->where('startTime', '>=', $sdate)
                    ->where('startTime', '<=', $edate)
                    ->where('game_id', $gameCode)
                    ->groupBy('user_id');
        $res = $query->get([
            \DB::raw('user_id'),
            \DB::raw('sum(betAmount) bet'),//下注金额
            \DB::raw('sum(income) win_loss'),//盈亏金额
        ])->toArray();

        return $res;
    }

}