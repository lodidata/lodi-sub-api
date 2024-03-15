<?php
use Utils\Www\Action;
use Model\User as UserModel;
return new class extends Action
{
    const TOKEN = true;
    const TITLE = "检测用户名是否存在";
    const TAGS = "登录注册";
    const SCHEMAS = [
        'exist'     => "string() # true：存在，false：不存在",
   ];


    public function run() {
        $name = trim($this->request->getParam('name'));
        $res  = UserModel::getAccountExist($name);
        return $this->lang->set(0, [], ['exist' => $res]);
    }

};