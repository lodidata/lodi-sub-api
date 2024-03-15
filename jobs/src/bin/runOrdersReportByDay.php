<?php
$ci = $app->getContainer();

$date = $argv[2] ?? null;
$res = \Logic\GameApi\GameApi::insertOrdersReportByDay($date);
die($res);
