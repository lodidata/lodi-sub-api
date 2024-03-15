<?php

namespace Logic\GameApi\Order;

/**
 * NG
 */
class NG extends AbsOrder
{
    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_ng';
    /**
     * 类ID
     * @var int
     */
    public $game_id = 137;

    /**
     * 游戏类型
     * @var string
     */
    public $game_type = 'NG';

    /**
     * 游戏类型组
     * @var string
     */
    public $game_types = "'NG'";

    public function OrderRepair()
    {
        $sdate = date('Y-m-d', strtotime("-1 day"));
        $edate = $sdate . ' 23:59:59';

        $sql = " SELECT user_id,roundId,amount,earn,created FROM {$this->order_table}
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
                'game' => 'GAME',
                'order_number' => $val['roundId'],
                'game_type' => 'NG',
                'type_name' => $this->lang->text('NG'),
                'game_id' => 137,
                'server_id' => 0,
                'account' => $val['playerNativeId'],
                'bet' => $val['amount'],
                'profit' => $val['earn'] - $val['amount'],
                'date' => $val['created']
            ];
            \Utils\MQServer::send(\Logic\GameApi\GameApi::$synchronousOrderCallback, $tmp);
        }
        echo count($data) . PHP_EOL;
    }

    public function queryUserSumOrder($sdate, $edate, $gameCode)
    {
        $query = \DB::table($this->order_table)
            ->where('created', '>=', $sdate)
            ->where('created', '<=', $edate)
            ->where('gameCode', $gameCode)
            ->groupBy('user_id');
        $res = $query->get([
            \DB::raw('user_id'),
            \DB::raw('sum(amount) bet'),//下注金额
            \DB::raw('sum(amount) valid_bet'),//有效投注金额
            \DB::raw('sum(earn) win_loss'),//派彩金额
        ])->toArray();

        return $res;
    }

}

