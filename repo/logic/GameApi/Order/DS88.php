<?php
/**
 * DS88斗鸡
 */

namespace Logic\GameApi\Order;

class DS88 extends AbsOrder
{
    /**
     * 分类ID
     * @var int
     */
    public $game_id = 100;
    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_ds88';

    //将拉取的第三方订单表与oders同步
    public function OrderRepair()
    {
        $sdate = date('Y-m-d', strtotime("-1 day"));
        $edate = $sdate . ' 23:59:59';

        $sql = "SELECT user_id,slug,account,bet_amount,net_income,settled_at FROM {$this->order_table}
WHERE settled_at >= '{$sdate}' AND settled_at <= '{$edate}' 
AND slug NOT IN
( SELECT order_number FROM orders 
WHERE orders.game_type =  'DS88' AND orders.date = '{$sdate}')";
        $data = \DB::select($sql);
        echo $sql . PHP_EOL;
        foreach ($data as $val) {
            $val = (array)$val;
            $tmp = [
                'user_id' => $val['user_id'],
                'game' => 'SABONG',
                'order_number' => $val['slug'],
                'game_type' => 'DS88',
                'type_name' => $this->lang->text('DS88'),
                'game_id' => $this->game_id,
                'server_id' => 0,
                'account' => $val['account'],
                'bet' => bcmul($val['bet_amount'], 100, 0),
                'profit' => bcmul($val['net_income'], 100, 0),
                'date' => $val['settled_at'],
            ];
            \Utils\MQServer::send(\Logic\GameApi\GameApi::$synchronousOrderCallback, $tmp);
        }
        echo count($data) . PHP_EOL;
    }


    public function queryUserSumOrder($sdate,$edate,$gameCode){
        $query = \DB::table($this->order_table)
            ->where('settled_at','>=',$sdate)
            ->where('settled_at','<=',$edate)
            ->groupBy('user_id');
        $res = (array)$query->get([
            \DB::raw('user_id'),
            \DB::raw('IFNULL(sum(bet_amount),0)*100 bet'),//下注金额
            \DB::raw('sum(bet_amount)*100 valid_bet'),//有效投注金额
            \DB::raw('IFNULL(sum(net_income),0)*100 win_loss'),//派彩金额
        ])->toArray();

        return $res;
    }
}

