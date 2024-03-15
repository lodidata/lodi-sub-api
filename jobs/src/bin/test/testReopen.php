<?php
$settle = new \Logic\Lottery\Settle($app->getContainer());
 $settle->testOrder($orderNumber = '2018040902124105744');

// $settle->runReopenOld();

//echo 'test', PHP_EOL;
//$settle->runReopenV2();


// $chase = new \Logic\Lottery\Chase($app->getContainer());
// $chase->runByNotify(99, '201805100060');