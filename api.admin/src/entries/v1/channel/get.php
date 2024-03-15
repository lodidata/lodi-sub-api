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
        $stime = $this->request->getParam('start_time','');
        $etime = $this->request->getParam('end_time','');
        $name = $this->request->getParam('name', '');
        $page = $this->request->getParam('page', 1);
        $page_size = $this->request->getParam('page_size', 10);

        $subQuery = \DB::table('channel_management')->orderBy('id','desc');
        if(!empty($stime)){
            $subQuery = $subQuery->where('created', '>=', $stime.' 00:00:00');
        }
        if(!empty($etime)){
            $subQuery = $subQuery->where('created', '<=', $etime.' 23:59:59');
        }
        if(!empty($name)){
            $subQuery = $subQuery->where(function($query) use($name){
                $query->where('number', 'like', '%'.$name.'%')->orWhere('name', 'like', '%'.$name.'%');
            });
        }
        $attr['total'] = $subQuery->count();
        $data = $subQuery->orderBy('id','desc')->forPage($page, $page_size)->get()->toArray();

        //获取系统设置推广地址
        $config = \DB::table('system_config')->where('key','h5_url')->first();
        $h5_url = '';
        if(!empty($config)){
            $h5_url = $config->value;
        }
        foreach($data as &$val){
            $val = (array)$val;
            if(empty($val['url'])){
                $val['url'] = $h5_url;
            }
        }

        $attr['num'] = $page;
        $attr['size'] = $page_size;
        $attr['url'] = $h5_url;

        return $this->lang->set(0,[],$data,$attr);
    }
};
