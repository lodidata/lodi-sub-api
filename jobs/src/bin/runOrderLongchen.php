<?php
$ci = $app->getContainer();
$stime = $argv[2];
$etime = $argv[3];
$type = $argv[4];
$data = \DB::table('game_order_longchen')
    ->where('beji_prize_time','>=',$stime)
    ->where('beji_prize_time','<=',$etime)
    ->get()->toArray();

foreach ($data as $value){
    $value = (array)$value;
    $order = [
        'user_id' => $value['user_id'] ?? 0,
        'game' => 'QP',
        'order_number' => $value['GameID'],
        'game_type' => 'LONGCHEN',
        'type_name' => 'TG棋牌',
        'game_id' => 0,
        'server_id' => 0,
        'account' => $value['Accounts'],
        'bet' => $value['CellScore'],
        'profit' => $value['Profit'],
        'date' => $value['GameEndTime'],
    ];
    $common = new \Logic\GameApi\Common($ci);
    $common->transferOrder($order);

}
echo count($data);
die();

