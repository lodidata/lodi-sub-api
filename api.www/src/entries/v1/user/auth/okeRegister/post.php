<?php

use Utils\Www\Action;
use Respect\Validation\Validator;
use Logic\Define\Lang;

return new class extends Action
{
    const TITLE = "OKE会员注册";
    const DESCRIPTION = "OKE会员注册";
    const TAGS = "登录注册";
    const PARAMS = [
        "user_name"     => "string(required) #用户名称",
        "password"      => "string(required) #密码",
        "re_password"   => "string() #再次输入密码",
        "email"         => "string() #电子邮箱地址",
        //"telphone_code" => "string() #电话国际区号",
        "mobile"        => "string() #联系电话",
        "invit_code"    => "string() #推荐码",
        "verify_code"      => "string() #手机或者邮箱验证码",
    ];
    const SCHEMAS = [
        "auth" => [
            "token"         => "string(required) #Token字串",
            "expiration"    => "int(required) #生命周期",
            "socketToken"   => "string(required) #socket链接token",
            "socketLoginId" => "string(required) #socket链接id",
            "uuid"          => "string(required) #uuid"
        ]
    ];

    public function run() {
        $mobile       = trim($this->request->getParam('mobile'));
        $username     = trim($this->request->getParam('user_name'));
        $password     = trim($this->request->getParam('password'));
        $re_password  = trim($this->request->getParam('re_password'));
        $invitCode    = trim($this->request->getParam('invit_code'));
        $verifyCode   = trim($this->request->getParam('verify_code'));
        $email        = trim($this->request->getParam('email'));
        if($password != $re_password){
            return $this->lang->set(4003);
        }

        if(empty($mobile) && empty($email)){
            return $this->lang->set(185);
        }

        $user = new \Logic\User\User($this->ci);

        $res = $user->okeRegister($username, $password, $mobile, $email, $verifyCode, $invitCode);

        if ($res instanceof Lang && !$res->getState()) {
            // 自动登录
            $res = $this->auth->login($username, $password, 2);
            (new \Logic\Activity\Activity($this->ci))->bindInfo($this->auth->getUserId(), "");

            return $res;
        }

        return $res;
    }
};