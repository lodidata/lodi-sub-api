<?php
use Utils\Www\Action;
use Logic\Level\Level as levelLogic;

return new class extends Action {
    const TOKEN = true;
    const TITLE = "取消kpay未支付订单";
    const TAGS = "钱包";
    const QUERY = [
    ];
    const SCHEMAS = [];
    public function run() {

        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $trade_no = $this->request->getParam('trade_no', 1);
        $res = DB::table("funds_deposit")->where(['trade_no' => $trade_no,'status'=>'pending'])->update(['status'=>'canceled']);
        try {
            if ($res) {
                $pay_type  = 'kpay';
                $pay = new Logic\Recharge\Recharge($this->ci);
                if (!$pay->existThirdClass($pay_type)) {
                    $desc = '未有该第三方:' . $pay_type . '类，请技术核查';
                    throw new \Exception($desc);
                } else {
                    $obj = $pay->getThirdClass($pay_type);
                    $res   = $obj->cancelRecharge($trade_no);
                }
            }

        } catch (\Throwable $e) {
            return $this->lang->set(886, [$e->getMessage()]);
        }
        return $this->lang->set(0);
    }

};