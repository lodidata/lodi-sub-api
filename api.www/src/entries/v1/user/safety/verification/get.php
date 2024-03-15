<?php
use Utils\Www\Action;
use Respect\Validation\Validator as V;
return new class extends Action {
    const TITLE = "GET 会员安全中心  安全验证";
    const TAGS = "安全中心";
    const SCHEMAS = [
        "list" => [
            "activepwd" => [
                "id"     => "int(required) #id",
                "status" => "int(required) #动态密码（1：有，0：没有）",
                "value"  => "string(required) #显示在页面",
            ],
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
        return json_decode(file_get_contents(__DIR__.'/get.json'));
    }
};