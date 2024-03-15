<?php
use Utils\Www\Action;
use Model\User;

return new class extends Action {
    const TOKEN = true;
    const TITLE = "编辑是否需要优惠";
    const TAGS = "优惠活动";
    const QUERY = [
    ];
    const PARAMS = [
        "status"  => "int(required) #需要:1,不需要:0",
    ];
    const SCHEMAS = [
   ];

    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $status = $this->request->getParam('status');
        if($status){
            $value = "auth_status &~2";
        }else{
            $value = "auth_status |2";
        }
        $res = User::updateDepositCoupon($this->auth->getUserId(), $value);
        return $this->lang->set($res ? 0 : -1, []);
    }
};