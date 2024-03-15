<?php
use \Logic\Recharge\Recharge;
$ci = $app->getContainer();

$orderList = [];

foreach($orderList as $tradeNo)
{
    try{
        $recharge = new \Logic\Recharge\Recharge($ci);
        $deposit = \Model\FundsDeposit::where('trade_no', '=', $tradeNo)->where('status','!=','paid')
            ->first();

        if ($deposit) {

            $result = $recharge->onlineCallBack($deposit, 0);
            if ($result) {
                //发放直推-充值奖励
                $obj = new \Logic\Recharge\Recharge($ci);
                $obj->directRechargeAward($deposit->user_id,$deposit->money);

                //发送消息
                $result['order_no'] = $tradeNo;
                $result['trade_no'] = $tradeNo;
                $result['trade_time'] = date('Y-m-d H:i:s');
                $recharge->onlinePaySuccessMsg($result, '补单', '操作人员ID：脚本' );
                $user_info = DB::table('user')
                    ->find($deposit->user_id);

                (new \Logic\Admin\Log($ci))->create($deposit->user_id, $user_info->name, \Logic\Admin\Log::MODULE_CASH, '线上充值', '线上充值', '补单', 1, "订单号：{$tradeNo}");
            }
        }

    } catch (\Exception $e){
        print_r($e->getMessage());
        die;
    }
}
die('succes');