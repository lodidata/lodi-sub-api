<?php
/**
 * author      : ben <956841620@qq.com>
 * createTime  : 2018/4/09 23:25
 * description :
 */

namespace lib\validate\admin;

use lib\validate\BaseValidate;
class MessageValidate extends BaseValidate
{
    // 验证规则
    protected $rule = [
        "message_type"      => "in:1,2",
        "desc"=>"require",
        "title"=>"require|max:255",
        "send_type"=>"require|in:1,2,3",
        "send_type_value"=>"requireIf:send_type,1,3",

    ];
    protected $field = [

        'message_type'      => '消息类型 ',
        'desc'   =>    '内容',
        'title'   =>    '标题',
        'send_type'   =>    '发送对象',
        'send_type_value'   =>    '接收人',

    ];

    protected $message = [

    ];

    protected $scene = [
        'post' => [
            'message_type', 'desc', 'title', 'send_type', 'send_type_value',

        ],

    ];


}