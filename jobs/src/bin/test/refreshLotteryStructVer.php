<?php
$halls = \Model\Hall::get();
foreach ($halls as $hall) {
    $app->getContainer()->redis->del(\Logic\Define\CacheKey::$perfix['lotteryPlayStruct'].$hall->lottery_id.'_'.$hall->id);
    $app->getContainer()->redis->incr(\Logic\Define\CacheKey::$perfix['lotteryPlayStructVer'].$hall->lottery_id.'_'.$hall->id);
}
echo 'over', PHP_EOL;