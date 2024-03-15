<?php

use Utils\Www\Action;
use Respect\Validation\Validator;
use Logic\Define\Lang;

return new class extends Action
{
    const TITLE = "MXN会员注册";
    const DESCRIPTION = "MXN会员注册";
    const TAGS = "登录注册";
    const PARAMS = [
        "user_name"     => "string(required) #用户名称",
        "password"      => "string(required) #密码",
        "re_password"   => "string() #再次输入密码",
        //"mobile"        => "string() #联系电话",
        "invit_code"    => "string() #推荐码",
        "token"         => "string(optional) #图片验证码token",
        "code"          => "string(optional) #图片验证码",
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
        $token        = trim($this->request->getParam('token'));
        $code         = trim($this->request->getParam('code'));

        /*$captcha = new \Logic\Captcha\Captcha($this->ci);
        if (!$captcha->validateImageCode($token, $code)) {
            return $this->lang->set(105);
        }*/

        if($password != $re_password){
            return $this->lang->set(4003);
        }

        $user = new \Logic\User\User($this->ci);

        $res = $user->mxnRegister($username, $password, $invitCode, $mobile, $code);

        if ($res instanceof Lang && !$res->getState()) {
            // 自动登录
            $res = $this->auth->login($username, $password, 2);
            (new \Logic\Activity\Activity($this->ci))->bindInfo($this->auth->getUserId(), "");

            return $res;
        }

        return $res;
    }
};