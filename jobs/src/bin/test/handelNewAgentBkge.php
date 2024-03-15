<?php
try{
    $date= isset($argv[2]) ? $argv[2] : null;
    $dateType= isset($argv[3]) ? $argv[3] : null;
    $bkge = new \Logic\User\Bkge($app->getContainer());
    $res = $bkge->newBkgeRunData($date,$dateType);
    echo $res;
}catch (\Throwable $e){
    echo $e->getMessage();
}
echo '完成';die;