<?php

use Logic\Admin\BaseController;

return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '社区论坛列表';
    const DESCRIPTION = '社区论坛列表';
    
    const QUERY       = [
//        'page'       => 'int(required)   #页码',
//        'page_size'  => 'int(required)    #每页大小',
    ];
    
    const PARAMS      = [];
    const SCHEMAS     = [
        'id' => 'int #社区ID',
        'name' => 'string #社区名称',
        'icon' => 'string #图标地址',
        'jump_url' => 'string #跳转链接',
        'status' => 'int #启动状态 0启用 1停用',
        'created' => 'string #生成时间',
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];
    public function run()
    {
        $data = \DB::connection('slave')->table('community_bbs')->orderBy('id','desc')->get()->toArray();
        if(!empty($data)){
            foreach ($data as $key => &$val){
                $val->icon = showImageUrl($val->icon);
            }
        }
        unset($val);
        $attributes = [];
        return $this->lang->set(0,[],$data,$attributes);
    }
};
