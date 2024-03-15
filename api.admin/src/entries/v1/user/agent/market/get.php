<?php
use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '代理推广域名';
    const DESCRIPTION = '';
    
    const QUERY       = [
        'user_name' => 'string(, 30)',
        'url' => 'string(, 30)',
        'start_time'         => 'string()   #页码',
        'end_time'    => 'string()    #每页大小',
        'page'         => 'int()   #页码',
        'page_size'    => 'int()    #每页大小',
    ];
    
    const PARAMS      = [];
    const SCHEMAS     = [
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];
    public function run()
    {
        $user_name = $this->request->getParam('user_name');
        $url = $this->request->getParam('url');
        $stime = $this->request->getParam('start_time');
        $etime = $this->request->getParam('end_time');
        $page = $this->request->getParam('page',1);
        $page_size = $this->request->getParam('page_size',20);

        $query= \DB::table('user_agent_market');
        $user_name && $query->where('user_name','=',$user_name);
        $stime && $query->where('updated','>=',$stime);
        $etime && $query->where('updated','<=',$etime);
        $url && $query->where(function($q)use($url){
            $q->where('h5_url','=',$url)->orWhere('pc_url','=',$url);
        });
        $t = clone $query;
        $query->forPage($page,$page_size);
        $total = $t->count();
        $data = $query->get([
                'id',
                'user_name',
                'pc_url',
                'h5_url',
                'updated',
                'created',
            ])->toArray();
        $attr = [
            'number' => $page,
            'size' => $page_size,
            'total' => $total,
        ];
        return $this->lang->set(0,[],$data,$attr);
    }
};
