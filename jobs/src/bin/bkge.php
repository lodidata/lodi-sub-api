<?php
/**
 * 返佣
 */
global $app,$logger;
$bkge = new \Logic\User\Bkge($app->getContainer());
$logger->debug("【开始个人返佣】");
$bkge->runData();  // 系统返佣