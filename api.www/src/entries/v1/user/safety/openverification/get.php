<?php
use Utils\Www\Action;
use Model\Bank;
return new class extends Action
{
    const TOKEN = true;
    const TITLE = "安全中心-开启密保";
    const TAGS = "安全中心";
    const SCHEMAS = [
        "type" => "int(required) #类型(1:密保验证, 2:登录密码验证)",
        "list" => [
            "mobile" => [
                "id"     => "int(required) #id",
                "status" => "int(required) #手机号（1：有，0：没有）",
                "value"  => "string(required) #显示 ****6150",
            ],
            "email" => [
                "id"     => "int(required) #id",
                "status" => "int(required) #邮箱（1：有，0：没有）",
                "value"  => "string(required) #显示 *****23@email.com",
            ],
            "question" => [
                "id"     => "int(required) #id",
                "status" => "int(required) #安全问题（1：有，0：没有）",
                "value"  => "string(required) #显示值 我的出生日期?",
            ],
        ],
   ];


    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $safety = new \Logic\User\Safety($this->ci);
        $info = $safety->getTools($this->auth->getUserId());
        if (empty($info)) {
            return ['type' => 2];
        } else {
            return array_merge(["type" => 1], ["list" => $info]);
        }
    }
};