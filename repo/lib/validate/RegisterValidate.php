<?php
/**
 * author      : ben <956841620@qq.com>
 * createTime  : 2018/4/09 23:25
 * description :
 */

namespace lib\validate;

class RegisterValidate extends BaseValidate
{
    // 验证规则
    protected $rule = [
        "user_name"=>"require|length:6,16|unique:User,name|checkUserName",
        "password"=>"require|length:6,16|checkPassword",
        "repassword"=>"require|confirm:password",
        "bkge_sport"=>"require|integer|egt:0",
        "bkge_live"=>"require|integer|egt:0",
        "bkge_game"=>"require|integer|egt:0",
        "bkge_lottery"=>"require|integer|egt:0",

    ];
    protected $field = [
        'user_name'   =>    '用户名',
        'password'   =>    '密码',
    ];

    protected $message = [
        'repassword.require' => '请重复输入密码',
        'repassword.confirm' => '两次输入密码不一致',
//        'name.unique' => '标题已经存在',


    ];

    protected $scene = [
        'adminput' => [
            'user_name', 'password'
        ],

    ];

    protected function checkUserName($value){

        if(preg_match("/[ '.,:;*?~`!@#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$value)){
            return '账号不能含有标点符号及特殊字符';
        }

        if(preg_match('/[\x7f-\xff]/', $value)){
            return '账号不能包含中文字符';
        }
        return true;

    }

    protected function checkPassword($value)
    {
        if(preg_match("/[ '.,:;*?~`!@#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$value)){
            return '密码不能含有标点符号及特殊字符';
        }
        return true;
    }


}