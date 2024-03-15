<?php

use Logic\Recharge\Recharge;
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
        $userId = $this->auth->getUserId();
        $info = \DB::table('funds_deposit')->where('user_id',$userId)->where('trade_no',$trade_no)->first();
        $config    = Recharge::getThirdConfig('KPAY');
        return $this->lang->set(0, [],$info,['deposit_type'=>$config['id']]);
    }
};
