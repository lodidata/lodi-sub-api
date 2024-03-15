<?php

use Logic\Admin\Active as activeLogic;
//use Model\Admin\Active;
use Logic\Admin\BaseController;

return new class() extends BaseController
{
//    const STATE       = \API::DEPRECATED;
    const TITLE = '清空活动黑名单';

    const QUERY = [
    ];
    const PARAMS = [
    ];
    const SCHEMAS = [
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run($id = null)
    {

        //清空活动黑名单
        \DB::table('active_apply_blacklist')->where('active_id',$id)->delete();
        \DB::table('active')->where('id',$id)->update(['blacklist_url'=>'']);

        return $this->lang->set(0);
    }
};
