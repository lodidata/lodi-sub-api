<?php

use Logic\Admin\BaseController;

return new class() extends BaseController
{
    const TITLE       = '语言列表';
    const DESCRIPTION = '{id:1
name:"简体中文"
code:"zh-cn"
area:null
country:"中国"
pic:"/assets/index/flag/CN.png"
hot:0
sort:0
status:1
created_at:"2021-06-18 16:15:47"
updated_at:"2021-06-18 16:15:47"
cur:"CNY"}';
    
    const QUERY       = [];
    
    const PARAMS      = [];
    const SCHEMAS     = [
        [
            'id'        => "int(required) #ID",
            'name'      => "string(required) #语言名称",
            'code'      => "string(required) #语言代码",
            'cur'       => "string(required) #货币代码",
            'sort'      => "int(required) #排序 从大到小",
            "status"    => "int(required, 1) #状态 1开启， 0停用",
            "created_at"=> "dateTime() #添加时间 2021-06-18 16:15:47",
            "updated_at"=> "dateTime() #更新时间 2021-06-18 16:15:47"
        ]
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];
    public function run()
    {
        $lang = \Model\Language::orderBy('sort','DESC')
            ->get()
            ->toArray();
        return $lang;
    }
};
