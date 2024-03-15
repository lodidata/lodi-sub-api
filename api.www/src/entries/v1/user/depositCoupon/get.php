<?php
use Utils\Www\Action;
use Model\User;

return new class extends Action {
    const TOKEN = true;
    const TITLE = "是否接受优惠";
    const QUERY = [
    ];
    const TAGS = "优惠活动";
    const SCHEMAS = [
       [
           "status"            => "int() #状态值(1:接受优惠, 0:拒绝优惠)",
       ]
   ];

    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $res = User::needDepositCoupon($this->auth->getUserId());
        return $this->lang->set(0, [], ['status' => $res]);
    }
};