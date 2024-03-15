<?php

$date = isset($argv[2]) ? date('Y-m-d',strtotime($argv[2])): date('Y-m-d');
echo $date;
$lottery = \Logic\Lottery\RsyncLotteryInfo::init();
\Logic\Lottery\RsyncLotteryInfo::allprize($lottery,$date);