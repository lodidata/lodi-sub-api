<?php
/**
 * 统计游戏订单
 * @param $type
 * @throws Exception
 */

$type = $argv[2] ?? '';
if(empty($type)){
    echo 'type no';die;
}
echo 'type:'.$type.PHP_EOL;
$type = strtoupper($type);
$gameClass = \Logic\GameApi\GameApi::getApi($type, 0);
$gameClass->checkOrderTotalMoney();