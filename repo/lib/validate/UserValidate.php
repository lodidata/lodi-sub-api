<?php
/**
 * author      : ben <956841620@qq.com>
 * createTime  : 2018/4/09 23:25
 * description :
 */

namespace lib\validate;

class UserValidate extends BaseValidate
{
    // 验证规则
    protected $rule = [
        "password_old"=>"require|length:6,16|checkPassword",
        "password_new"=>"require|length:6,16|checkPassword",
        "repassword_new"=>"require|confirm:password_new",


    ];
    protected $field = [
        'password_old' =>  '旧密码',
        'password_new'     =>    '新密码',
    ];

    protected $message = [
        'repassword_new.require' => '请重复输入新密码',
        'repassword_new.confirm' => '两次输入密码不一致',


    ];

    protected $scene = [
        'loginpwd' => [
            'password_old', 'password_new','repassword_new'
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