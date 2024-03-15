<?php
/**
 * author      : ben <956841620@qq.com>
 * createTime  : 2018/4/09 23:25
 * description :
 */

namespace lib\validate\admin;

use lib\validate\BaseValidate;
class NoticeValidate extends BaseValidate
{
    // 验证规则
    protected $rule = [
        "content"=>"max:2000",
        "title"=>"require|max:150",
        "popup_type"=>"require|in:1,2,3,4",
        "send_type"=>"require|in:1,2,3",
        "recipient"=>"requireIf:send_type,3|max:250",
        "start_time"=>"require|dateFormat:Y-m-d",
        "end_time"=>"require|dateFormat:Y-m-d|checkTime",
    ];
    protected $field = [
        'content'   =>    '内容',
        'title'   =>    '标题',
        'popup_type'   =>    '弹出类型',
        'send_type'   =>    '发送类型',
        'recipient'   =>    '接收人',
        'start_time'   =>    '生效时间',
        'end_time'   =>    '截止时间',
    ];

    protected $message = [
        'end_time.checkTime' => '截止时间不能小于生效时间',
    ];

    protected $scene = [
        'post' => [
            'content', 'title', 'popup_type', 'send_type',
            'recipient', 'start_time', 'end_time'
        ],

    ];

    protected function checkTime($value,$rule,$data){

        return strtotime($value) >= strtotime($data['start_time']);
    }


}