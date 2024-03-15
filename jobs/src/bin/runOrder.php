<?php
$ci = $app->getContainer();
$all = $ci->redis->hGetAll(\Logic\Define\Cache3thGameKey::$perfix['gameList']);
$type = $argv[2] ?? null;
if($type == 'all' || $type == null) {
    foreach ($all as $type => $objGame) {
        if (!class_exists($objGame)) {
            continue;
        }
        print_r($objGame);echo PHP_EOL;
        $obj = new $objGame($ci);
        $obj->initGameType($type);
        $obj->synchronousData();
    }
}else {
    $objGame = "\Logic\GameApi\Game\\".$type;
    print_r($objGame);echo PHP_EOL;
    $obj = new $objGame($ci);
    $obj->initGameType($type);
    $obj->synchronousData();
}
die();

