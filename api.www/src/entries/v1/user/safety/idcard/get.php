<?php
use Utils\Www\Action;
use Model\Bank;
return new class extends Action
{
    const TOKEN = true;
    const TITLE = "会员安全中心-获取真实姓名";
    const TAGS = "安全中心";
    const SCHEMAS = [
        "name" => "string() #真实姓名"
   ];


    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $profile = \Model\Profile::where('user_id', $this->auth->getUserId())->first();
        return ['name' => \Utils\Utils::RSADecrypt($profile['name'])];
    }
};