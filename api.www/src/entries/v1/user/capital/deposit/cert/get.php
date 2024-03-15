<?php

use Utils\Www\Action;
return new class extends Action
{
    public function run()
    {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $trade_no = $this->request->getParam('trade_no', 1);

        $funds_deposit = DB::table("funds_deposit")->where(['trade_no' => $trade_no])->first(['status']);
        if (!$funds_deposit) return $this->lang->set(0);

        try {
            $pay_type  = 'kpay';
            $pay = new Logic\Recharge\Recharge($this->ci);
            if (!$pay->existThirdClass($pay_type)) {
                $desc = '未有该第三方:' . $pay_type . '类，请技术核查';
                throw new \Exception($desc);
            } else {
                $obj = $pay->getThirdClass($pay_type);
                $res   = $obj->showCert($trade_no);
            }
        } catch (\Throwable $e) {
            return $this->lang->set(886, [$e->getMessage()]);
        }
        return $this->lang->set(0, [],['jump_url'=>$res['jump_url']]);
    }
};
