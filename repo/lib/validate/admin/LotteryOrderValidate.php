<?php
/**
 * author      : ben <956841620@qq.com>
 * createTime  : 2018/4/09 23:25
 * description :
 */

namespace lib\validate\admin;

use lib\validate\BaseValidate;
class LotteryOrderValidate extends BaseValidate
{
    // 验证规则
    protected $rule = [
        "chase_number"=>"require|integer|max:19",

    ];
    protected $field = [
        'chase_number'   =>    '订单号',

    ];

    protected $message = [
//        'name.require' => '标题不能为空',
//        'name.unique' => '标题已经存在',


    ];

    protected $scene = [
        'put' => [
            'chase_number'
        ],

    ];


}