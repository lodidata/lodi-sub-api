<?php
/**
 * 转出第三方
 */

$game_type = $argv[2] ?? '';
if (empty($game_type)) {
    echo 'game_type no';
    die;
}
$game_type = strtoupper($game_type);
echo 'game_type:' . $game_type . PHP_EOL;
global $app, $logger;
try {
    $sql = "select u.id,u.wallet_id,fc.balance from funds_child fc LEFT JOIN `user` u on fc.pid=u.wallet_id where fc.game_type='" . $game_type . "' and fc.balance>0;";
    $list = (array)\DB::select($sql);
    $i = 0;
    foreach ($list as $user) {
        $i++;
        $gameClass = \Logic\GameApi\GameApi::getApi($game_type, $user->id);
        // 下分
        list($freeMoney, $totalMoney) = $gameClass->getThirdBalance();
        if($totalMoney == 0){
            $wid = \Model\User::where('id', $user->id)->value('wallet_id');
            \Model\FundsChild::where('pid', $user->wallet_id)->where('game_type', $game_type)->update(['balance' => $totalMoney]);
        }elseif ($totalMoney) {
            $wid = \Model\User::where('id', $user->id)->value('wallet_id');
            \Model\FundsChild::where('pid', $user->wallet_id)->where('game_type', $game_type)->update(['balance' => $totalMoney]);
            $outStatus = $gameClass->rollOutThird($freeMoney);
            $logger->error($game_type . '转出游戏钱包 uid:'.$user->id . ' wid:'. $user->wallet_id, $outStatus);
        }else{
            $logger->error($game_type . '转出游戏钱包 uid:'.$user->id . ' wid:'. $user->wallet_id. '没有余额或者接口错误');
        }
        if ($i % 100 == 0) {
            sleep(5);
        }
    }
} catch (Exception $e) {
    print_r($e->getMessage());
}