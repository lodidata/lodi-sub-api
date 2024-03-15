<?php
$ci = $app->getContainer();
$ip = \Utils\Client::getIp();

$userList = ['183895'=>'8440',];
foreach ($userList as $user_id => $send_money){
    $memo = 'Activity gift ' . $send_money;
    $send_money = bcmul($send_money, 100, 0);
    $play_code = $send_money * 10;
    $re = (new \Logic\Recharge\Recharge($ci))->handSendCoupon($user_id, $play_code, $send_money,$memo,$ip);
}

die('执行完毕');

