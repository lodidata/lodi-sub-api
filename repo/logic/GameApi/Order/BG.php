<?php
/**
 * Created by PhpStorm.
 * User: 95684
 * Date: 2019/5/25
 * Time: 14:02
 */

namespace Logic\GameApi\Order;

use Logic\Logic;

/**
 * BG LIVE
 * Class BG
 * @package Logic\GameApi\Order
 */
class BG extends Logic {
    public $game_id = 112;
    public $order_table = "game_order_bg";
    public $game_type = 'BG';
    public $game_parent_type = 'LIVE';
    public function getOrderGroupUser($sdate,$edate,$page = 1,$size = 20){
        $query = \DB::table($this->order_table.' as cq')
            ->leftJoin('user','cq.user_id','user.id')
            ->where('cq.orderTime','>=',$sdate)
            ->where('cq.orderTime','<=',$edate)
            ->groupBy('cq.user_id');
        $total = clone $query;
        $data = $query->forPage($page,$size)->get([
            'cq.user_id',
            'user.name AS user_name',
            \DB::raw('count(1) bet_count'),
            \DB::raw('sum(cq.bAmount) bet'),
            \DB::raw('sum(cq.validBet) valid_bet'),
            \DB::raw('sum(cq.aAmount) win_loss'),
            \DB::raw('sum('.$this->order_table.'.payment) send_prize'),
        ])->toArray();
        if ($data) {
            foreach ($data as &$val) {
                $val = (array)$val;
                $val['bet'] = bcmul($val['bet'], 100, 0);
                $val['valid_bet'] = bcmul($val['valid_bet'], 100, 0);
                $val['win_loss'] = bcmul($val['win_loss'], 100, 0);
                $val['send_prize'] = bcmul($val['send_prize'], 100, 0);
            }
            unset($val);
        }
        $attr = [
            'total' => $total->pluck('cq.user_id')->count(),
            'number' => $page,
            'size' => $size,
        ];
        return ['attr'=>$attr,'data'=>$data];
    }

    public function OrderRepair(){
        $sdate = date('Y-m-d',strtotime("-1 day"));
        $edate = $sdate . ' 23:59:59';

        $sql = " SELECT user_id,orderId,bAmount,payment,orderTime FROM {$this->order_table}
WHERE orderTime >= '{$sdate}' AND orderTime <= '{$edate}' 
AND OCode NOT IN
( SELECT orderId FROM orders 
WHERE orders.game_type = '".$this->game_type."' AND orders.date = '{$sdate}') ;";

        echo $sql;echo PHP_EOL;
        $data = \DB::select($sql);

        $res = [];
        foreach ($data as $value){
            $value = (array)$value;
            $tmp = [
                'user_id' => $value['user_id'],
                'game' => $this->game_parent_type,
                'order_number' => $value['orderId'],
                'game_type' => $this->game_type,
                'type_name' => $this->lang->text($this->game_type),
                'game_id' => $this->game_id,
                'server_id' => 0,
                'account' => $value['loginId'],
                'bet' => bcmul($value['bAmount'], 100, 0),
                'profit' => bcmul($value['payment'], 100, 0),
                'date' => $value['orderTime'],
            ];
            \Utils\MQServer::send(\Logic\GameApi\GameApi::$synchronousOrderCallback, $tmp);
        }
        echo count($data);
    }

    public function orderOverPipe($sdate,$edate){
        $query = \DB::table($this->order_table)
            ->where('gameCategory', $this->game_parent_type)
            ->where('orderTime','>=',$sdate)
            ->where('orderTime','<=',$edate);
        $res = $query->groupBy(\DB::raw("left(orderTime,10)" ))
            ->get([
                \DB::raw('left(`orderTime`,10) as date'),
                \DB::raw('count(1) count'),
                \DB::raw('sum(bAmount) bet'),//下注金额
                \DB::raw('sum(validBet) valid_bet'),//有效投注金额
                \DB::raw('sum(payment) win_loss'),//派彩金额
            ])->toArray();
        $data['list'] = $res;
        $data['game_name'] = $this->lang->text($this->game_type);
        $data['game_type'] = $this->game_type;
        $data['user_prefix'] = $this->getUserPrefix();
        return $data;
    }

    public function querySumOrder($sdate,$edate){
        $res = (array)\DB::table($this->order_table)
            ->where('gameCategory', $this->game_parent_type)
            ->where('orderTime','>=',$sdate)
            ->where('orderTime','<=',$edate)
            ->get([
                \DB::raw('count(1) count'),
                \DB::raw('IFNULL(sum(bAmount),0) bet'),//下注金额
                \DB::raw('IFNULL(sum(validBet),0) valid_bet'),//有效投注金额
                \DB::raw('IFNULL(sum(payment),0) win_loss'),//派彩金额
            ])->first();
        return $res;
    }

    public function queryUserSumOrder($sdate, $edate, $gameCode)
    {
        $query = \DB::table($this->order_table)
                    ->where('gameCategory', $this->game_parent_type)
                    ->where('startTime', '>=', $sdate)
                    ->where('startTime', '<=', $edate)
                    ->groupBy('user_id');
        $res = $query->get([
            \DB::raw('user_id'),
            \DB::raw('sum(bAmount) bet'),//下注金额
            \DB::raw('sum(payment) win_loss'),//盈亏金额
        ])->toArray();

        return $res;
    }

    public function getUserPrefix(){
        return 'game'.$this->ci->get('settings')['app']['tid'].'tes';
    }
}