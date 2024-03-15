<?php

namespace Logic\GameApi\Order;

/**
 * AT电子
 */
class AT extends AbsOrder
{

    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_at';
    /**
     * 类ID
     * @var int
     */
    public $game_id = 88;

    /**
     * 游戏类型
     * @var string
     */
    public $game_type = 'AT';

    /**
     * 游戏类型组
     * @var string
     */
    public $game_types = "'AT', 'ATBY', 'ATJJ'";

    /**
     * 补单
     */
    public function OrderRepair()
    {
        $sdate = date('Y-m-d', strtotime("-1 day"));
        $edate = $sdate . ' 23:59:59';

        //游戏类型
        $types = [
            'slot' => ['id' => 88, 'game' => 'GAME', 'type' => 'AT'],
            'fish' => ['id' => 89, 'game' => 'BY', 'type' => 'ATBY'],
            'arcade' => ['id' => 90, 'game' => 'ARCADE', 'type' => 'ATJJ'],
        ];

        $sql = " SELECT user_id,player,gameType,order_number,bet,win,createdAt FROM {$this->order_table}
WHERE createdAt >= '{$sdate}' AND createdAt <= '{$edate}' 
AND order_number NOT IN
( SELECT order_number FROM orders 
WHERE orders.game_type IN ({$this->game_types}) AND orders.date = '{$sdate}') ;";
        echo $sql;
        echo PHP_EOL;
        $data = \DB::select($sql);
        foreach ($data as $val) {
            $val = (array)$val;
            $tmp = [
                'user_id' => $val['user_id'],
                'game' => $types[$val['gameType']]['game'],
                'order_number' => $val['order_number'],
                'game_type' => $types[$val['gameType']]['type'],
                'type_name' => $this->lang->text($types[$val['gameType']]['type']),
                'game_id' => $types[$val['gameType']]['id'],
                'server_id' => 0,
                'account' => $val['player'],
                'bet' => $val['bet'],
                'profit' => $val['win'],
                'date' => $val['createdAt'],
            ];
            \Utils\MQServer::send(\Logic\GameApi\GameApi::$synchronousOrderCallback, $tmp);
        }
        echo count($data) . PHP_EOL;
    }

    public function queryUserSumOrder($sdate, $edate, $gameCode)
    {
        $query = \DB::table($this->order_table)
            ->where('createdAt', '>=', $sdate)
            ->where('createdAt', '<=', $edate)
            ->where('productId', $gameCode)
            ->groupBy('user_id');
        $res = $query->get([
            \DB::raw('user_id'),
            \DB::raw('sum(bet) bet'),//下注金额
            \DB::raw('sum(validBet) valid_bet'),//有效投注金额
            \DB::raw('sum(result) win_loss'),//派彩金额
        ])->toArray();

        return $res;
    }

}

