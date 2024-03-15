<?php

use Logic\Admin\BaseController;
use Illuminate\Database\Capsule\Manager as DB;
return new class() extends BaseController
{
//    const STATE       = \API::DEPRECATED;
    const TITLE       = '彩种排序';
    const DESCRIPTION = '接口';
    
    const QUERY       = [
    ];
    const SCHEMAS = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run()
    {

        $res = DB::table('lottery')
            ->where('pid','<>',0)
            ->whereRaw("find_in_set('enabled', state)")
            ->orderBy('sort')
            ->get(['id','name','sort'])
            ->toArray();
        return $res;

    }

};
