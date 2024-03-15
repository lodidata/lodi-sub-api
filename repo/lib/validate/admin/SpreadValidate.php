<?php

namespace lib\validate\admin;

use lib\validate\BaseValidate;

class SpreadValidate extends BaseValidate {

    // 验证规则
    protected $rule = [
        'name'    => 'require',
        'sort'    => 'require|integer',
        'status'  => 'require|in:enabled,disabled,deleted',
        'picture' => 'require|url',
    ];

    protected $field = [
        'name'    => '标题',
        'sort'    => '排序',
        'picture' => '图片',
        'status'  => '状态',
    ];
}