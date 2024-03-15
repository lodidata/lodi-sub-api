<?php

use Model\GameOrderExec;

$ci = $app->getContainer();

$type = $argv[2];
$type = strtoupper($type);
try {
    $where = [
        ['type','=',$type],
    ];
    $games = \DB::table('game_menu')
        ->where($where)
        ->get(['id','type'])->toArray();
    foreach ($games as $val) {
        $val     = (array)($val);
        $type    = strtoupper($val['type']);
        $objGame = "\Logic\GameApi\Game\\" . $type;
        $ci->logger->info(" 【update orders】 ". $type . ' start ');

        if (!class_exists($objGame)) {
            continue;
        }
        $obj = new $objGame($ci);


            $order_exec = GameOrderExec::select('last_id','stop')->where('game_type', $type)->first();
            if(!$order_exec){
                $data = [
                    'game_type' => $type,
                    'last_id'   => 0,
                    'stop'      => 0
                ];
                GameOrderExec::insert($data);
                $order_exec = GameOrderExec::select('last_id','stop')->where('game_type', $type)->first();
            }

        $order_exec = $order_exec->toArray();

        //该游戏停止更新到orders
        if($order_exec['stop']) continue;

        $game_table = $obj->orderTableName;

        //子表最大id
        $max_id =  \DB::table($game_table)->max('id');
        //说明子表没有新注单
        if($order_exec['last_id'] >= $max_id) continue;

        $obj->childUpdateOrders($type, $val['id'], $order_exec['last_id'], $max_id);
    }
} catch (\Exception $e) {
    $ci->logger->error("【updateOrders】" . $e->getMessage());
}
die();

