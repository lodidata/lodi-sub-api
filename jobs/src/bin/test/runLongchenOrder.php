<?php
$ci = $app->getContainer();

$sdate = $argv[2];
$edate = $argv[3] . ' 23:59:59';

$sql = " SELECT * FROM game_order_longchen
WHERE GameEndTime >= '{$sdate}' AND GameEndTime <= '{$edate}' 
AND GameID NOT IN
( SELECT order_number FROM orders 
WHERE orders.game_type =  'LONGCHEN' AND orders.date >= '{$sdate}' AND orders.date <= '{$sdate}' ORDER BY order_number) ;";

echo $sql;echo PHP_EOL;
$data = \DB::select($sql);

$res = [];
foreach ($data as $value){
    $value = (array)$value;
    $tmp = [
        'user_id' => $value['user_id'],
        'game' => 'QP',
        'order_number' => $value['GameID'],
        'game_type' => 'LONGCHEN',
        'type_name' => 'TG棋牌',
        'game_id' => $value['KindID'],
        'server_id' => $value['KindID'],
        'account' => $value['Accounts'],
        'bet' => $value['CellScore'],
        'profit' => $value['Profit'],
        'date' => $value['GameEndTime'],
    ];
    \Utils\MQServer::send(\Logic\GameApi\GameApi::$synchronousOrderCallback, $tmp);
}
echo count($data);
die();

