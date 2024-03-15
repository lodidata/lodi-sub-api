<?php
global $app;
$gameapi = new Logic\Lottery\Rebet($app->getContainer());
$gameapi->sendSpreadTypeDataTstat();