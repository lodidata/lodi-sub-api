<?php
use Utils\Www\Action;
use Respect\Validation\Validator as V;
return new class extends Action
{
    const TITLE = "会员注册-校验邮箱是否已注册";
    const TAGS = "登录注册";
    const QUERY = [
       "email"       => "string(required) #邮箱",
   ];
    const SCHEMAS = [
   ];


    public function run() {

        $validator = $this->validator->validate($this->request, [
            'email' => V::email()->noWhitespace()->length(6, 50)->setName($this->lang->text("email address")),
        ]);
        if (!$validator->isValid()) {
            return $validator;
        }
        $email = $this->request->getQueryParam('email');
        $emailEn = Utils\Utils::RSAEncrypt($email);
        if(\Model\User::where('email', $emailEn)->count() > 0){
            return $this->lang->set(186);
        }

        return [];
    }
};