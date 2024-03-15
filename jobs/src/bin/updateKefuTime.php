<?php

try{
    global $app;
    $gameapi = new Logic\Lottery\Rebet($app->getContainer());
    print_r($gameapi->updateKefuTime());
}catch (\Exception $e){
    print_r($e->getMessage());
}
