<?php

use Logic\Admin\BaseController;

return new class() extends BaseController
{
    const TITLE       = '交易流水(记录)/资金流水--类别与类型';
    const QUERY       = [
        'order_number' => 'string() #订单ID',
        'money' => 'int() #金额：分',
    ];
    
    const PARAMS      = [];
    const SCHEMAS     = [
        [
            'State' => 'int #操作1：处理成功，2：已补单，3：失败需要继续添加，4：失败无需继续',
            'msg'  => 'string #错误信息，统一 SUCCESS为成功',
        ]
    ];
    public function run()
    {
        $ips = \DB::table('pay_site_config')->value('ip');
        if($ips){
            $ips = explode(',',$ips);
        }else{
            $ips = [];
        }
        $ip = \Utils\Client::getIp();

        if($ips && !in_array($ip,$ips) && false) {
            return ['State' => 4, 'ErrMes' => '请联系添加白名单'];
        }

        $Param = $this->request->getParam('Param');
        $order_number = $Param['BillNo'];
        $money = $Param['Gold'];
        $this->pay = new \Logic\Recharge\Pay($this->ci);
        //某些第三方我们在订单生成的时间拿不到金额，只有回调的时候才能拿到
//        $money && \DB::table('funds_deposit')->where('trade_no','=',$order_number)->update(['money'=>$money]);
        if (!isset($re) && !$order_number) {
            $re = ['State' => 4, 'ErrMes' => '数据缺失，请核对'];
        }
        $deposit = $this->pay->getDepositByOrderId($order_number);
        if (!isset($re) && !$deposit) {
            $re = ['State' => 4, 'ErrMes' => '查无此订单,请核对'];
        }
        if (!isset($re) && $money != $deposit->money && false) {
            $re = ['State' => 4, 'ErrMes' => '订单金额不一致，请核对'];
        }
        if (!isset($re) && $deposit->status != 'pending') {
            $re = ['State' => 2, 'ErrMes' => '客户已补单'];
        }
        if(!isset($re)) {
            $recharge = new \Logic\Recharge\Recharge($this->ci);
            $result = $recharge->onlineCallBack($deposit);
            //发送消息
            if ($result) {
                $result['order_no'] = $order_number;
                $result['trade_no'] = $order_number;
                $result['trade_time'] = date('Y-m-d H:i:s', time());
                $recharge->onlinePaySuccessMsg($result, null);
                $re = ['State' => 1, 'ErrMes' => ''];
            }
        }
        !isset($re) && $re = ['State' => 3, 'ErrMes' => ''];
        die(json_encode($re));
    }

};
