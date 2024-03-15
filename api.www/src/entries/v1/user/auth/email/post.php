<?php
use Utils\Www\Action;
use Respect\Validation\Validator as V;

return new class extends Action
{
    const TITLE       = '发起邮箱验证';
    const DESCRIPTION = '需要先请求图形验证码接口';
    const TAGS = "登录注册";
    const PARAMS      = [
        'email' => 'string(required) # 邮箱地址',
        'code' => 'int(required) # 图形验证码',
        'token' => 'string(required) # 图形验证码串',
    ];
    const SCHEMAS     = [
    ];


    public function run() {

        $validator = $this->validator->validate($this->request, [
            'email' => V::email()->noWhitespace()->length(6, 50)->setName($this->lang->text("email address")),
            //'token' => V::alnum()->noWhitespace()->length(32)->setName($this->lang->text("token code")),
            //'code' => V::intVal()->noWhitespace()->length(4)->setName($this->lang->text("captcha code")),
        ]);

        if (!$validator->isValid()) {
            return $validator;
        }

        $email = $this->request->getParam('email');
        $encryEmail  = \Utils\Utils::RSAEncrypt($email);
        if (\Model\User::where('email', $encryEmail)->count()) {
            return $this->lang->set(186);
        }
        $captcha = new \Logic\Captcha\Captcha($this->ci);
        //if ($captcha->validateImageCode($this->request->getParam('token'), $this->request->getParam('code'))) {
            return $captcha->registerSendTextCodeByEmail($email);
        //}
        //return $this->lang->set(108);
    }
};
