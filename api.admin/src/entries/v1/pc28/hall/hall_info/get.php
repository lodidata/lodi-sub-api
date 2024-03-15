<?php

use Logic\Admin\BaseController;
return new class() extends BaseController
{
//    const STATE       = \API::DEPRECATED;
    const TITLE       = '厅设置列表';
    const DESCRIPTION = '接口';
    

    const QUERY       = [
    ];
    const SCHEMAS     = [

    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run()
    {
        $params = $this->request->getParams();
        $sql = "select h.*,l.name as lottery_name from hall h left join lottery l on h.lottery_id = l.id where h.type = '{$params['type']}' ";
        if(isset($params['id']) && $params['id']){
            $sql = $sql."  and h.id = {$params['id']}" ;
        }
        $data = DB::select($sql);
        foreach($data as $k=>$v){
            $sql = "select id,room_name,number from room where hall_id = {$v->id}";
            $roomData =  DB::select($sql);
            $data[$k]->room = $roomData;
        }
        return $data;
    }

};
