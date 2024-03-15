<?php
try{
    \Logic\GameApi\GameApi::gameMoneyErrorRefund();
}catch (\Throwable $e){
    echo $e->getMessage();
}
echo '完成';die;