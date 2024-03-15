<?php
use Utils\Www\Action;
use lib\validate\UserValidate;
use Respect\Validation\Validator as V;

return new class extends Action {
    const TOKEN = true;
    const TITLE = "安全中心-修改登录密码";
    const DESCRIPTION = "返回状态 1002 成功，并退出登录";
    const TAGS = "安全中心";
    const PARAMS = [
        'password_old' => "string(required) #旧密码",
        'password_new' => "string(required) #新密码",
        'repassword_new' => "string(required) #确认新密码"
    ];

    public function run()
    {

        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        // (new UserValidate())->paramsCheck('loginpwd',$this->request,$this->response);

        $params = $this->request->getParams();

        $password_new = $params['password_new'];
        $repassword_new = $params['repassword_new'];

        if ($password_new != $repassword_new) {
            return $this->lang->set(4003, [], []);
        }

        $validate_params = compact(['password_new', 'repassword_new']);
        $validate_rules = [
            'password_new' => V::password()
                ->setName($this->lang->text("password")),
            'repassword_new' => V::password()
                ->setName($this->lang->text("password")),
        ];
        $validator = $this->validator->validate($validate_params, $validate_rules);

        if (!$validator->isValid()) {
            return $validator;
        }


        $userId = $this->auth->getUserId();

        $user = \Model\User::where('id', $userId)->first();

        if ($user->password != md5(md5($params['password_old']) . $user['salt']))
            return $this->lang->set(1001);

        $password_new = md5(md5($params['password_new']) . $user['salt']);
        $res = \Model\User::where('id', $userId)->update(['password' => $password_new]);

        if ($res === false)
            return $this->lang->set(-2);

        $this->auth->logout($userId);
        return $this->lang->set(1002);
    }
};