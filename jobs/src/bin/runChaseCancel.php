<?php


/**
 * 取消追号
 * @param  integer $chaseNumber [description]
 * @return [type]               [description]
 */
function chaseCancel($chaseNumber){
    global $app;
    $db = $app->getContainer()->db;
    try {
    $db->getConnection()->beginTransaction();

        $chaseOrder = \DB::table('lottery_chase_order')
            ->where('chase_number', $chaseNumber)
            ->first();

        $result = \DB::table('lottery_chase_order_sub')
            ->where('chase_number', $chaseNumber)
            ->where('state', '=', 'created')
            ->first([\DB::raw('SUM(pay_money) AS sum_money'), \DB::raw('COUNT(1) AS total')]);

        if ($result->sum_money == 0 || $result->total == 0) {
            $db->getConnection()->rollback();
            throw new \Exception($chaseNumber . " chase number error!!", 2);
        }
        // 更新追号订单
        $r1 = \DB::table('lottery_chase_order_sub')
            ->where('chase_number', $chaseNumber)
            ->where('state', '=', 'created')
            ->update(['state' => 'cancel']);

        $r2 = \DB::table('lottery_chase_order')
            ->where('chase_number', $chaseNumber)
            ->update(['state' => 'cancel']);
        $user = \DB::table('user')->where('id',$chaseOrder->user_id)->first();

        $memo = "停止追号-追单号:" . $chaseNumber . "-取消总期:" . $result->total;
        $wallet = new \Logic\Wallet\Wallet($app->getContainer());
        // 锁定钱包
        $funds = \Model\Funds::where('id', $user->wallet_id)->lockForUpdate()->first();
        $r3 = $wallet->crease($user->wallet_id, $result->sum_money);
        //流水里面添加打码量可提余额等信息
        $dml = new \Logic\Wallet\Dml($app->getContainer());
        $dmlData = $dml->getUserDmlData($user->id);
        $r4 = \Model\FundsDealLog::create([
            "user_id" => $user->id,
            "user_type" => 1,
            "username" => $user->name,
            "order_number" => $chaseNumber,
            "deal_type" => \Model\FundsDealLog::TYPE_CANCEL_ORDER,
            "deal_category" => \Model\FundsDealLog::CATEGORY_INCOME,
            "deal_money" => $result->sum_money,
            "balance" => intval($funds['balance'] + $result->sum_money),
            "memo" => $memo,
            "wallet_type" => \Model\FundsDealLog::WALLET_TYPE_PRIMARY,
            'total_bet' => $dmlData->total_bet,
            'withdraw_bet' => 0,
            'total_require_bet' => $dmlData->total_require_bet,
            'free_money' => $dmlData->free_money
        ]);
        $db->getConnection()->commit();
    return true;
    } catch (\Exception $e) {
        $db->getConnection()->rollback();
    }
}

$chaseNumber = isset($argv[2]) ?  explode(',',$argv[2]) : '';
if($chaseNumber) {
    foreach($chaseNumber as $val) {
        chaseCancel( intval($val));
    }
}
