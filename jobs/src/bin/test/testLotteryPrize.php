<?php

$orderNumber = $argv[2];
$mode = $argv[3] ?? 'sendPrize';
$settle = new \Logic\Lottery\Settle($app->getContainer());
list($maxSendPrize, $maxOdds) = $settle->getConfig();
// $logic = new \LotteryPlay\Logic;
// 查询该期订单
$orderData = \Model\LotteryOrder::where('order_number', $orderNumber)
    ->get();

if (empty($lotteryInfo)) {
    $lotteryInfo = \Model\LotteryInfo::where('lottery_number', $orderData[0]['lottery_number'])
        ->where('lottery_type', $orderData[0]['lottery_id'])
        ->first();
}

foreach ($orderData ?? [] as $order) {
    $order = $settle->runSingle($lotteryInfo, $order, $maxSendPrize, $maxOdds, $runMode = $mode);
    print_r($order);
}