<?php

$ci = $app->getContainer();

$start_time = $argv[2];

try {
    $games = ['joker','jili','pp','sgmk'];
    foreach ($games as $val) {
        $type    = strtoupper($val);
        $objGame = "\Logic\GameApi\Game\\" . $type;
        if (!class_exists($objGame)) {
            continue;
        }
        $obj = new $objGame($ci);
        var_dump('开始处理：'.$val);

        $obj->splitHistoryData($start_time);

        var_dump($val.'处理完成');
    }
} catch (\Exception $e) {
    var_dump($e->getMessage());
}
die();

