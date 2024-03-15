<?php
$ci = $app->getContainer();

$type = $argv[2] ?? null;
$bkge = new \Logic\User\Bkge($ci);
if($type == 2){
    $res = $bkge->agentLoseearnBkgeWeekStart();
}elseif($type == 3){
    $res = $bkge->agentLoseearnBkgeMonthStart();
}

die($res);
