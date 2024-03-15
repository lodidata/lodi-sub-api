<?php
/**
 * BTI体育订单报表
 */

namespace Logic\GameApi\Order;


class BTI extends AbsOrder
{
    /**
     * 分类ID
     * @var int
     */
    public $game_id = 135;
    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_bti';

    //将拉取的第三方订单表与oders同步
    public function OrderRepair()
    {
        $sdate = date('Y-m-d', strtotime("-1 day"));
//        $sdate = '2019-04-23';
        $edate = $sdate . ' 23:59:59';

        $sql = "SELECT user_id,PurchaseID,MerchantCustomerID,ValidStake,ReturnAmount,CreationDate FROM {$this->order_table}
WHERE CreationDate >= '{$sdate}' AND CreationDate <= '{$edate}' 
AND PurchaseID NOT IN
( SELECT order_number FROM orders 
WHERE orders.game_type =  'BTI' AND orders.date = '{$sdate}')";
        $data = \DB::select($sql);
        echo $sql . PHP_EOL;
        foreach ($data as $val) {
            $val = (array)$val;
            $tmp = [
                'user_id' => $val['user_id'],
                'game' => 'SPORT',
                'order_number' => $val['PurchaseID'],
                'game_type' => 'BTI',
                'type_name' => $this->lang->text('BTI'),
                'game_id' => $this->game_id,
                'server_id' => 0,
                'account' => $val['MerchantCustomerID'],
                'bet' => bcmul($val['ValidStake'], 100, 0),
                'profit' => bcmul($val['ReturnAmount'], 100, 0),
                'date' => $val['CreationDate'],
            ];
            \Utils\MQServer::send(\Logic\GameApi\GameApi::$synchronousOrderCallback, $tmp);
        }
        echo count($data) . PHP_EOL;
    }

    public function queryUserSumOrder($sdate, $edate, $gameCode)
    {
        $query = \DB::table($this->order_table)
            ->where('CreationDate', '>=', $sdate)
            ->where('CreationDate', '<=', $edate)
            ->groupBy('user_id');
        $res = $query->get([
            \DB::raw('user_id'),
            \DB::raw('sum(TotalStake)*100 bet'),//下注金额
            \DB::raw('sum(ValidStake)*100 valid_bet'),//有效投注金额
            \DB::raw('sum(ReturnAmount)*100 win_loss'),//派彩金额
        ])->toArray();

        return $res;
    }
}

