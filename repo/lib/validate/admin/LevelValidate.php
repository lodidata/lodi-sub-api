<?php
/**
 * author      : ben <956841620@qq.com>
 * createTime  : 2018/4/09 23:25
 * description :
 */

namespace lib\validate\admin;

use lib\validate\BaseValidate;
class LevelValidate extends BaseValidate
{
    // 验证规则
    protected $rule = [
//        {"name":"testv2","memo":"testv2","deposit_min":"10000","deposit_times":"1","use_time_min":"6"}
        "id"=>"require|isPositiveInteger",
        "name"=>"require|max:50|unique:level,name",
        "deposit_min"=>"require|integer|>=:0",                    //存款总额
        "deposit_times"=>"require|integer|>=:0",                 //存款次数
        "use_time_min"=>"require|integer|>=:0",                 //账号使用时间
        "offline_dml_percent"=>"require|between:0,100",//线下入款打码量倍数
        "online_dml_percent"=>"require|between:0,100",//线上入款打码量倍
        "memo"=>"max:255",

    ];
    protected $field = [
        "name"=>"层级名称",
        "deposit_min"=>"存款总额",                    //存款总额
        "deposit_times"=>"存款次数",                 //存款次数
        "use_time_min"=>"账号使用时间",                 //账号使用时间
        "offline_dml_percent"=>"线下入款打码量倍数",//线下入款打码量倍数
        "online_dml_percent"=>"线上入款打码量倍",//线上入款打码量倍
        "memo"=>"描述",
    ];

    protected $message = [
//        'name.require' => '标题不能为空',
//        'name.unique' => '标题已经存在',


    ];

    protected $scene = [
        'put' => [
            'name'=>"require|max:50|unique:level,name^id",
            'deposit_min', 'deposit_times', 'use_time_min', 'offline_dml_percent','online_dml_percent','memo'
        ],

        'post' => [
            'name', 'deposit_min', 'deposit_times', 'use_time_min', 'offline_dml_percent','online_dml_percent','memo'
        ],
    ];


}