<?php

namespace Logic\GameApi\Order;

/**
 * WMATCH电子
 */
class WMATCH extends AbsOrder
{

    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_wmatch';
    /**
     * 类ID
     * @var int
     */
    public $game_id = 140;

    /**
     * 游戏类型
     * @var string
     */
    public $game_type = 'WMATCH';

    /**
     * 游戏类型组
     * @var string
     */
    public $game_types = "'WMATCH', 'WMATCHBY', 'WMATCHQP', 'WMATCHTAB'";

    public function OrderRepair()
    {
        $sdate = date('Y-m-d', strtotime("-1 day"));
        $edate = $sdate . ' 23:59:59';

        //游戏类型
        $types = [
            140 => ['game' => 'GAME', 'type' => 'WMATCH'],
            141 => ['game' => 'BY', 'type' => 'WMATCHBY'],
            142 => ['game' => 'QP', 'type' => 'WMATCHQP'],
            143 => ['game' => 'TABLE', 'type' => 'WMATCHTAB'],
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
                'game' => $types[$val['gameTypeId']]['game'],
                'order_number' => $val['roundId'],
                'game_type' => $types[$val['game_id']]['type'],
                'type_name' => $this->lang->text($types[$val['gameType']]['type']),
                'game_id' => $val['game_id'],
                'server_id' => 0,
                'account' => $val['externalUserId'],
                'bet' => $val['totalBetAmount'],
                'profit' => $val['totalWinAmount'] - $val['totalBetAmount'],
                'date' => $val['roundEndTime'],
            ];
            \Utils\MQServer::send(\Logic\GameApi\GameApi::$synchronousOrderCallback, $tmp);
        }
        echo count($data) . PHP_EOL;
    }

    public function queryUserSumOrder($sdate, $edate, $gameCode)
    {
        $query = \DB::table($this->order_table)
            ->where('roundEndTime', '>=', $sdate)
            ->where('roundEndTime', '<=', $edate)
            ->where('gameIdentify', $gameCode)
            ->groupBy('user_id');
        $res = $query->get([
            \DB::raw('user_id'),
            \DB::raw('sum(totalBetAmount) bet'),//下注金额
            \DB::raw('sum(totalBetAmount) valid_bet'),//有效投注金额
            \DB::raw('sum(totalWinAmount) win_loss'),//派彩金额
        ])->toArray();

        return $res;
    }

}

