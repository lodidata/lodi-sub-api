<?php
$ci = $app->getContainer();

$date = $argv[2] ?? null;

$gameapi = new Logic\Lottery\Rebet($ci);
$gameapi->sendGameTypeDataTstat();
die('success');
