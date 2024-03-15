<?php
/**
 * 登出
 */
$type = $argv[2] ?? '';
if(empty($type)){
    echo 'type no';die;
}
$uid = $argv[3] ?? '';
if(empty($uid)){
    echo 'uid no';die;
}
echo 'type:'.$type. '  uid:'. $uid.PHP_EOL;
$gameClass = \Logic\GameApi\GameApi::getApi($type, $uid);
// 退出游戏
$gameClass->quitGame();