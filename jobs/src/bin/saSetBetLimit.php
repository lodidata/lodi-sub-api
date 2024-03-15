<?php
/**
 * SA限红
 */

$gameClass = \Logic\GameApi\GameApi::getApi('SA', 0);
$sql = "select ua.user_account from funds_child fc left join user u on fc.pid=u.wallet_id left join game_user_account ua on u.id=ua.user_id where fc.game_type='SA' and fc.create_account='1'";
$list = (array)\DB::select($sql);
foreach($list as $key => $val){
    $val = (array) $val;
    $res = $gameClass->SetBetLimit($val['user_account']);
    if(isset($res['ErrorMsgId']) && $res['ErrorMsgId']){
        echo $val['user_account'] . ' - ' . $res['ErrorMsg'] . PHP_EOL;
    }
}
echo 'success';