<?php
use Utils\Www\Action;
use Respect\Validation\Validator as V;

return new class extends Action
{
    const TOKEN      = true;
    const TITLE       = '安全中心-发起邮箱验证';
    const DESCRIPTION = '需要先请求图形验证码接口';
    const TAGS = "安全中心";
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
            'token' => V::alnum()->noWhitespace()->length(32)->setName($this->lang->text("token code")),
            'code' => V::intVal()->noWhitespace()->length(4)->setName($this->lang->text("captcha code")),
        ]);

        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $captcha = new \Logic\Captcha\Captcha($this->ci);
        if ($captcha->validateImageCode($this->request->getParam('token'), $this->request->getParam('code'))) {
            return $captcha->sendTextCodeByEmail($this->auth->getUserId(), $this->request->getParam('email'));
        }
        return $this->lang->set(105);
    }
};
