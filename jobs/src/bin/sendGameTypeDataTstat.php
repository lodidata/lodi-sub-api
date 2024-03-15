<?php
global $app;
$app->getContainer()->redis->del('gameActivity'. date('Y-m-d'));
$date = $argv[2] ?? null;
$gameapi = new Logic\Lottery\Rebet($app->getContainer());
$gameapi->sendGameTypeDataTstat($date);