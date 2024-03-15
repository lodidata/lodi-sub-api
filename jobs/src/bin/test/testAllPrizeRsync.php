<?php
# sudo php runBin.php testAllPrizeRsync 2018-04-21
\Logic\Lottery\RsyncLotteryInfo::init();

\Logic\Lottery\RsyncLotteryInfo::allprize(
    \Logic\Lottery\RsyncLotteryInfo::$lottery,
    isset($argv[2]) ? $argv[2] : '' // 日期参数 2018-01-01
);


