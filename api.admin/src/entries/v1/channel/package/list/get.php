<?php

use Logic\Admin\BaseController;

return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '渠道列表';
    const DESCRIPTION = '渠道列表';
    
    const QUERY       = [
        'page'       => 'int(required)   #页码',
        'page_size'  => 'int(required)    #每页大小',
    ];
    
    const PARAMS      = [];
    const SCHEMAS     = [];
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];
    public function run()
    {
        $page = $this->request->getParam('page', 1);
        $page_size = $this->request->getParam('page_size', 10);

        $query = \DB::table('channel_management');
        $attr['total']=$query->count();
        $data=$query->orderBy('id','desc')->forPage($page,$page_size)->get(['number as channel_id','name'])->toArray();


        $attr['num'] = $page;
        $attr['size'] = $page_size;

        return $this->lang->set(0,[],$data,$attr);
    }
};
