<?php
/**
 * Created by PhpStorm.
 * User: ben
 * Date: 2018/4/16
 * Time: 17:00
 */

namespace lib\validate\admin;

use lib\validate\BaseValidate;
class SystemSetValidate extends BaseValidate
{
    // 验证规则
    protected $rule = [
        "user" => "require|checkUserRegSet",
        "agent" => "require|checkUserRegSet",

    ];
    protected $field = [

        'user' => '用户设置 ',
        'agent' => '代理设置',
        

    ];

    protected $message = [

    ];

    protected $scene = [
        'regset' => [
            'user', 'agent', 

        ],

    ];

    public function checkUserRegSet($value,$rule,$data){
        
        return is_array($value) ? true : '格式错误';
    }
}