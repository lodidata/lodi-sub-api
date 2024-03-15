<?php

namespace Logic\GameApi\Order;


/**
 * QT
 */
class QT extends AbsOrder
{
    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_qt';
    /**
     * 类ID
     * @var int
     */
    public $game_id = 132;

    /**
     * 游戏类型
     * @var string
     */
    public $game_type = 'QT';

    /**
     * 游戏类型组
     * @var string
     */
    public $game_types = "'QT', 'QTTAB'";

    public function OrderRepair()
    {
        $sdate = date('Y-m-d', strtotime("-1 day"));
        $edate = $sdate . ' 23:59:59';

        //游戏类型
        $types = [
            'TABLE' => ['id' => 131, 'game' => 'TABLE', 'type' => 'QTTAB'],
            'SLOT' => ['id' => 132, 'game' => 'GAME', 'type' => 'QT']
        ];

        $sql = " SELECT user_id,gameCategory,round_id,playerId,totalBet,totalPayout,completed FROM {$this->order_table}
WHERE completed >= '{$sdate}' AND completed <= '{$edate}' 
AND round_id NOT IN
( SELECT order_number FROM orders 
WHERE orders.game_type IN ({$this->game_types}) AND orders.date = '{$sdate}') ;";
        echo $sql;
        echo PHP_EOL;
        $data = \DB::select($sql);
        foreach ($data as $val) {
            $val = (array)$val;

            $tmp = [
                'user_id' => $val['user_id'],
                'game' => $types[$val['gameCategory']]['game'],
                'order_number' => $val['round_id'],
                'game_type' => $types[$val['gameCategory']]['type'],
                'type_name' => $this->lang->text($types[$val['gameCategory']]['type']),
                'game_id' => $types[$val['gameCategory']]['id'],
                'server_id' => 0,
                'account' => $val['playerId'],
                'bet' => $val['totalBet'],
                'profit' => $val['totalPayout'] - $val['totalBet'],
                'date' => $val['completed']
            ];
            \Utils\MQServer::send(\Logic\GameApi\GameApi::$synchronousOrderCallback, $tmp);
        }
        echo count($data) . PHP_EOL;
    }

    public function queryUserSumOrder($sdate, $edate, $gameCode)
    {
        $query = \DB::table($this->order_table)
            ->where('completed', '>=', $sdate)
            ->where('completed', '<=', $edate)
            ->where('gameId', $gameCode)
            ->groupBy('user_id');
        $res = $query->get([
            \DB::raw('user_id'),
            \DB::raw('sum(totalBet) bet'),//下注金额
            \DB::raw('sum(totalBet) valid_bet'),//有效投注金额
            \DB::raw('sum(totalPayout) win_loss'),//派彩金额
        ])->toArray();

        return $res;
    }

}

