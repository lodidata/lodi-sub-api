<?php
/**
 * 回水
 */

if (count($argv) > 3) {
    echo "参数不合法\n\r";
    return;
}

$date = $argv[2] ?? '';
echo $date;
/*$rebet = new \Logic\Lottery\Rebet($app->getContainer());
$rebet->runByUserLevelRebet($date, $runMode = 'rebet');*/

$rebet = new \Logic\Lottery\RebetThird($app->getContainer());
$rebet->runByUserLevelRebet($date, $runMode = 'rebet');