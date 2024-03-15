<?php
/**
 * 处理orders_temp表
 */
$ci = $app->getContainer();
try{
    return (new \Logic\GameApi\Common($ci))->HandleOrdersTemp();
}catch (\Exception $e){
    print_r($e->getMessage());
}
