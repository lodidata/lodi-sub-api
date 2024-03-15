<?php
$type = $argv[2];

$agent=new \Logic\User\Agent($app->getContainer());
$agent->profitLossSwitch(['type'=>$type]);