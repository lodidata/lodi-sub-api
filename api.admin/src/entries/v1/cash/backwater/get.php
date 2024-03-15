<?php
use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '返水审核列表';
    const DESCRIPTION = '';
    
    const QUERY       = [];
    
    const PARAMS      = [];
    const SCHEMAS     = [];

    public function run()
    {
        $page = $this->request->getParam('page', 1);
        $size = $this->request->getParam('page_size', 20);
        $type = $this->request->getParam('type');
        $status = $this->request->getParam('status');
        $stime = $this->request->getParam('start_time');
        $etime = $this->request->getParam('end_time');

        $query=DB::table('active_backwater as ab')->leftJoin('active as a','ab.active_id','=','a.id');
        $type && $query->where('type',$type);
        if(isset($status)){
            $query->where('ab.status',$status);
        }
        $stime && $query->where('create_time','>=',$stime);
        $etime && $query->where('create_time','<=',$etime);

        $count = clone $query;
        $attributes['total'] =  $count->count();
        $attributes['number'] = $page;
        $attributes['size'] = $size;
        $data=$query->forPage($page,$size)->orderBy('ab.id','desc')->get(['ab.*','a.name'])->toArray();


        return $this->lang->set(0,[],$data,$attributes);
    }
};
