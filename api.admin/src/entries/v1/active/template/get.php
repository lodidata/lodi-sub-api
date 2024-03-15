<?php

use Logic\Admin\Active as activeLogic;
//use Model\Admin\Active;
use Logic\Admin\BaseController;

return new class() extends BaseController
{
//    const STATE       = \API::DEPRECATED;
    const TITLE = '活动模板列表';

    const QUERY = [
    ];
    const PARAMS = [

    ];
    const SCHEMAS = [
        [
            "id" => "模板id",
            "name" => "模板名称",
        ]
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run()
    {
        $data = DB::table('active_template')
            ->select(['id', 'name'])
            ->get()
            ->toArray();
        if ($data) {
            foreach ($data as $k=>$v) {
                if ($v->id == 12) {       //活动管理tab中不展示彩金活动，彩金活动已经移动到现金管理tab中
                    unset($data[$k]);
                }
            }
        }
        $data = array_values($data);
        return $data;

    }
};
