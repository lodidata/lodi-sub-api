<?php
if (count($argv) > 3) {
    echo "参数不合法\n\r";
    return;
}

$date = $argv[2] ?? '';
echo $date;
$bkge = new \Logic\User\Bkge($app->getContainer());
$lock = $app->getContainer()->redis->setnx('user_bkge_active_settle_supervene',1);
$app->getContainer()->redis->expire('user_bkge_active_settle_supervene', 5*60);
if(!$lock) {
    echo 'lock';
    return;
}
$date = date('Y-m-d');
$isAllowRun = $app->getContainer()->redis->hget(\Logic\Define\CacheKey::$perfix['runBkgeActive'], $date);
if($isAllowRun) {
    echo 'exist';
    $app->getContainer()->redis->del('user_bkge_active_settle_supervene');
    return;
}
$sdate = $edate = date('Y-m-d',strtotime('-1 day'));
$bkge->bkgeActiveData($sdate,$edate);  //计算返ti
$bkge->bkgeActive();  //
$app->getContainer()->redis->hset(\Logic\Define\CacheKey::$perfix['runBkgeActive'], $date, 1);
$app->getContainer()->redis->del('user_bkge_active_settle_supervene');
echo PHP_EOL;
echo 'success';
