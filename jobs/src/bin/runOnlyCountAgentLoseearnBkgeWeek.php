<?php
$ci = $app->getContainer();

$user_name = $argv[2] ?? null;
$days = date('w') == 0 ? 13 : date('w') + 6;
//上周一
$start_date = date('Y-m-d',time()-$days*86400);
$days = date('w') == 0 ? 7 : date('w');
//上周日
$end_date = date('Y-m-d',time()-$days*86400);
$bkge = new \Logic\User\Bkge($ci);
$res = $bkge->onlyCountOneAgentLoseearnBkge($user_name,$start_date, $end_date);

die($res);
