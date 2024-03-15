<?php
/**
 * author      : ben <956841620@qq.com>
 * createTime  : 2018/4/09 23:25
 * description :
 */

namespace lib\validate\admin;

use lib\validate\BaseValidate;
class TopConfigValidate extends BaseValidate
{
    // 验证规则
    protected $rule = [
        "download"      => "require|in:0,1",
        "title"         => "require|max:30",
        "logo_img"      => "require|url",

    ];
    protected $field = [
        'description' => '文字描述',
        'title '      => '按钮文本',
        'download'    => '下载开关',
        'logo_img'    => '图片地址',
    ];

    protected $message = [
//        'name.require' => '标题不能为空',
//        'name.unique' => '标题已经存在',
    ];

    protected $scene = [

    ];


}