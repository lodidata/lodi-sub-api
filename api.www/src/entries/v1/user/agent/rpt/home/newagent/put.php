<?php

use lib\validate\RegisterValidate;
use Utils\Www\Action;

return new class extends Action{

    const TITLE = '首页代理-新增代理';
    const DESCRIPTION = '';

    const QUERY = [
        'id' => 'int #用户id'
    ];


    public function run(){
        $verify = $this->auth->verfiyToken();

        if (!$verify->allowNext()) {
            return $verify;
        }
        $userId = $this->auth->getUserId();
        $code=DB::table('user_agent')->where('user_id',$userId)->value('code');


        (new RegisterValidate())->paramsCheck('adminput',$this->request,$this->response);//注册参数校验

        $user_name    = $this->request->getParam('user_name');
        $password    = $this->request->getParam('password');

        $user = new \Logic\User\User($this->ci);
        $logs = new \Model\Admin\LogicModel();
        $logs->logs_type = '新增/添加';
        $logs->opt_desc = '用户名('.$user_name.')';
        $logs->setTarget(0,$user_name);
        $logs->log();
        return $user->register($user_name, $password, $code);
    }
};