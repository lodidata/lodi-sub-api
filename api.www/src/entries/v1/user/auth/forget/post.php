<?php
use Utils\Www\Action;
use Respect\Validation\Validator as V;
use Logic\Define\Lang;
return new class extends Action
{
    const TITLE = '忘记登录密码-设置密码';
    const TAGS = "登录注册";
    const PARAMS = [
        "name" => "string(required) #用户账号或手机号",
        'password'  => 'string(required) #密码',
        'code'      => 'string(required) #手机或者邮箱验证码',
    ];
    const SCHEMAS = [];

    public function run() {
        $validator = $this->validator->validate($this->request, [
            'name' => V::username()->setName($this->lang->text("username")),
            'password' => V::password()->setName($this->lang->text("password")),
            'code' => V::captchaTextCode()->setName($this->lang->text("sms code"))
        ]);

        if (!$validator->isValid()) {
            return $validator;
        }

        $name = $this->request->getParam('name');
        $name_mobile =\Utils\Utils::RSAEncrypt($name);
        $password = $this->request->getParam('password');
        $code = $this->request->getParam('code');
        
        $user = \Model\User::where('name', $name)->orWhere('mobile', $name_mobile)->first();
        if (empty($user)) {
            return $this->lang->set(51);
        }
        $captcha = new \Logic\Captcha\Captcha($this->ci);
        $userId =$user['id'];
        $profile = \Model\Profile::where('user_id', $userId)->first();
        $mobile = \Utils\Utils::RSADecrypt($user['mobile']);
        $email = \Utils\Utils::RSADecrypt($profile['email']);
        if (!$captcha->validateTextCode($mobile, $code) && !$captcha->validateTextCodeByEmail($user['id'], $code)) {
            return $this->lang->set(123);
        }

        $salt = \Model\User::getGenerateChar(6);
        $user->update(['password' => \Model\User::getPasword($password, $salt), 'salt' => $salt]);
        return $this->lang->set(126);
    }
};