<?php
use Utils\Www\Action;
use Respect\Validation\Validator as V;
return new class extends Action
{
    const TITLE = "会员注册-校验账号是否已注册";
    const TAGS = "登录注册";
    const QUERY = [
       "user_name"       => "string(required) #用户账号",
   ];
    const SCHEMAS = [
   ];


    public function run() {

        $user_name = trim($this->request->getQueryParam('user_name'));
        if(empty($user_name)){
            $this->lang->set(10);
        }
        if(\Model\User::where('name', $user_name)->count() > 0){
            return $this->lang->set(4002);
        }

        return [];
    }
};