<?php
/**
 * author      : ben <956841620@qq.com>
 * createTime  : 2018/4/09 23:25
 * description :
 */

namespace lib\validate\admin;

use lib\validate\BaseValidate;
class DepositValidate extends BaseValidate
{
    // 验证规则
    protected $rule = [
        "id"=>"require|isPositiveInteger",
        "type" => "require|in:increase,decrease",
        "amount" => "require|egt:1|isPositiveInteger",//金额
        "memo" => "max:50",//金额


    ];
    protected $field = [
        "type"  => "改动方式",
        "amount"=>"金额",
        "memo"=>"备注",

    ];

    protected $message = [
        'amount.egt' => '金额必须大于等于0.01',
//        'name.unique' => '标题已经存在',


    ];

    protected $scene = [
        'freemoney' => [
            'type','amount'
        ],

    ];


}