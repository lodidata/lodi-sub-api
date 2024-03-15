<?php

use Logic\Admin\BaseController;

return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '获取社区论坛信息';
    const DESCRIPTION = '获取社区论坛信息';
    
    const QUERY       = [
    ];
    
    const PARAMS      = [
        'id' => 'int #社区ID',
    ];
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
        $data = \Model\Admin\AdminUser::select('id','username')->get()->toArray();

        $attributes = [];
        return $this->lang->set(0,[],$data,$attributes);
    }
};
