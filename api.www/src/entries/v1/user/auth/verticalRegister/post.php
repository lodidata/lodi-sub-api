<?php

use Respect\Validation\Validator as V;
use Utils\Www\Action;
use Respect\Validation\Validator;
use Logic\Define\Lang;

return new class extends Action
{
    const TITLE = "会员注册-提交会员注册(竖版)";
    const DESCRIPTION = "提交会员注册";
    const TAGS = "登录注册";
    const PARAMS = [
        "mobile"        => "string() #联系电话",
        "tel_code"      => "string() #手机验证码",
        "withdraw_pwd"  => "string() #pin密码  也就是提现密码",
        "invit_code"    => "string() #推荐码",
        "gender"        => "int()    #性别 (1:男,2:女,3:保密)",
        "name"          => "string() #名字",
        "surname"       => "string() #姓",
        "bank_id"       => "string() #银行id",
        "bank_account"  => "string() #银行账号",
        "line"          => "string() #line账号",
    ];
    const SCHEMAS = [
            "username"      => "string(required) #用户账号",
            "password"      => "string(required) #密码",
    ];

    public function run() {
        $mobile           = trim($this->request->getParam('mobile'));
        $telCode          = trim($this->request->getParam('tel_code'));
        $withdraw_pwd     = trim($this->request->getParam('withdraw_pwd',''));
        $invit_code       = trim($this->request->getParam('invit_code'));
        $gender           = trim($this->request->getParam('gender'));
        $name             = trim($this->request->getParam('name'));
        $surname          = trim($this->request->getParam('surname'));
        $bank_id          = trim($this->request->getParam('bank_id'));
        $bank_account     = trim($this->request->getParam('bank_account'));
        $line             = trim($this->request->getParam('line'));
        $username          = trim($this->request->getParam('username',''));
        $password     = trim($this->request->getParam('password', ''));
        $re_password  = trim($this->request->getParam('re_password', ''));
        if(isset($password) && !empty($password)){
            if($password != $re_password){
                return $this->lang->set(4003);
            }
        }

        $user = new \Logic\User\User($this->ci);

        $res = $user->verticalRegister($mobile, $withdraw_pwd, $bank_id, $bank_account, $name, $surname,$gender, $telCode, $invit_code, $line, $username, $password);

        return $res;
    }

    /*
     * 原来的注册都注释了
     * public function run() {
        $mobile = $this->request->getParam('mobile');
        $username = $this->request->getParam('user_name');
        $telphoneCode = $this->request->getParam('telphone_code');
        $telCode = $this->request->getParam('tel_code');
        $invitCode = $this->request->getParam('invit_code');
        $password = $this->request->getParam('password');
        $qp = $this->request->getParam('qp');
        $register = $this->request->getParam('register');
        $email = $this->request->getParam('email');
        $verify = $this->request->getParam('verify');

        $user = new \Logic\User\User($this->ci);
        $mobileRegister = false;

        // 判断是否强制开启手机注册
        $registerUser = \Logic\Set\SystemConfig::getModuleSystemConfig('register')['register_type'] ?? 2;
        if ($qp) {
            $registerUser = 4;
            $telCode = 16899;
        }

        if(!$register && in_array($registerUser,[1,2,3,4])) {   // 兼容以前旧接口模式
            if ((!empty($telCode) || !empty($mobile)) || $registerUser == 4) {
                $mobileRegister = true;

                //仅使用手机号注册时，强制要求用户名与手机号需要一致
                if ($username != $mobile && $registerUser == 3) {
                    return $this->lang->set(183);
                }

                $res = $user->registerByMobile($username, $password, $telCode, $telphoneCode, $mobile, $invitCode);
            } else {
                $res = $user->register($username, $password, $invitCode);
            }
        }else {
            //按后台选填配置
            $param = $this->request->getParams();
            $con = \Logic\Set\SystemConfig::getModuleSystemConfig('registerCon');
            $check = array_keys($con);
            foreach ($check as $val) {
                if($con[$val]['required'] == 1 && !isset($param[$val]) || $con[$val]['required'] == 1 && empty(trim($param[$val]))) {
                    return $this->lang->set(886, [$con[$val]['placeholder']]);
                }
            }

            //需要邮箱验证码
            if($con['verify']['required'] == 1){
                $captcha = new \Logic\Captcha\Captcha($this->ci);
                if (!$captcha->validateRegisterTextCodeByEmail($email, $verify)) {
                    return $this->lang->set(123, [], [], ['email' => $email]);
                }
            }

            //手机号验证码等验证
            if(trim($mobile)) {
                $telCode = $con['tel_code']['required'] == 1 ? $telCode : 16899 ;
                $res = $user->registerByMobile($username, $password, $telCode, $telphoneCode, $mobile, $invitCode);
            }else {
                $res = $user->register($username, $password, $invitCode);
            }
        }
        if ($res instanceof Lang && !$res->getState()) {
            // 自动登录
            $res = $this->auth->login($username, $password, 2);
            if ($mobileRegister) {
                (new \Logic\Activity\Activity($this->ci))->bindInfo($this->auth->getUserId(), 1);
            } else {
                (new \Logic\Activity\Activity($this->ci))->bindInfo($this->auth->getUserId(), "");
            }

            return $res;
        }

        return $res;
    }*/
};