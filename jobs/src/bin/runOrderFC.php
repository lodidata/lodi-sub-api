<?php
global $app;
$ci = $app->getContainer();
$stime = $argv[2];
$etime = $argv[3];

//游戏类型
$platformTypes = [
    2 => ['id' => 93, 'game' => 'GAME', 'type' => 'FC'],
    1 => ['id' => 94, 'game' => 'BY', 'type' => 'FCBY'],
    7 => ['id' => 93, 'game' => 'GAME', 'type' => 'FC'],
];

//打码量配置
$auditSetting = \Logic\Set\SystemConfig::getModuleSystemConfig('audit');

$sql = " SELECT user_id,gametype,recordID,bet,prize,bdate FROM game_order_fc WHERE bdate >= '{$stime}' AND bdate <= '{$etime}' ";

$data = \DB::select($sql);
$batchOrderData = [];
foreach ($data as $val){
    $val = (array) $val;
    if(!isset($platformTypes[$val['gametype']])){
        $val['gametype'] = 2;
    }

    //拉取订单其它后续 逻辑 处理通知
    $orders = [
        'user_id' => $val['user_id'],
        'game' => $platformTypes[$val['gametype']]['game'],
        'order_number' => $val['recordID'],
        'game_type' => $platformTypes[$val['gametype']]['type'],
        'type_name' => $ci->lang->text($platformTypes[$val['gametype']]['type']),
        'play_id' => $platformTypes[$val['gametype']]['id'],
        'bet' => bcmul($val['bet'], 100, 0),
        'profit' => bcmul($val['prize']-$val['bet'], 100, 0),
        'send_money' => bcmul($val['prize'], 100, 0),
        'order_time' => $val['bdate'],
        'date' => substr($val['bdate'], 0, 10),
        'created' => date('Y-m-d H:i:s')
    ];
    $gameAduitSetting = isset($auditSetting[$orders['game']]) && $auditSetting[$orders['game']] ? bcdiv($auditSetting[$orders['game']], 100, 2) : 1;//游戏类型打码量设置，如果不存在则为1
    $orders['dml'] = $orders['bet'] * $gameAduitSetting;
    $batchOrderData[] = $orders;
}

$obj = new \Logic\GameApi\Game\FC($ci);
$obj->addGameToOrdersTable($batchOrderData);

echo 'OK';