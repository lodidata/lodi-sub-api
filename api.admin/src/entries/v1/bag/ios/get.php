<?php
use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '获取IOS包信息';
    const DESCRIPTION = '';

    const QUERY       = [
        'bound_id' => 'string(, 30)',
        'name' => 'string(require, 50)',#APP Name,
        'ver' => 'string(require,10)',#版本,
        'status' => 'int()#审核不通过0，通过1',
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
        $bound_id = $this->request->getParam('bound_id');
        $name = $this->request->getParam('name');
        $status = $this->request->getParam('status');
        $page = $this->request->getParam('page',1);
        $page_size = $this->request->getParam('page_size',20);
        
        $query= \DB::table('app_bag as bag')
            ->leftJoin('admin_user as admin','bag.update_uid','=','admin.id')
            ->orderBy('id','DESC')
            ->where('delete','=',0)
            ->where('type','=',1);
        if(!is_null($status)) {
            $query->where('bag.status','=',$status);
        }

        $name && $query->where('bag.name','=',$name);
        $bound_id && $query->where('bound_id','=',$bound_id);
        $total = $query->count();
        $data = $query->forPage($page,$page_size)->get([
                'bag.id',
                'bag.bound_id',
                'bag.url as ver',
                'bag.name',
                'bag.status',
                'admin.username',
                'bag.update_date',
                'bag.create_date',
            ])->toArray();

        $attr = [
            'number' => $page,
            'size' => $page_size,
            'total' => $total,
        ];
        return $this->lang->set(0,[],$data,$attr);
    }
};
