<?php
namespace Logic\GameApi\Order;


/**
 * YGG电子
 * Class YGG
 * @package Logic\GameApi\Order
 */
class YGG extends AbsOrder {
    public $game_id = 108;
    public $order_table = "game_order_ygg";
    public $game_type = 'YGG';
    public $game_parent_type = 'GAME';

    public function OrderRepair(){
        $sdate = date('Y-m-d',strtotime("-1 day"));
        $edate = $sdate . ' 23:59:59';

        $sql = " SELECT user_id,reference,amount,profit,prize,createTime FROM {$this->order_table}
WHERE createTime >= '{$sdate}' AND createTime <= '{$edate}' 
AND reference NOT IN
( SELECT order_number FROM orders 
WHERE orders.game_type = '".$this->game_type."' AND orders.date = '{$sdate}') ;";

        echo $sql;echo PHP_EOL;
        $data = \DB::select($sql);

        $res = [];
        foreach ($data as $value){
            $value = (array)$value;
            $tmp = [
                'user_id' => $value['user_id'],
                'game' => $this->game_parent_type,
                'order_number' => $value['reference'],
                'game_type' => $this->game_type,
                'type_name' => $this->lang->text($this->game_type),
                'game_id' => $this->game_id,
                'bet' => $value['amount'],
                'profit' => $value['profit'],
                'date' => $value['createTime'],
            ];
            \Utils\MQServer::send(\Logic\GameApi\GameApi::$synchronousOrderCallback, $tmp);
        }
        echo count($data);
    }

    public function queryUserSumOrder($sdate, $edate, $gameCode)
    {
        $query = \DB::table($this->order_table)
                    ->where('createTime', '>=', $sdate)
                    ->where('createTime', '<=', $edate)
                    ->where('DCGameID', $gameCode)
                    ->groupBy('user_id');
        $res = $query->get([
            \DB::raw('user_id'),
            \DB::raw('sum(amount) bet'),//下注金额
            \DB::raw('sum(profit) win_loss'),//盈亏金额
        ])->toArray();

        return $res;
    }

}