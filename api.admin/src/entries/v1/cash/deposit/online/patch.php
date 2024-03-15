<?php

use Logic\Admin\BaseController;
use Logic\Admin\Log;

return new class() extends BaseController
{
    const STATE = '';
    const TITLE = '线上存款补单';
    const DESCRIPTION = '';

    const QUERY = [
        'order_no' => 'string(required) #单号'
    ];

    const PARAMS = [];
    const STATEs = [];
    const SCHEMAS = [];
    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run($tradeNo)
    {
        $this->checkID($tradeNo);
        $recharge = new \Logic\Recharge\Recharge($this->ci);
        $deposit = \Model\FundsDeposit::where('trade_no', '=', $tradeNo)
            ->first();

        if ($deposit) {
            //2022-03-03 直接完成不调第三方
            /*try{
                //调用第三方 看是否支付
                $recharge->getPayStatus($tradeNo, $deposit);
            }catch (\Throwable $e){
                return $this->lang->set(886, [$e->getMessage()]);
            }*/

            $result = $recharge->onlineCallBack($deposit, $this->playLoad['uid']);
            if ($result) {
                //发放直推-充值奖励
                $obj = new \Logic\Recharge\Recharge($this->ci);
                $obj->directRechargeAward($deposit->user_id,$deposit->money);

                //发送消息
                $result['order_no'] = $tradeNo;
                $result['trade_no'] = $tradeNo;
                $result['trade_time'] = date('Y-m-d H:i:s');
                $recharge->onlinePaySuccessMsg($result, '补单', '操作人员ID：' . $this->playLoad['uid']);
                $this->addLogs($tradeNo, 1);
                return $this->lang->set(0);
            }
        }
        $this->addLogs($tradeNo, 0);
        return $this->lang->set(-2);
    }

    public function addLogs($tradeNo, $result)
    {
        /*==================================日志操作代码===============================*/
        $dep = DB::table('funds_deposit')
            ->where('trade_no', '=', $tradeNo)
            ->get()
            ->first();
        $dep = (array)$dep;

        $user_info = DB::table('user')
            ->find($dep['user_id']);

        (new Log($this->ci))->create($dep['user_id'], $user_info->name, Log::MODULE_CASH, '线上充值', '线上充值', '补单', $result, "订单号：{$tradeNo}");
        /*=================================================================*/
    }
};
