<?php
/**
 * author      : ben <956841620@qq.com>
 * createTime  : 2018/4/09 23:25
 * description :
 */

namespace lib\validate\admin;

use lib\validate\BaseValidate;
class ProxyDocValidate extends BaseValidate
{
    // 验证规则
    protected $rule = [
        "content"=>"require|max:65,535",
        "name"=>"require|in:赚取佣金,如何运作",
        "pf"=>"require|in:pc,h5",

    ];
    protected $field = [
        'name'      => '标题 ',
        'content'   =>    '内容',
        'pf'   =>    '平台选择',

    ];

    protected $message = [

    ];

    protected $scene = [
        'put' => [
            'name', 'content', 'pf',

        ],

    ];



}