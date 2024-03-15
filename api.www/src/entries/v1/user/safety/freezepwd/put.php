<?php
use Utils\Www\Action;
use Respect\Validation\Validator as V;
return new class extends Action
{
    const TOKEN      = true;
    const TITLE = "安全中心-设置保险箱密码";
    const TAGS = "安全中心";
    const PARAMS = [
       "password" => "string(required) #保险箱密码"
   ];
    const SCHEMAS = [
   ];

    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $validator = $this->validator->validate($this->request, [
            'password' => V::intVal()->noWhitespace()->length(4)->setName($this->lang->text("Safe code")),
        ]);

        if (!$validator->isValid()) {
            return $validator;
        }

        $userId = $this->auth->getUserId();
        $password = $this->request->getParam('password');
        $user = \Model\User::where('id', $userId)->first();
        $freeze_password = password_hash($password,PASSWORD_DEFAULT);
        \Model\Funds::where('id', $user['wallet_id'])->update(['freeze_password' => $freeze_password]);
        \Model\SafeCenter::where('user_id', $userId)->update(['freeze_password' => 1]);
        return [];
    }
};