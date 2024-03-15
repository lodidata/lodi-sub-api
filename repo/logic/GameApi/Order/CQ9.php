<?php

namespace Logic\GameApi\Order;

/**
 * CQ9电子
 * Class CQ9
 * @package Logic\GameApi\Order
 */
class CQ9 extends AbsOrder {
    public $game_id = 74;
    public $order_table = "game_order_cqnine_dz";
    public $game_type = 'CQ9';
    public $game_parent_type = 'GAME';

    public function OrderRepair(){
        $sdate = date('Y-m-d',strtotime("-1 day"));
        $edate = $sdate . ' 23:59:59';

        $sql = " SELECT user_id,round,bet,win,createtime FROM {$this->order_table}
WHERE createtime >= '{$sdate}' AND createtime <= '{$edate}' 
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
                'game' => $this->game_parent_type,
                'order_number' => $value['round'],
                'game_type' => $this->game_type,
                'type_name' => $this->lang->text($this->game_type),
                'game_id' => $this->game_id,
                'bet' => $value['bet'],
                'profit' => $value['win'] - $value['bet'],
                'date' => $value['createtime'],
            ];
            \Utils\MQServer::send(\Logic\GameApi\GameApi::$synchronousOrderCallback, $tmp);
        }
        echo count($data);
    }


    public function queryUserSumOrder($sdate,$edate,$gameCode){
        $query = \DB::table($this->order_table)
            ->where('gamecode',$gameCode)
            ->where('createtime','>=',$sdate)
            ->where('createtime','<=',$edate)
            ->groupBy('user_id');
            $res = (array)$query->get([
                \DB::raw('user_id'),
                \DB::raw('IFNULL(sum(bet),0) bet'),//下注金额
                \DB::raw('sum(bet) valid_bet'),//有效投注金额
                \DB::raw('IFNULL(sum(win - bet),0) win_loss'),//派彩金额
            ])->toArray();

        return $res;
    }

}