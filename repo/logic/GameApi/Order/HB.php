<?php

namespace Logic\GameApi\Order;


/**
 * HB电子
 */
class HB extends AbsOrder
{

    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_hb';
    /**
     * 类ID
     * @var int
     */
    public $game_id = 128;

    /**
     * 游戏类型
     * @var string
     */
    public $game_type = 'HB';

    /**
     * 游戏类型组
     * @var string
     */
    public $game_types = "'HB', 'HBTAB', 'HBQP'";

    public function OrderRepair()
    {
        $sdate = date('Y-m-d', strtotime("-1 day"));
        $edate = $sdate . ' 23:59:59';

        //游戏类型
        $types = [
            11 => ['id' => 128, 'game' => 'GAME', 'type' => 'HB'],
            8 => ['id' => 129, 'game' => 'TABLE', 'type' => 'HBTAB'],
            6 => ['id' => 130, 'game' => 'QP', 'type' => 'HBQP'],
        ];

        $sql = " SELECT user_id,Username,GameTypeId,GameInstanceId,Stake,Payout,DtCompleted FROM {$this->order_table}
WHERE DtCompleted >= '{$sdate}' AND DtCompleted <= '{$edate}' 
AND GameInstanceId NOT IN
( SELECT order_number FROM orders 
WHERE orders.game_type IN ({$this->game_types}) AND orders.date = '{$sdate}') ;";
        echo $sql;
        echo PHP_EOL;
        $data = \DB::select($sql);
        foreach ($data as $val) {
            $val = (array)$val;
            $tmp = [
                'user_id' => $val['user_id'],
                'game' => $types[$val['GameTypeId']]['game'],
                'order_number' => $val['GameInstanceId'],
                'game_type' => $types[$val['GameTypeId']]['type'],
                'type_name' => $this->lang->text($types[$val['GameTypeId']]['type']),
                'game_id' => $types[$val['GameTypeId']]['id'],
                'server_id' => 0,
                'account' => $val['Username'],
                'bet' => $val['Stake'],
                'profit' => $val['Payout'] - $val['Stake'],
                'date' => $val['DtCompleted'],
            ];
            \Utils\MQServer::send(\Logic\GameApi\GameApi::$synchronousOrderCallback, $tmp);
        }
        echo count($data) . PHP_EOL;
    }

    public function queryUserSumOrder($sdate, $edate, $gameCode)
    {
        $query = \DB::table($this->order_table)
            ->where('DtCompleted', '>=', $sdate)
            ->where('DtCompleted', '<=', $edate)
            ->where('GameKeyName', $gameCode)
            ->groupBy('user_id');
        $res = $query->get([
            \DB::raw('user_id'),
            \DB::raw('sum(Stake) bet'),//下注金额
            \DB::raw('sum(Stake) valid_bet'),//有效投注金额
            \DB::raw('sum(Payout) win_loss'),//派彩金额
        ])->toArray();

        return $res;
    }

}

