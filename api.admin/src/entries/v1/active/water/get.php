<?php

use \Logic\Admin\BaseController;




return new class() extends BaseController{

    const TITLE = '返水活动游戏列表';

    const QUERY = [
    ];
    const PARAMS = [

    ];
    const SCHEMAS = [
        [
            "id" => "游戏id",
            "name" => "游戏名称",
            "rename" => "rename",
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
        $data = DB::table('game_menu')
                  ->select(['id', 'name', 'rename', 'sort'])
                  ->where('pid','=',0)
                  ->where('id','!=',23)
                  ->where('status','=','enabled')
                  ->where('switch','=','enabled')
                  ->orderBy('sort','asc')
                  ->get()
                  ->toArray();
        return $data;

    }
};