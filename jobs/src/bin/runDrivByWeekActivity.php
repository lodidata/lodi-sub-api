<?php

$dateType = $argv[2] ?? 'week';
if(!in_array($dateType, ['week', 'month'])){
    echo '参数错误:week-month';die;
}

$startDate = $argv[3] ?? null;
$endDate = $argv[4] ?? null;

$rebet = new \Logic\Lottery\Rebet($app->getContainer());
$rebet->drivByWeekActivity($dateType,$startDate,$endDate);
echo '返水结束';