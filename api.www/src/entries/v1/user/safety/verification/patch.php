<?php
use Utils\Www\Action;
use Respect\Validation\Validator as V;
return new class extends Action
{
    const TOKEN = true;
    const TITLE = "安全中心-安全验证";
    const TAGS = "安全中心";
    const PARAMS = [
       "id"         => "int(required) #id(1：动态密码，2：手机号，3：邮箱，4：安全问题)",
       "username"   => "string(required) #用户账号 [忘记登录密码时传]",
       "value"      => "string(required) #值"
   ];
    const SCHEMAS = [
   ];


    public function run() {
        $id = $this->request->getParam('id');
        $username = $this->request->getParam('username');
        $value = $this->request->getParam('value');
        if (empty($username)) {
            $verify = $this->auth->verfiyToken();
            if (!$verify->allowNext()) {
                return $verify;
            }
            $userId = $this->auth->getUserId();
        } else {
            $user = \Model\User::where('name', $username)->first();
            if (empty($user)) {
                return $this->lang->set(86);
            }
            $userId = $user['id'];
        }
        $user = new \Logic\User\User($this->ci);
        return $user->verification($userId, $id, $value, $username);
    }
};