<?php
use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '获取相关信息';
    const DESCRIPTION = '';
    
    const QUERY       = [];
    
    const PARAMS      = [];
    const SCHEMAS     = [
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];
    public function run()
    {
        $app_config= (array)\DB::table('app_kf')->first();
        return $app_config;
    }
};
