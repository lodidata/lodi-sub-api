<?php

namespace Logic\GameApi\Order;

/**
 * CG电子
 */
class CG extends AbsOrder
{
    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_cg';
    /**
     * 类ID
     * @var int
     */
    public $game_id = 91;

    /**
     * 游戏类型
     * @var string
     */
    public $game_type = 'CG';


    public function OrderRepair()
    {
        $sdate = date('Y-m-d', strtotime("-1 day"));
        $edate = $sdate . ' 23:59:59';

        $platformTypes = [
            'slot' => ['id' => 91, 'game' => 'GAME', 'type' => 'CG'],
            'pvp' => ['id' => 92, 'game' => 'QP', 'type' => 'CGQP'],
        ];

        $sql = "SELECT user_id,SerialNumber,GameType,LogTime,BetMoney,MoneyWin,JackpotMoney FROM {$this->order_table} 
WHERE LogTime >= '{$sdate}' AND LogTime <= '{$edate}' 
AND SerialNumber NOT IN
( SELECT order_number FROM orders 
WHERE orders.game_type IN ('CG', 'CGQP') AND orders.date = '{$sdate}') ;";
        echo $sql;
        echo PHP_EOL;
        $data = \DB::select($sql);
        foreach ($data as $val) {
            $val = (array)$val;
            $tmp = [
                'user_id' => $val['user_id'],
                'game' => $platformTypes[$val['gameCategoryType']]['game'],
                'order_number' => $val['SerialNumber'],
                'game_type' => $platformTypes[$val['gameCategoryType']]['type'],
                'type_name' => $this->lang->text($platformTypes[$val['gameCategoryType']]['type']),
                'game_id' => $platformTypes[$val['gameCategoryType']]['id'],
                'server_id' => 0,
                'account' => $val['ThirdPartyAccount'],
                'bet' => bcmul($val['BetMoney'], 100, 0),
                'profit' => bcmul($val['MoneyWin'] - $val['BetMoney']+$val['JackpotMoney'], 100, 0),
                'date' => $val['LogTime'],
            ];
            \Utils\MQServer::send(\Logic\GameApi\GameApi::$synchronousOrderCallback, $tmp);
        }
        echo count($data) . PHP_EOL;
    }

    public function queryUserSumOrder($sdate, $edate, $gameCode)
    {
        $query = \DB::table($this->order_table)
            ->where('LogTime', '>=', $sdate)
            ->where('LogTime', '<=', $edate)
            ->where('GameType', $gameCode)
            ->groupBy('user_id');
        
        $res = (array)$query->get([
            \DB::raw('user_id'),
            \DB::raw('sum(BetMoney)*100 bet'),//下注金额
            \DB::raw('sum(MoneyWin-BetMoney+JackpotMoney)*100 win_loss'),//盈亏金额
        ])->toArray();

        return $res;
    }

}

