<?php
$data = \DB::table('game_order_agin_by')->get()->toArray();
foreach ($data as $value) {
    $value = (array)$value;
    \Utils\MQServer::send(\Logic\GameApi\GameApi::$synchronousOrderCallback, [
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
        'date' => date('Y-m-d H:i:s', $value['Time']),
    ]);
}

die();

