<?php
use Utils\Www\Action;
use Respect\Validation\Validator as V;
use Logic\Define\Lang;
return new class extends Action
{
    const TITLE = '忘记登录密码 - 设置新密码';
    const TAGS = "登录注册";
    const PARAMS = [
        "name" => "string(required) #用户账号或手机号",
        'password'  => 'string(required) #密码-新设置的密码',
    ];
    const SCHEMAS = [];

    public function run() {
        $validator = $this->validator->validate($this->request, [
            'name' => V::username()->setName($this->lang->text("username")),
            'password' => V::password()->setName($this->lang->text("password")),
        ]);

        if (!$validator->isValid()) {
            return $validator;
        }
        $name = $this->request->getParam('name');
        $name_mobile =\Utils\Utils::RSAEncrypt($name);
        $user = \Model\User::where('name', $name)->orWhere('mobile', $name_mobile)->first();

        // put resetpwd记录状态
        $a = $this->redis->get(\Logic\Define\CacheKey::$perfix['userSafety'].'_9_'.$user['id']);

        // verfiy 手机或者邮箱通过状态
        $b = $this->redis->get(\Logic\Define\CacheKey::$perfix['userSafety'].'_3_'.$user['id']);
        $c = $this->redis->get(\Logic\Define\CacheKey::$perfix['userSafety'].'_2_'.$user['id']);

        if (empty($user)) {
            return $this->lang->set(86);
        }

        if (empty($a) && (empty($b) && empty($c))) {
            return $this->lang->set(173);
        }

        $salt = \Model\User::getGenerateChar(6);
        $password = \Model\User::getPasword($this->request->getParam('password'), $salt);
        \Model\User::where('id', $user['id'])->update(['password' => $password, 'salt' => $salt]);
        return $this->lang->set(174);
    }
};