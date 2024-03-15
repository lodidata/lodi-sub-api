<?php
namespace Logic\GameApi\Order;

/**
 * SGMK 新霸电子
 */

class SGMK extends AbsOrder {

    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_sgmk';
    /**
     * 类ID
     * @var int
     */
    public $game_id = 81;

    /**
     * 游戏类型
     * @var string
     */
    public $game_type = 'SGMK';

    /**
     * 游戏类型组
     * @var string
     */
    public $game_types = "'SGMK', 'SGMKBY', 'SGMKTAB', 'SGMKJJ'";


    public function OrderRepair(){
        $sdate = date('Y-m-d',strtotime("-1 day"));
        $edate = $sdate . ' 23:59:59';

        //游戏类型
        $types = [
            'SM' => ['game' => 'GAME', 'type' => 'SGMK', 'id' => 81],
            'AD' => ['game' => 'ARCADE', 'type' => 'SGMKJJ', 'id' => 82],
            'BC' => ['game' => 'TABLE', 'type' => 'SGMKTAB', 'id' => 83],
            'FH' => ['game' => 'BY', 'type' => 'SGMKBY', 'id' => 84],
        ];

        $sql = " SELECT user_id,categoryId,ticketId,acctId,betAmount,winLoss,ticketTime FROM {$this->order_table}
WHERE ticketTime >= '{$sdate}' AND ticketTime <= '{$edate}' 
AND ticketId NOT IN
( SELECT order_number FROM orders 
WHERE orders.game_type IN ({$this->game_types}) AND orders.date = '{$sdate}') ;";
        echo $sql;echo PHP_EOL;
        $data = \DB::select($sql);
        foreach ($data as $val){
            $val = (array)$val;
            $tmp = [
                'user_id' => $val['user_id'],
                'game' => $types[$val['categoryId']]['game'],
                'order_number' => $val['ticketId'],
                'game_type' => $types[$val['categoryId']]['type'],
                'type_name' => $this->lang->text($types[$val['categoryId']]['type']),
                'game_id' => $types[$val['categoryId']]['id'],
                'server_id' => 0,
                'account' => $val['acctId'],
                'bet' => bcmul($val['betAmount'],100,0),
                'profit' => bcmul($val['winLoss'],100,0),
                'date' => $val['ticketTime'],
            ];
            \Utils\MQServer::send(\Logic\GameApi\GameApi::$synchronousOrderCallback, $tmp);
        }
        echo count($data).PHP_EOL;
    }

    public function queryUserSumOrder($sdate, $edate, $gameCode)
    {
        $query = \DB::table($this->order_table)
            ->where('gameCode', $gameCode)
            ->where('ticketTime', '>=', $sdate)
            ->where('ticketTime', '<=', $edate)
            ->groupBy('user_id');

        $res = (array)$query->get([
            \DB::raw('user_id'),
            \DB::raw('sum(betAmount)*100 bet'),//下注金额
            \DB::raw('sum(betAmount)*100 valid_bet'),//有效投注金额
            \DB::raw('sum(winLoss)*100 win_loss'),//盈亏金额
        ])->toArray();

        return $res;
    }

}

