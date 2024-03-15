<?php
use \Logic\Lottery\Rebet;
use \Logic\Lottery\RebetThird;

//global $app, $logger;
//$redis = $app->getContainer()->redis;
//$hour = $redis->get(\Logic\Define\CacheKey::$perfix['rebot_time']) ?? '00';
//var_dump($hour, date('H')); exit;


$alias = $argv[2] ?? '';
$games = \DB::table('game_menu')
    ->where('alias', '=', strtoupper($alias))
    ->get()->toArray();
\Logic\GameApi\GameApi::synchronousData2($games);
print_r($games);
echo PHP_EOL,'success';