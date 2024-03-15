<?php
use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '获取IOS包信息';
    const DESCRIPTION = '';
    
    const QUERY       = [
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

        $query= \DB::table('app_bag as bag')
            ->leftJoin('admin_user as admin','bag.update_uid','=','admin.id')
            ->orderBy('id','DESC')
            ->where('type','=',2);
        return $query->get([
                'bag.id',
                'bag.url',
                'bag.name',
                'bag.upgrade',
                'admin.username',
                'bag.update_date',
            ])->toArray();
    }
};
