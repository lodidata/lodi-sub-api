<?php
/**
 * DG视讯
 */

namespace Logic\GameApi\Order;

class DG extends AbsOrder
{
    /**
     * 分类ID
     * @var int
     */
    public $game_id = 103;
    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_dg';

    //将拉取的第三方订单表与oders同步
    public function OrderRepair()
    {
        $sdate = date('Y-m-d', strtotime("-1 day"));
//        $sdate = '2019-04-23';
        $edate = $sdate . ' 23:59:59';

        $sql = "SELECT user_id,order_number,userName,betPoints,profit,betTime FROM {$this->order_table}
WHERE betTime >= '{$sdate}' AND betTime <= '{$edate}' 
AND order_number NOT IN
( SELECT order_number FROM orders 
WHERE orders.game_type =  'DG' AND orders.date = '{$sdate}')";
        $data = \DB::select($sql);
        echo $sql . PHP_EOL;
        foreach ($data as $val) {
            $val = (array)$val;
            $tmp = [
                'user_id' => $val['user_id'],
                'game' => 'LIVE',
                'order_number' => $val['order_number'],
                'game_type' => 'DG',
                'type_name' => $this->lang->text('DG'),
                'game_id' => 103,
                'server_id' => 0,
                'account' => $val['userName'],
                'bet' => bcmul($val['betPoints'], 100, 0),
                'profit' => bcmul($val['profit'], 100, 0),
                'date' => $val['betTime'],
            ];
            \Utils\MQServer::send(\Logic\GameApi\GameApi::$synchronousOrderCallback, $tmp);
        }
        echo count($data) . PHP_EOL;
    }

    public function queryUserSumOrder($sdate,$edate,$gameCode){
        $query = \DB::table($this->order_table)
            ->where('GameId',$gameCode)
            ->where('betTime','>=',$sdate)
            ->where('betTime','<=',$edate)
            ->groupBy('user_id');
        $res = (array)$query->get([
            \DB::raw('user_id'),
            \DB::raw('IFNULL(sum(betPoints),0)*100 bet'),//下注金额
            \DB::raw('sum(betPoints)*100 valid_bet'),//有效投注金额
            \DB::raw('IFNULL(sum(profit),0)*100 win_loss'),//派彩金额
        ])->toArray();

        return $res;
    }
}

