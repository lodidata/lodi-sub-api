<?php
$ci = $app->getContainer();

$userName = $argv[2] ?? null;
$startDate = $argv[3] ?? null;
$endDate = $argv[4] ?? null;
$bkge = new \Logic\User\Bkge($ci);
$re = $bkge->onlyCountAgentLoseearnBkge($userName,$startDate,$endDate);
die($re);
