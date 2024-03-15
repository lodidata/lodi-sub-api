<?php
$ci = $app->getContainer();

$month = $argv[2] ?? null;
$bkge = new \Logic\User\Bkge($ci);
$res = $bkge->SendSomeAgentLoseearnBkgeMonthStart($month);
die($res);
