<?php
/**
 * SBO体育订单报表
 */
namespace Logic\GameApi\Order;

class SBO extends AbsOrder {
    /**
     * 分类ID
     * @var int
     */
    public $game_id = 72;
    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_sbo';

    public $game_type = 'SBO';

    //将拉取的第三方订单表与oders同步
    public function OrderRepair(){
        $sdate = date('Y-m-d', strtotime("-1 day"));
//        $sdate = '2019-04-23';
        $edate = $sdate . ' 23:59:59';

        $sql = "SELECT user_id,refNo,username,stake,winlost,orderTime FROM {$this->order_table}
WHERE orderTime >= '{$sdate}' AND orderTime <= '{$edate}' 
AND refNo NOT IN
( SELECT order_number FROM orders 
WHERE orders.game_type =  '{$this->game_type}' AND orders.date = '{$sdate}')";
        $data = \DB::select($sql);
        echo $sql.PHP_EOL;
        foreach ($data as $value){
            $value = (array)$value;
            $tmp = [
                'user_id' => $value['user_id'],//用户id
                'game' => 'SPORT',//体育
                'order_number' => $value['refNo'],
                'game_type' => $this->game_type,
                'type_name' => $this->lang->text($this->game_type),
                'game_id' => $this->game_id,
                'server_id' => 0,
                'account' => $value['username'],
                'bet' => bcmul($value['stake'],100,0),
                'profit' => bcmul($value['winlost'],100,0),
                'date' => $value['orderTime'],//北京时间
            ];
            \Utils\MQServer::send(\Logic\GameApi\GameApi::$synchronousOrderCallback, $tmp);
        }
        echo count($data).PHP_EOL;
    }

    /**
     * 获取用户游戏统计
     * @param $sdate
     * @param $edate
     * @param $gameCode
     * @return array
     */
    public function queryUserSumOrder($sdate, $edate, $gameCode)
    {
        $query = \DB::table($this->order_table)
            ->where('gameDate', '>=', $sdate)
            ->where('gameDate', '<=', $edate)
            ->groupBy('user_id');

        $res = (array)$query->get([
            \DB::raw('user_id'),
            \DB::raw('sum(stake)*100 bet'),//下注金额
            \DB::raw('sum(stake)*100 valid_bet'),//有效投注金额
            \DB::raw('sum(winlost)*100 win_loss'),//盈亏金额
        ])->toArray();

        return $res;
    }
}

