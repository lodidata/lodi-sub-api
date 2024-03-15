<?php
namespace Logic\GameApi\Order;

/**
 * JDB电子
 * Class JDB
 * @package Logic\GameApi\Order
 */
class JDB extends AbsOrder {

    public $game_id = 70;
    public $order_table = "game_order_jdb_dz";
    public $game_type = 'JDB';
    public $game_parent_type = 'GAME';

    public function OrderRepair(){
        $sdate = date('Y-m-d',strtotime("-1 day"));
        $edate = $sdate . ' 23:59:59';

        $sql = " SELECT user_id,seqNo,mtype,playerId,bet,total,gameDate FROM {$this->order_table}
WHERE gameDate >= '{$sdate}' AND gameDate <= '{$edate}' 
AND seqNo NOT IN
( SELECT order_number FROM orders 
WHERE orders.game_type = '".$this->game_type."' AND orders.date = '{$sdate}') ;";

        echo $sql;echo PHP_EOL;
        $data = \DB::select($sql);

        $res = [];
        foreach ($data as $value){
            $value = (array)$value;
            $tmp = [
                'user_id' => $value['user_id'],
                'game' => $this->game_parent_type,
                'order_number' => $value['seqNo'],
                'game_type' => $this->game_type,
                'type_name' => $this->lang->text($this->game_type),
                'game_id' => $this->game_id,
                'server_id' => $value['mtype'],
                'account' => $value['playerId'],
                'bet' => -$value['bet'],
                'profit' => $value['total'],
                'date' => $value['gameDate'],
            ];
            \Utils\MQServer::send(\Logic\GameApi\GameApi::$synchronousOrderCallback, $tmp);
        }
        echo count($data);
    }

    public function queryUserSumOrder($sdate,$edate, $gameCode){
        $startTime = strtotime($sdate);
        $endTime = strtotime($edate);
        $stime = date('Y-m-d', $startTime);
        $etime = date('Y-m-d H:i:s', $endTime);
        $query = \DB::table($this->order_table)
            ->where('gameName',$gameCode)
            ->where('gameDate','>=',$stime)
            ->where('gameDate','<=',$etime)
            ->groupBy('user_id');

        $res = (array)$query->get([
            \DB::raw('user_id'),
            \DB::raw('IFNULL(sum(bet),0) bet'),//下注金额
            \DB::raw('IFNULL(sum(bet),0) valid_bet'),//有效投注金额
            \DB::raw('IFNULL(sum(total),0) win_loss'),//派彩金额
        ])->toArray();
        return $res ;
    }

    public function getUserPrefix(){
        $site_type = $website = $this->ci->get('settings')['website']['site_type'];
        if($site_type == 'ncg'){
            return $this->ci->get('settings')['app']['tid'].'n';
        }else{
            return $this->ci->get('settings')['app']['tid'].'o';
        }

    }
}

