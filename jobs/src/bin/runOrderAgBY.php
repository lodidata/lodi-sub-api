<?php
$ci = $app->getContainer();
$stime = $argv[2];
$etime = $argv[3];
$zero = $argv[4] ?? 0;
$data = \DB::table('game_order_agin_by')
    ->where('beji_prize_time','>=',$stime)
    ->where('beji_prize_time','<=',$etime);

    if($zero){
        $data->where('UserCashPay','<=',0);
    }
$data = $data->get()->toArray();

foreach ($data as $value){
    $value = (array)$value;
    $order = [
        'user_id' => $value['user_id'] ?? 0,
        'game' => 'BY',
        'order_number' => $value['BillId'],
        'game_type' => 'AGINBY',
        'type_name' => 'AG捕鱼',
        'game_id' => $value['FishId'],
        'server_id' => $value['FishType'],
        'account' => $value['UserName'],
        'bet' => $value['UserCashPay'],
        'profit' => $value['UserCashEarn'] - $value['UserCashPay'],
        'date' => $value['beji_prize_time'],
    ];
    $common = new \Logic\GameApi\Common($ci);
    $common->transferOrder($order);

}
echo count($data);
die();

