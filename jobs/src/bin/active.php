<?php
/**
 *
 *
 */
global $app;
$gameapi = new Logic\Lottery\BatchLottery2($app->getContainer());
$gameapi->batchSendLottery2();