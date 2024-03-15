<?php

use Utils\Www\Action;
use Respect\Validation\Validator as V;

return new class extends Action
{
    public function run()
    {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $trade_no = $this->request->getParam('trade_no', 1);
        //主订单
        $transfer_no = $this->request->getParam('transfer_no', 1);
        //子订单
        $transfer_no_sub = $this->request->getParam('transfer_no_sub', 1);
        $status = $this->request->getParam('status', 0);
        $transfer_sub = DB::table("transfer_no_sub")
            ->where(['sub_order' => $transfer_no_sub, 'transfer_no' => $transfer_no])
            ->first(['status','is_reward']);
        if (empty($transfer_sub)) return $this->lang->set(0);
        if ($transfer_sub->status == 'canceled') return $this->lang->set(886,['The order has been canceled']);
        if($transfer_sub->is_reward==1 && strcmp($transfer_sub->status,'success')==0) return $this->lang->set(885);
        if ($status == 0 && $transfer_sub->status == 'dispute') return $this->lang->set(886,['The order enters dispute processing and is to be resolved by customer service']);
        try {
            $userId = $this->auth->getUserId();
            $result = (new Logic\Transfer\ThirdTransfer($this->ci))->sendLodiPayUserAmount($trade_no, $transfer_no, $transfer_no_sub, $status,$userId);
        } catch (\Throwable $e) {
            return $this->lang->set(886, [$e->getMessage()]);
        }
        return $this->lang->set($result['code'], [$result['msg']]);
    }
};
