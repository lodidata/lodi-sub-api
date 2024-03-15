<?php
/**
 * 层级的月俸禄模块
 * @author Taylor 2018-12-28
 */
$award = new \Logic\Level\Award($app->getContainer());
$award->monthly_award();
