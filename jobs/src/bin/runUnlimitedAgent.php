<?php
$ci = $app->getContainer();

$type = $argv[2] ?? null;
$bkge = new \Logic\User\Bkge($ci);
$res = $bkge->unlimitedAgentBkgeRun($type);
die($res);
