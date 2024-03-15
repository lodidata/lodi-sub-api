<?php
use Utils\Www\Action;
use Model\User;

/**
 * 小米用户登录和注册接口
 */
return new class extends Action
{
    const HIDDEN = true;
    const TITLE = "小米用户登录和注册接口";
    const DESCRIPTION = "小米用户登录和注册接口 账号存在则登录，没用则注册 MI123456 分为前缀type:MI和后缀uid:123456，默认密码a123456";
    const TAGS = "登录注册";
    const PARAMS = [
        "uid"   => "string(required) #用户账号后缀 123456",
        "type"  => "string() #账号前缀 默认为MI 完整用户账号MI123456"
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
        $params = $this->request->getParams();

        if (empty($params['uid'])) {
            return $this->lang->set(886, [$this->lang->text("Login user uid cannot be empty")]);
        } else {
            $type = isset($params['type']) ? $params['type'] : 'MI';
            $username = $type[0].$params['uid'];
            $user = User::where('name', '=', $username)->first();
            if(empty($user)){//需要注册
                $user = new \Logic\User\User($this->ci);
                $user->register($username, 'a123456');//默认密码
                $user_id = $user->getUserId();
                if($user_id){
                    \DB::table('user')->where('id', $user_id)->update(['role' => 'rotot']);
                }
            }
            $lang = $this->auth->login($username, 'a123456');
            return $lang;
        }
    }
};