<?php
/**
 * 异步转入金额到第三方游戏
 * @param $uid
 */

$uid = $argv[2] ?? '';
if(empty($uid)){
    echo 'uid no';die;
}
echo '  uid:'. $uid.PHP_EOL;

global $app,$logger;
try {
    $logger->info("【 synchronousUserBalanceRollOut 】");
    $games = json_decode($app->getContainer()->redis->get(\Logic\Define\Cache3thGameKey::$perfix['gameUserCacheLast'].$uid),true);
    $wid = \Model\User::where('id',$uid)->value('wallet_id');
    $tmp_game = \Model\FundsChild::where('pid',$wid)->where('balance','>',0)->pluck('game_type')->toArray();
    print_r($games);
    foreach ($tmp_game as $val) {
        if ($val != $games['alias']) {
            $gameClass = \Logic\GameApi\GameApi::getApi($val, $uid);
            print_r($gameClass->rollOutThird());
        }
    }
    //玩家进入该游戏转入金额
    $gameClass = \Logic\GameApi\GameApi::getApi($games['alias'], $uid);
    print_r($gameClass->rollInThird());
} catch (\Exception $e) {
    $logger->error("【 synchronousUserBalanceRollOut 】" . $e->getMessage());
}