<?php
//活动返水扣款
/*$rebet_list = \DB::table('active_apply')->selectRaw('coupon_money,user_id')
    ->where('created','>=','2023-03-17')->where('active_id',249)->get()->toArray();


if($rebet_list){
    global $app;
    foreach ($rebet_list as $v){
        $v=(array)$v;
        $recharge = new \Logic\Recharge\Recharge($app->getContainer());

        $result = $recharge->tzHandDecrease(
            $v['user_id'],
            $v['coupon_money'],
            'deCoupon',
            \Utils\Client::getIp(),
            0,
            1,
            false,
            \Model\FundsDealLog::TYPE_REDUCE_MANUAL
        );

    }
}*/
