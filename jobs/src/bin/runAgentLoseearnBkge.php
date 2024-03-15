<?php
$ci = $app->getContainer();

$date = $argv[2] ?? null;
\DB::table('agent_plat_earnlose')->where('date',$date)->delete();
\DB::table('agent_loseearn_bkge')->where('date',$date)->delete();
$bkge = new \Logic\User\Bkge($ci);
$res = $bkge->agentLoseearnBkgeRun($date);
die($res);
