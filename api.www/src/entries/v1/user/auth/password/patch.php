<?php
use Utils\Www\Action;
use Respect\Validation\Validator as V;
use Model\User;
return new class extends Action {
    const TOKEN = true;
    const TITLE = "重新设置登录密码";
    const TAGS = "登录注册";
    const PARAMS = [
       "new_pwd"  => "string(required) #新密码",
       "password" => "string() #旧密码 根据user表中tp_password_initial的值，1是，0用户已修改过，如果修改过则必须传旧密码"
   ];
    const SCHEMAS = [
   ];


    public function run() {

        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $validator = $this->validator->validate($this->request, [
            'new_pwd' => V::password()->setName($this->lang->text("new password")),
            'password' => V::password()->setName($this->lang->text("login password")),
        ]);

        if (!$validator->isValid()) {
            return $validator;
        }

        $user = User::where('id', $this->auth->getUserId())->first();

        // 判断是否需要校验旧密码
        $needVerifyOld = $user['tp_password_initial'] ? false : true;

        // 校验旧密码
        if ($needVerifyOld && !$this->auth->verifyPass($user['password'], $this->request->getParam('password'), $user['salt'])) {
            return $this->lang->set(113);
        }

        $password = $this->request->getParam('new_pwd');
        $salt = User::getGenerateChar(6);
        $password = User::getPasword($password, $salt);
        User::where('id', $this->auth->getUserId())->update(['password' => $password, 'salt' => $salt]);
        // 使用create 触发模型事件
        // User::create(['id' => $this->auth->getUserId(), 'password' => $this->request->getParam('new_pwd')]);

        // 退出登录
        $this->auth->logout($this->auth->getUserId());
        
        return $this->lang->set(114);
    }
};