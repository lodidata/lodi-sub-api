<?php

use Logic\Admin\BaseController;

return new class() extends BaseController
{
    const TITLE       = '获取语言列表数组';
    const DESCRIPTION = '获取语言列表数组用于其他页面多语言选择\r\n [{id:1
name:"简体中文"
code:"zh-cn"
cur:"CNY"}]';
    const SCHEMAS     = [
        [
            'id'        => "int(required) #ID",
            'name'      => "string(required) #语言名称",
            'code'      => "string(required) #语言代码",
            'cur'       => "string(required) #货币代码"
        ]
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];
    public function run()
    {
        $lang = \Model\Language::orderBy('sort','DESC')
            ->get(['id,name,code,cur'])
            ->where('status' ,1)
            ->toArray();
        return $lang;
    }
};
