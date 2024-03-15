<?php
namespace Logic\GameApi\Order;


class BSG extends AbsOrder
{
    /**
     * 分类ID
     * @var int
     */
    public $game_id = 124;

    public $game_type = 'BSG';

    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_bsg';

    /**
     * 补单
     */
    public function OrderRepair()
    {
        $sdate = date('Y-m-d', strtotime("-1 day"));
        $edate = $sdate . ' 23:59:59';

        $sql = "SELECT user_id,order_number,username,bet_amount,income,bettime FROM {$this->order_table}
WHERE bettime >= '{$sdate}' AND bettime <= '{$edate}' 
AND order_number NOT IN
( SELECT order_number FROM orders 
WHERE orders.game_type =  '{$this->game_type}' AND orders.date = '{$sdate}')";
        $data = \DB::select($sql);
        echo $sql . PHP_EOL;
        foreach ($data as $val) {
            $val = (array)$val;
            $tmp = [
                'user_id' => $val['user_id'],
                'game' => 'GAME',
                'order_number' => $val['order_number'],
                'game_type' => $this->game_type,
                'type_name' => $this->lang->text($this->game_type),
                'game_id' => $this->game_id,
                'server_id' => 0,
                'account' => $val['username'],
                'bet' => $val['bet_amount'],
                'profit' => $val['income'],
                'date' => $val['bettime'],
            ];
            \Utils\MQServer::send(\Logic\GameApi\GameApi::$synchronousOrderCallback, $tmp);
        }
        echo count($data) . PHP_EOL;
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
            ->where('bettime', '>=', $sdate)
            ->where('bettime', '<=', $edate)
            ->where('game_id', $gameCode)
            ->groupBy('user_id');
        $res = $query->get([
            \DB::raw('user_id'),
            \DB::raw('sum(bet_amount) bet'),//下注金额
            \DB::raw('sum(bet_amount) valid_bet'),//有效投注金额
            \DB::raw('sum(income) win_loss'),//派彩金额
        ])->toArray();

        return $res;
    }
}

