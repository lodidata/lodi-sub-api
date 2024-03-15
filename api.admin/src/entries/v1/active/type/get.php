<?php

use Logic\Admin\Active as activeLogic;
//use Model\Admin\Active;
use Logic\Admin\BaseController;

return new class() extends BaseController
{
//    const STATE       = \API::DEPRECATED;
    const TITLE = '活动类型列表';

    const QUERY = [
    ];
    const PARAMS = [

    ];
    const SCHEMAS = [
        [
            "id" => "活动类型id",
            "name" => "类型名称",
            "description" => "描述",
            "sort" => "排序"
        ]
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run()
    {
        $data = DB::connection("slave")->table('active_type')
            ->select(['id', 'name', 'image','description', 'sort','status'])
            ->orderBy('sort', 'asc')
            ->get()
            ->toArray();
        if(!empty($data)){
            foreach ($data as &$val){
                $val->image = showImageUrl($val->image);
            }
        }
        return $data;

    }
};
