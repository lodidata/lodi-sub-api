<?php
/**
 * 代付
 */
$id = $argv[2] ?? '';
if(empty($id)){
    echo 'order_id no';die;
}
global $app,$logger;
$transfer = new  Logic\Transfer\ThirdTransfer($app->getContainer());
$transfer->getTransferResult($id);