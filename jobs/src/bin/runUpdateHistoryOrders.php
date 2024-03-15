<?php

$ci = $app->getContainer();
define('RUN_HISTORY_ORDERS',1);
try {
    var_dump('开始合并游戏订单');
    \Logic\GameApi\GameApi::synchronousOrders();
    var_dump('合并游戏订单结束');
    var_dump('开始合并彩票订单');
    \Logic\GameApi\GameApi::synchronousLotteryOrders();
    var_dump('合并彩票订单结束');
} catch (\Exception $e) {
    var_dump($e->getMessage());
}
die();

