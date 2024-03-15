<?php
/**
 * 拉单
 */
$type = $argv[2] ?? '';
if(empty($type)){
    echo 'type no';die;
}
echo $type.PHP_EOL;
try{
    return \Logic\GameApi\GameApi::synchronousData2([['type' => $type]]);
}catch (\Exception $e){
    print_r($e->getMessage());
}
