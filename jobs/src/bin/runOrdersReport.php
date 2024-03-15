<?php
$ci = $app->getContainer();

$type = $argv[2] ?? null;
$res = \Logic\GameApi\GameApi::handleOrdersReport($type);
die($res);
