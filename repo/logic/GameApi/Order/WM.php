<?php
/**
 * DG视讯
 */

namespace Logic\GameApi\Order;


class WM extends AbsOrder
{
    /**
     * 分类ID
     * @var int
     */
    public $game_id = 104;
    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_wm';

    //将拉取的第三方订单表与oders同步
    public function OrderRepair()
    {
        $sdate = date('Y-m-d', strtotime("-1 day"));
        //        $sdate = '2019-04-23';
        $edate = $sdate . ' 23:59:59';

        $sql = "SELECT user_id,order_number,`user`,bet,winLoss as profit,betTime FROM {$this->order_table}
WHERE betTime >= '{$sdate}' AND betTime <= '{$edate}' 
AND order_number NOT IN
( SELECT order_number FROM orders 
WHERE orders.game_type =  'WM' AND orders.date = '{$sdate}')";
        $data = \DB::select($sql);
        echo $sql . PHP_EOL;
        foreach ($data as $val) {
            $val = (array)$val;
            $tmp = [
                'user_id' => $val['user_id'],
                'game' => 'LIVE',
                'order_number' => $val['order_number'],
                'game_type' => 'WM',
                'type_name' => $this->lang->text('WM'),
                'game_id' => 104,
                'server_id' => 0,
                'account' => $val['user'],
                'bet' => bcmul($val['bet'], 100, 0),
                'profit' => bcmul($val['profit'], 100, 0),
                'date' => $val['betTime'],
            ];
            \Utils\MQServer::send(\Logic\GameApi\GameApi::$synchronousOrderCallback, $tmp);
        }
        echo count($data) . PHP_EOL;
    }

    public function queryUserSumOrder($sdate, $edate,$gamecode)
    {
        $query = \DB::table($this->order_table)
                    ->where('betTime', '>=', $sdate)
                    ->where('betTime', '<=', $edate)
                    ->where('gid', $gamecode)
                    ->groupBy('user_id');

        $res = (array)$query->get([
            \DB::raw('IFNULL(sum(bet),0)*100 bet'),
            \DB::raw('IFNULL(sum(validbet),0)*100 valid_bet'),
            \DB::raw('IFNULL(sum(winLoss),0)*100 win_loss'),
        ])->toArray();
        return $res;
    }

}

