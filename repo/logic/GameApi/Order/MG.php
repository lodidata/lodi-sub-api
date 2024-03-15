<?php

namespace Logic\GameApi\Order;

/**
 * MG电子
 */
class MG extends AbsOrder
{

    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_mg';
    /**
     * 类ID
     * @var int
     */
    public $game_id = 118;

    /**
     * 游戏类型
     * @var string
     */
    public $game_type = 'MG';

    /**
     * 游戏类型组
     * @var string
     */
    public $game_types = "'MG', 'MGQP', 'MGJJ', 'MGLIVE', 'MGBY'";

    public function OrderRepair()
    {
        $sdate = date('Y-m-d', strtotime("-1 day"));
        $edate = $sdate . ' 23:59:59';

        //游戏类型
        $types = [
            118 => ['game' => 'GAME', 'type' => 'MG'],
            119 => ['game' => 'QP', 'type' => 'MGQP'],
            121 => ['game' => 'ARCADE', 'type' => 'MGJJ'],
            122 => ['game' => 'LIVE', 'type' => 'MGLIVE'],
            123 => ['game' => 'BY', 'type' => 'MGBY'],
        ];

        $sql = " SELECT user_id,playerId,game_id,betUID,betAmount,payoutAmount,createdTime FROM {$this->order_table}
WHERE createdTime >= '{$sdate}' AND createdTime <= '{$edate}' 
AND betUID NOT IN
( SELECT order_number FROM orders 
WHERE orders.game_type IN ({$this->game_types}) AND orders.date = '{$sdate}') ;";
        echo $sql;
        echo PHP_EOL;
        $data = \DB::select($sql);
        foreach ($data as $val) {
            $val = (array)$val;
            $tmp = [
                'user_id' => $val['user_id'],
                'game' => $types[$val['game_id']]['game'],
                'order_number' => $val['betUID'],
                'game_type' => $types[$val['game_id']]['type'],
                'type_name' => $this->lang->text($types[$val['gameType']]['type']),
                'game_id' => $val['game_id'],
                'server_id' => 0,
                'account' => $val['playerId'],
                'bet' => $val['betAmount'],
                'profit' => $val['payoutAmount'] - $val['betAmount'],
                'date' => $val['createdTime'],
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
            ->where('gameCode', $gameCode)
            ->groupBy('user_id');
        $res = $query->get([
            \DB::raw('user_id'),
            \DB::raw('sum(betAmount) bet'),//下注金额
            \DB::raw('sum(betAmount) valid_bet'),//有效投注金额
            \DB::raw('sum(payoutAmount) win_loss'),//派彩金额
        ])->toArray();

        return $res;
    }

}

