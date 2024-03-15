<?php
use Utils\Www\Action;
return new class extends Action
{
    const TOKEN = true;
    const TITLE = "获取kpay提现消息";
    const DESCRIPTION = "";
    const TAGS = "提现消息";
    const QUERY = [];
    const SCHEMAS = [];

    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $user_id = $this->auth->getUserId();
        $redisKey = 'kpay:withdraw_message:'.$user_id;
        $transfer_no_arr = $this->redis->lrange($redisKey,0,0);
        if (empty($transfer_no_arr)) {
            $result = [];
        } else {
            $result = ['transfer_no'=>$transfer_no_arr[0]];
        }
        return $this->lang->set(0, [], $result,[]);
    }
};
