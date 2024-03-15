<?php
use Utils\Www\Action;

return new class extends Action {
    const TITLE = "会员注册-返回所需的字段";
    const DESCRIPTION = "返回会员注册所需的字段";
    const TAGS = "登录注册";
    const SCHEMAS = [
        "user_name"     => "string(required) #用户名称",
        "password"      => "string(required) #密码",
        "true_name"     => "string() #姓名",
        "birth"         => "int() #出生日期",
        "gender"        => "enum[1,2,3]() #性别 (1:男,2:女,3:保密)",
        "email"         => "string() #电子邮箱地址",
        "city"          => "int() #城市",
        "address"       => "string() #详细地址",
        "telphone_code" => "string() #电话国际区号",
        "mobile"        => "string() #联系电话",
        "postcode"      => "string() #邮编",
        "nationality"   => "int() #国籍",
        "birth_place"   => "int() #出生地",
        "currency"      => "int() #货币",
        "language"      => "int() #语言",
        "first_account" => "int() #首选账户",
        "invit_code"    => "string() #推荐码",
        "withdraw_pwd"  => "string() #提现密码",
        "question"      => "int() #安全问题id",
        "answer"        => "string() #安全问题答案",
        "verify"        => "string() #验证码",
        "token"         => "string() #验证码token",
   ];


    public function run() {
        return array_values(\Logic\Set\SystemConfig::getModuleSystemConfig('registerCon'));
    }

};