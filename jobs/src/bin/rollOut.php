<?php
/**
 * 转出第三方
 */

$game_type = $argv[2] ?? '';
if(empty($game_type)){
    echo 'game_type no';die;
}
$uid = $argv[3] ?? '';
if(empty($uid)){
    echo 'uid no';die;
}
echo 'game_type:'.$game_type. '  uid:'. $uid.PHP_EOL;
try {
    $gameClass = \Logic\GameApi\GameApi::getApi($game_type, $uid);
    // 下分
    list($freeMoney, $totalMoney) = $gameClass->getThirdBalance();
    if($totalMoney) {
        $wid = \Model\User::where('id',$uid)->value('wallet_id');
        \Model\FundsChild::where('pid',$wid)->where('game_type',$game_type)->update(['balance' => $totalMoney]);
    }
    $gameClass->rollOutThird($freeMoney);

} catch (Exception $e) {
    print_r($e->getMessage());
}