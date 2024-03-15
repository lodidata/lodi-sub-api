<?php
$rebet = new \Logic\Lottery\Rebet($app->getContainer());
$rebet->runByUserLevelRebet($date = '2018-08-14', $runMode = 'test');