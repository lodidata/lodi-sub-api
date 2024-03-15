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
        $message = $this->request->getParam('message','');
        $user_id = $this->auth->getUserId();
        try {
            $pay_type  = 'KPAY';
            $className = "Logic\Transfer\ThirdParty\\" . strtoupper($pay_type);
            if (!class_exists($className)) {
                $desc = '未有该第三方:' . $pay_type . '类，请技术核查';
                throw new \Exception($desc);
            } else {
                $transfer_config = (array)DB::table('transfer_config')
                    ->where('code',$pay_type)
                    ->first(['key','pub_key','partner_id','url_return','url_notify','request_url','app_secret','pay_callback_domain']);
                $obj = new $className;
                $obj->init($transfer_config);
                $res = $obj->uploadSms($user_id,$message);
            }
        } catch (\Throwable $e) {
            return $this->lang->set(886, [$e->getMessage()]);
        }
        return $this->lang->set(0);
    }
};
