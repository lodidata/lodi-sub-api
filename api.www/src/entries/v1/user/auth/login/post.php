<?php
use Utils\Www\Action;
use Respect\Validation\Validator as V;

return new class extends Action
{
    const TITLE = "用户登录";
    const DESCRIPTION = "根据用户名密码验证码申请TOKEN，成功返回Token及用户uuid";
    const TAGS = "登录注册";
    const PARAMS = [
       "name"       => "string(required) #用户账号",
       "password"   => "string(required) #密码",
       "token"      => "string(optional) #图片验证码token (密码输入错误后需要图片验证码)",
       "code"       => "string(optional) #图片验证码 (密码输入错误后需要图片验证码)",
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
        $validator = $this->validator->validate($this->request, [
            'name' => V::username(),
            'password' => V::password(),
        ]);
        $token = $this->request->getParam('token');
        $code  = $this->request->getParam('code');
        $uuid = $this->request->getParam('uuid');
        $login_type = $this->request->getParam('login_type',0);
        if ($validator->isValid()) {
            $lang = $this->auth->login($this->request->getParam('name'), $this->request->getParam('password'), $login_type, $token, $code, false, $uuid);

            return $lang;
            
        } else {
            return $validator;
        }
    }
};