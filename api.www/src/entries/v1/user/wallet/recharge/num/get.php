<?php

use Utils\Www\Action;

return new class extends Action {
    const TOKEN = true;
    const TITLE = 'GET 获取会员充值次数';
    const TAGS = "充值提现";
    const SCHEMAS = [
        'num' => 'int #充值次数',
    ];

    public function run() {
        $verify = $this->auth->verfiyToken();

        if (!$verify->allowNext()) {
            return $verify;
        }
        $user_id = $this->auth->getUserId();
        $num = \Model\UserData::where('user_id',$user_id)->value('deposit_num');

        return $this->lang->set(0,[],$num??0);
    }
};
