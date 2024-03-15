<?php

$type = $argv[2] ?? '';
if(empty($type)){
    echo 'type no';die;
}
echo 'type:'.$type.PHP_EOL;
$type = strtoupper($type);
global $app;
$class = "\Logic\GameApi\Error\\" . $type;
$obj = new $class($app->getContainer());
$obj->addOrder();