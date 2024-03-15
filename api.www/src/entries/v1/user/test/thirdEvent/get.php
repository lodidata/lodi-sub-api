<?php
use Utils\Www\Action;
return new class extends Action
{
    const TOKEN = true;
    const TITLE = '第三方统计 测试';
    const TAGS = "";
    const SCHEMAS = [

    ];
    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $user_login = new Logic\User\User($this->ci);
        $user_id = $this->auth->getUserId();
        //注册统计
        try{
            $user_login->thirdSendMsg($user_id,'login_test');
        }catch (\Exception $e){
            return $this->lang->set(886,[$e->getMessage()]);
        }

        return $this->lang->set(0);
    }
};