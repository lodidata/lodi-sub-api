<?php
namespace Logic\GameApi\Order;

/**
 * AWC游戏聚合平台
 */

class AWC extends AbsOrder {

    /**
     * 订单表
     * @var string
     */
    public $order_table = '';
    /**
     * 类ID
     * @var int
     */
    public $game_id = 0;

    /**
     * 游戏类型
     * @var string
     */
    public $game_type = '';

    /**
     * 游戏类型组
     * @var string
     */
    public $game_types = "";

    public $gameBigType = 'LIVE';


    public function OrderRepair(){
        $sdate = date('Y-m-d',strtotime("-1 day"));
        $edate = $sdate . ' 23:59:59';

        $sql = "SELECT user_id,platformTxId,gameCode,betTime,betAmount,winAmount,gameType FROM {$this->order_table} 
WHERE betTime >= '{$sdate}' AND betTime <= '{$edate}' 
AND platformTxId NOT IN
( SELECT order_number FROM orders 
WHERE orders.game_type IN ({$this->game_types}) AND orders.date = '{$sdate}') ;";
        echo $sql;echo PHP_EOL;
        $data = \DB::select($sql);
        foreach ($data as $value){
            $value = (array)$value;

            if(isset($platformTypes) && $platformTypes){
                $tmp = [
                    'user_id'       => $value['user_id'],
                    'game'          => $platformTypes[$value['gameType']]['game'],
                    'order_number'  => $value['platformTxId'],
                    'game_type'     => $platformTypes[$value['gameType']]['type'],
                    'type_name'     => $this->lang->text($platformTypes[$value['gameType']]['type']),
                    'game_id'       =>  $platformTypes[$value['gameType']]['id'],
                    'account'       => $value['userId'],
                    'bet'           => bcmul($value['betAmount'],100,0),
                    'profit'        => bcmul($value['winAmount'] - $value['betAmount'],100,0),
                    'date'          => $value['betTime'],
                ];
            }else{
                $tmp = [
                    'user_id'       => $value['user_id'],
                    'game'          => $this->gameBigType,
                    'order_number'  => $value['platformTxId'],
                    'game_type'     => $this->game_type,
                    'type_name'     => $this->lang->text($this->game_type),
                    'game_id'       => $this->game_id,
                    'account'       => $value['userId'],
                    'bet'           => bcmul($value['betAmount'],100,0),
                    'profit'        => bcmul($value['winAmount'] - $value['betAmount'],100,0),
                    'date'          => $value['betTime'],
                ];
            }


            \Utils\MQServer::send(\Logic\GameApi\GameApi::$synchronousOrderCallback, $tmp);
        }
        echo count($data).PHP_EOL;
    }


    public function queryUserSumOrder($sdate,$edate,$gameCode){
        $query = \DB::table($this->order_table)
            ->where('betTime','>=',$sdate)
            ->where('betTime','<=',$edate)
            ->where('gameCode', $gameCode)
            ->groupBy('user_id');
        $res = (array)$query->get([
            \DB::raw('user_id'),
            \DB::raw('sum(betAmount) bet'),//下注金额
            \DB::raw('sum(turnover) valid_bet'),//有效投注金额
            \DB::raw('sum(winAmount - betAmount) win_loss'),//派彩金额
        ])->toArray();

        return $res;
    }

}

