<?php
$rebet = new \Logic\Lottery\RebetThird($app->getContainer());
$rebet->runByUserLevelRebet($date = '2023-03-16', $runMode = 'test');
