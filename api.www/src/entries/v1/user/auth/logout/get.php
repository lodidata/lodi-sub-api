<?php
use Utils\Www\Action;
return new class extends Action
{
    const TOKEN = true;
    const TITLE = "用户注销";
    const TAGS = "登录注册";
    const SCHEMAS = [
   ];


    public function run() {
        return $this->auth->logout(null, $this->auth->getCurrentPlatformGroupId());
        // echo 1;exit;
    }
};