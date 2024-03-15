<?php
$date= isset($argv[2]) ? $argv[2] : null;
$rebet = new \Logic\Lottery\Rebet($app->getContainer());
$rebet->runByWeekActivity($date);
