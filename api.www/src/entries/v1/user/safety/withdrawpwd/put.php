<?php
use Utils\Www\Action;
use Respect\Validation\Validator as V;
return new class extends Action
{
    const TOKEN = true;
    const TITLE = "会员安全中心-设置取款密码";
    const TAGS = "安全中心";
    const PARAMS = [
       "password" => "string(required) #取款密码"
   ];
    const SCHEMAS = [
   ];

    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $validator = $this->validator->validate($this->request, [
            'password' => V::numeric()->noWhitespace()->length(4,4)->setName($this->lang->text("Withdrawal password")),
        ]);

        if (!$validator->isValid()) {
            return $validator;
        }

        $userId = $this->auth->getUserId();
        $password = $this->request->getParam('password');
        $res = $this->redis->get(\Logic\Define\CacheKey::$perfix['userSafety'].'_2_'.$userId);
        $user = \Model\User::where('id', $userId)->first();
        $funds = \Model\Funds::where('id', $user['wallet_id'])->first();

        if (!$res && !empty($funds['password'])) {
            return $this->lang->set(124);
        }

        $salt = \Model\User::getGenerateChar(6);
        \Model\Funds::where('id', $user['wallet_id'])->update(['password' => md5(md5($password).$salt), 'salt' => $salt]);
        \Model\SafeCenter::where('user_id', $userId)->update(['withdraw_password' => 1]);
        return [];
    }
};