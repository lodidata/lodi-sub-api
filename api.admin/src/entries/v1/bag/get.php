<?php
use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '获取包信息';
    const DESCRIPTION = '';

    const QUERY       = [
        //'bound_id' => 'string(, 30)',
        //'name' => 'string(require, 50)',#APP Name,
        //'ver' => 'string(require,10)',#版本,
        //'status' => 'int()#审核不通过0，通过1',
        //'page'         => 'int()   #页码',
        //'page_size'    => 'int()    #每页大小',
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
        $page = $this->request->getParam('page',1);
        $page_size = $this->request->getParam('page_size',20);
        
        $query= \DB::table('app_package as bag')
            ->orderBy('id','DESC');

        $total = $query->count();
        $data = $query->forPage($page,$page_size)->get()->toArray();
        if($data){
            $bag_type = [
                1 => '安卓',
                2 => '苹果'
            ];
            foreach ($data as &$v){
                $v->type_name = $bag_type[$v->type];
                $v->bag_url = showImageUrl($v->bag_url);
                $v->icon_url = showImageUrl($v->icon_url);
            }
            unset($v);
        }

        $attr = [
            'number' => $page,
            'size' => $page_size,
            'total' => $total,
        ];
        return $this->lang->set(0,[],$data,$attr);
    }
};
