<?php
/**
 * UG体育订单报表
 */

namespace Logic\GameApi\Order;


class UG extends AbsOrder
{
    /**
     * 分类ID
     * @var int
     */
    public $game_id = 95;
    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_ug';

    public $game_type = 'UG';

    //将拉取的第三方订单表与oders同步
    public function OrderRepair()
    {
        $sdate = date('Y-m-d', strtotime("-1 day"));
//        $sdate = '2019-04-23';
        $edate = $sdate . ' 23:59:59';

        $sql = "SELECT user_id,BetID,Account,BetAmount,Win,BetDate FROM {$this->order_table}
WHERE BetDate >= '{$sdate}' AND BetDate <= '{$edate}' 
AND BetID NOT IN
( SELECT order_number FROM orders 
WHERE orders.game_type =  '{$this->game_type}' AND orders.date = '{$sdate}')";
        $data = \DB::select($sql);
        echo $sql . PHP_EOL;
        foreach ($data as $val) {
            $val = (array)$val;
            $tmp = [
                'user_id' => $val['user_id'],
                'game' => 'SPORT',
                'order_number' => $val['BetID'],
                'game_type' => $this->game_type,
                'type_name' => $this->lang->text($this->game_type),
                'game_id' => $this->game_id,
                'server_id' => 0,
                'account' => $val['Account'],
                'bet' => bcmul($val['BetAmount'], 100, 0),
                'profit' => bcmul($val['Win'], 100, 0),
                'date' => $val['BetDate'],
            ];
            \Utils\MQServer::send(\Logic\GameApi\GameApi::$synchronousOrderCallback, $tmp);
        }
        echo count($data) . PHP_EOL;
    }

    public function queryUserSumOrder($sdate, $edate,$gamecode)
    {
        $query = \DB::table($this->order_table)
            ->where('BetDate', '>=', $sdate)
            ->where('BetDate', '<=', $edate)
            ->where('GameID', '=', $gamecode)
            ->groupBy('user_id');

        $res = (array)$query->get([
            \DB::raw('IFNULL(sum(BetAmount),0)*100 bet'),
            \DB::raw('IFNULL(sum(Turnover),0)*100 valid_bet'),
            \DB::raw('IFNULL(sum(Win),0)*100 win_loss'),
        ])->toArray();
        return $res;
    }

}

