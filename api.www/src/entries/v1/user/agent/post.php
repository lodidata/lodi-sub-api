<?php
use Utils\Www\Action;
use Respect\Validation\Validator as V;
return new class extends Action
{
    const TOKEN = true;
    const TITLE = "注册新会员成为下级代理";
    const DESCRIPTION = "注册新会员成为下级代理";
    const TAGS = "代理返佣";
    const PARAMS = [
       "user_name"  => "string(required) #用户名",
       "password"   => "string(required) #密码",
        "rake_back" => "string(required) #代理退佣率 JSON格式"
   ];
    const SCHEMAS = [
   ];

    
    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $validator = $this->validator->validate($this->request, [
            'user_name' => V::username(),
            'password' => V::password(),
        ]);

        if (!$validator->isValid()) {
            return $validator;
        }
        $userId = $this->auth->getUserId();
        $user_name = $this->request->getParam('user_name');
        $password = $this->request->getParam('password');
        $junior = $this->request->getParam('rake_back');
        $bkge_json = \Model\UserAgent::where('user_id', $userId)->value('bkge_json');
        $bkge = json_decode($bkge_json,true);
        $user_bkge = [];
        foreach ($bkge as $key => $val) {
            if(isset($junior[$key])) {
                if($junior[$key] > $val) {
                    return $this->lang->set(551);
                }
                $user_bkge[$key] = $junior[$key];
            }
        }
        $code = \Model\UserAgent::where('user_id',$userId)->value('code');
        $user = new \Logic\User\User($this->ci);
        return $user->register($user_name, $password, $code,json_encode($user_bkge));
    }
};