<?php
$ci = $app->getContainer();

$sdate = $argv[2];
$edate = $argv[3] . ' 23:59:59';

$sql = " SELECT send_prize.id,send_prize.user_id,send_prize.user_name,send_prize.order_number,send_prize.pay_money,send_prize.money as prize,send_prize.lose_earn,send_prize.created FROM send_prize 
LEFT JOIN `user` ON user.id = send_prize.user_id
WHERE send_prize.created >= '{$sdate}' AND send_prize.created <= '{$edate}' AND tags NOT IN (4,7)  
AND order_number not in ( 
 SELECT orders.order_number FROM orders 
 LEFT JOIN `user` ON user.id = orders.user_id WHERE orders.game_type =  'ZYCPSTA' AND orders.date >= '{$sdate}' AND orders.date <= '{$edate}'  AND user.tags NOT IN (4,7)) ;";

echo $sql;echo PHP_EOL;
$data = \DB::select($sql);

$res = [];
foreach ($data as $val){
    $val = (array)$val;
//    $tmp = [
//        'user_id' => $val['user_id'],
//        'order_number' => $val['order_number'],
//        'game' => 'CP',
//        'game_type' => 'ZYCPSTA',
//        'type_name' => '彩票',
//        'play_id' => 0,
//        'bet' => $val['pay_money'],
//        'profit' => $val['lose_earn'],
//        'send_money' => $val['prize'],
//        'dml' => $val['pay_money'],
//        'date' => $val['created'],
//    ];
//    $res[] = $tmp;
    $tmp = [
        'user_id' => $val['user_id'],
        'game' => 'CP',
        'order_number' => $val['order_number'],
        'game_type' => 'ZYCPSTA',
        'type_name' => '彩票',
        'game_id' => 0,
        'server_id' => 0,
        'account' => $val['user_name'],
        'bet' => $val['pay_money'],
        'profit' => $val['lose_earn'],
        'date' => $val['created'],
    ];
    \Utils\MQServer::send(\Logic\GameApi\GameApi::$synchronousOrderCallback, $tmp);
}

//\DB::table('orders')->insert($res);
echo count($data);
die();

