<?php
use Utils\Www\Action;
use Respect\Validation\Validator as V;
return new class extends Action
{
    const TOKEN = true;
    const TITLE = "安全中心-修改取款密码";
    const TAGS = "安全中心";
    const PARAMS = [
       "password"       => "string(required) #旧密码",
       "new_password"   => "string(required) #新密码"
   ];
    const SCHEMAS = [
   ];

    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $validator = $this->validator->validate($this->request, [
            'password' => V::numeric()->length(4,4)->noWhitespace()->setName($this->lang->text("old password")),
            'new_password' => V::numeric()->length(4,4)->noWhitespace()->setName($this->lang->text("new password")),
        ]);

        if (!$validator->isValid()) {
            return $validator;
        }

        $password = $this->request->getParam('password');
        $newPassword = $this->request->getParam('new_password');
        $userId = $this->auth->getUserId();

        $user = \Model\User::where('id', $userId)->first();
        $funds = \Model\Funds::where('id', $user['wallet_id'])->first();

        $key = \Logic\Define\CacheKey::$perfix['fundsPass'].$userId;
        $num = $this->redis->incr($key);
        $this->redis->expire($key, 86400);
        if (\Model\User::getPasword($password, $funds['salt']) != $funds['password']) {
            return $this->lang->set(154);
        }

        if ($num > 3) {
            return $this->lang->set(155);
        }

        $salt = \Model\User::getGenerateChar(6);
        $newPassword = \Model\User::getPasword($newPassword, $salt);
        \Model\Funds::where('id', $user['wallet_id'])->update([
            'password' => $newPassword,
            'salt' => $salt,
        ]);

        // 清空错误次数限制
        $this->redis->set($key, 0);
        return $this->lang->set(0);
    }
};