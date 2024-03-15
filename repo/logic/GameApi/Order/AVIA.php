<?php
/**
 * 泛亚电竞
 */

namespace Logic\GameApi\Order;

class AVIA extends AbsOrder
{
    /**
     * 分类ID
     * @var int
     */
    public $game_id = 96;
    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_avia';


    //将拉取的第三方订单表与oders同步
    public function OrderRepair()
    {
        $sdate = date('Y-m-d', strtotime("-1 day"));
//        $sdate = '2019-04-23';
        $edate = $sdate . ' 23:59:59';

        $sql = "SELECT user_id,OrderID,UserName,BetAmount,Money,RewardAt FROM {$this->order_table}
WHERE RewardAt >= '{$sdate}' AND RewardAt <= '{$edate}' 
AND OrderID NOT IN
( SELECT order_number FROM orders 
WHERE orders.game_type =  'AVIA' AND orders.date = '{$sdate}')";
        $data = \DB::select($sql);
        echo $sql . PHP_EOL;
        foreach ($data as $val) {
            $val = (array)$val;
            $tmp = [
                'user_id' => $val['user_id'],
                'game' => 'ESPORTS',
                'order_number' => $val['OrderID'],
                'game_type' => 'AVIA',
                'type_name' => $this->lang->text('AVIA'),
                'game_id' => 95,
                'server_id' => 0,
                'account' => $val['UserName'],
                'bet' => bcmul($val['BetAmount'], 100, 0),
                'profit' => bcmul($val['Money'], 100, 0),
                'date' => $val['RewardAt'],
            ];
            \Utils\MQServer::send(\Logic\GameApi\GameApi::$synchronousOrderCallback, $tmp);
        }
        echo count($data) . PHP_EOL;
    }

    public function queryUserSumOrder($sdate, $edate, $gameCode)
    {
        $query = \DB::table($this->order_table)
            ->where('RewardAt', '>=', $sdate)
            ->where('RewardAt', '<=', $edate)
            ->groupBy('user_id');
        $res = $query->get([
            \DB::raw('user_id'),
            \DB::raw('sum(BetAmount) bet'),//下注金额
            \DB::raw('sum(BetAmount) valid_bet'),//有效投注金额
            \DB::raw('sum(Money) win_loss'),//派彩金额
        ])->toArray();

        return $res;
    }
}

