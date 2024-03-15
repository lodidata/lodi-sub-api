<?php
namespace Logic\GameApi\Order;

/**
 * FC电子
 */

class FC extends AbsOrder {

    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_fc';
    /**
     * 类ID
     * @var int
     */
    public $game_id = 93;

    /**
     * 游戏类型
     * @var string
     */
    public $game_type = 'FC';

    /**
     * 游戏类型组
     * @var string
     */
    public $game_types = "'FC', 'FCBY'";


    public function OrderRepair(){
        $sdate = date('Y-m-d',strtotime("-1 day"));
        $edate = $sdate . ' 23:59:59';

        //游戏类型
        $types = [
            2 => ['id' => 93, 'game' => 'GAME', 'type' => 'FC'],
            1 => ['id' => 94, 'game' => 'BY', 'type' => 'FCBY'],
            7 => ['id' => 93, 'game' => 'GAME', 'type' => 'FC'],
        ];

        $sql = " SELECT user_id,account,gametype,recordID,bet,prize,bdate,jppoints FROM {$this->order_table}
WHERE bdate >= '{$sdate}' AND bdate <= '{$edate}' 
AND recordID NOT IN
( SELECT recordID FROM orders 
WHERE orders.game_type IN ({$this->game_types}) AND orders.date = '{$sdate}') ;";
        echo $sql;echo PHP_EOL;
        $data = \DB::select($sql);
        foreach ($data as $val){
            $val = (array)$val;
            $tmp = [
                'user_id' => $val['user_id'],
                'game' => $types[$val['gametype']]['game'],
                'recordID' => $val['recordID'],
                'game_type' => $types[$val['gametype']]['type'],
                'type_name' => $this->lang->text($types[$val['gametype']]['type']),
                'game_id' => $types[$val['gametype']]['id'],
                'server_id' => 0,
                'account' => $val['account'],
                'bet' => bcmul($val['bet'],100,0),
                'profit' => bcmul($val['prize']-$val['bet']+$val['jppoints'], 100, 0),
                'date' => $val['bdate'],
            ];
            \Utils\MQServer::send(\Logic\GameApi\GameApi::$synchronousOrderCallback, $tmp);
        }
        echo count($data).PHP_EOL;
    }


    public function queryUserSumOrder($sdate,$edate, $gameCode){
        $query = \DB::table($this->order_table)
            ->where('bdate','>=',$sdate)
            ->where('bdate','<=',$edate)
            ->where('recordID',$gameCode)
            ->groupBy('user_id');

        $res = (array)$query->get([
            \DB::raw('user_id'),
            \DB::raw('sum(bet) bet'),//下注金额
            \DB::raw('sum(bet) valid_bet'),//有效投注金额
            \DB::raw('sum(winlose) win_loss'),//派彩金额
        ])->toArray();

        return $res;
    }

}

