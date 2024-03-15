<?php

use Utils\Www\Action;

return new class extends Action
{
    const HIDDEN = true;
    const TOKEN = true;
    const TITLE = "小米订单获取订单状态";
    const TAGS = "充值提现";
    const QUERY = [
        'orderNumber' => "string(required) #订单号"
    ];
    const SCHEMAS = [
        'trade_no'  => "string(required) #订单号",
        'status' => 'enum[paid,pending,failed,canceled,rejected](,pid) #订单状态 paid(已支付), pending(待支付), failed(支付失败), canceled(已取消),rejected:已拒绝)',
    ];

    public function run()
    {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $userId = $this->auth->getUserId();

        $orderNumber = $this->request->getParam('orderNumber', '');
        if ($orderNumber == '') {
            return $this->lang->set(886, [$this->lang->text("Order number cannot be empty!")]);
        }

        $userData = (array)DB::table('user')->where('id', $userId)->get(['name', 'role'])->first();
        if(!$userData){
            return $this->lang->set(886, [$this->lang->text("User does not exist!")]);
        }

        $data=\DB::table('funds_deposit')
            ->where('user_id','=',$userId)
            ->where('trade_no','=',$orderNumber)
            ->get(['trade_no','status'])
            ->first();

        if ($data) {
            return $this->lang->set(0, [],$data);
        }

        return $this->lang->set(886,[$this->lang->text("Order does not exist")]);

    }
};