<?php
/**
 * 统计游戏订单
 * @param $type
 * @throws Exception
 */

try{
    \Logic\GameApi\GameApi::gameMoneyErrorRefund();
} catch (\Exception $e){
    print_r($e->getMessage());
}
