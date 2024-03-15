<?php

use Logic\Admin\BaseController;

return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '渠道管理列表';
    const DESCRIPTION = '渠道管理列表';
    
    const QUERY       = [
//        'page'       => 'int(required)   #页码',
//        'page_size'  => 'int(required)    #每页大小',
    ];
    
    const PARAMS      = [];
    const SCHEMAS     = [];
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];
    public function run()
    {

        $list = \DB::table('channel_management')->orderBy('id','desc')->get()->toArray();

        $data = [];
        foreach($list as $val){
            $data[] = [
                'number' => $val->number,
                'name'   => $val->name,
            ];
        }

        $attr = [];

        return $this->lang->set(0,[],$data,$attr);
    }
};
