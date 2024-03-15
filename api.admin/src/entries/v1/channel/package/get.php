<?php

use Logic\Admin\BaseController;

return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '渠道代理包生成列表';
    const DESCRIPTION = '渠道代理部生成列表';
    
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
        $name = $this->request->getParam('name');
        $batch_no = $this->request->getParam('batch_no');
        $page = $this->request->getParam('page', 1);
        $page_size = $this->request->getParam('page_size', 10);

        $query = \DB::table('channel_package');
        if(!empty($name)){
            $query=$query->where('name',$name);
        }
        if(!empty($batch_no)){
            $query=$query->where('batch_no',$batch_no);
        }
        $query=$query->where('status','!=',3);
        $attr['total']=$query->count();
        $data=$query->orderBy('id','desc')->forPage($page,$page_size)->get()->toArray();
        if(!empty($data)){
            foreach($data as $val){
                $val->download_url=showImageUrl($val->download_url);
            }
        }

        $attr['num'] = $page;
        $attr['size'] = $page_size;

        return $this->lang->set(0,[],$data,$attr);
    }
};
