<?php
/**
 * author      : ben <956841620@qq.com>
 * createTime  : 2018/4/09 23:25
 * description :
 */

namespace lib\validate\admin;

use lib\validate\BaseValidate;
class ActiveValidate extends BaseValidate
{
    // 验证规则
    protected $rule = [
//        "name" => "require",
        "title"=>"require|max:200",
//        "rule"=>"require|in:1,2,3",
        "send_type"=>"require|in:1,2,3",
//        "send_max"=>"require|in:1,2,3",
        "withdraw_require_val"=>"require|in:1,2,3",
        "status"=>"require|in:enabled,disabled,deleted",
        "sort"=>"require|in:1,2,3",
        "cover"=>"require|in:1,2,3",
        "description"=>"require|in:1,2,3",
//        "issue_mode"=>"require|in:1,2,3",
//        "template_id"=>"require|in:1,2,3",
        "vender_type"=>"require|in:1,2,3",
        "bind_info"=>"require|in:1,2,3",
        "begin_time"=>"require|dateFormat:Y-m-d",
        "end_time"=>"require|dateFormat:Y-m-d|checkTime",

    ];
    protected $field = [
        'content'   =>    '内容',
        'title'   =>    '活动标题',
        'name'   =>    '活动名称',
        'send_type'   =>    '发送类型',
        'recipient'   =>    '接收人',
        'description'   =>    '活动描述',
        'cover'   =>    '活动图片',
        'status'   =>    '状态',
        'sort'   =>    '排序',
        'begin_time'   =>    '开始时间',
        'end_time'   =>    '结束时间',
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