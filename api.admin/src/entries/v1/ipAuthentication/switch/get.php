<?php

use Logic\Admin\BaseController;

return new class() extends BaseController
{
    const TITLE = '谷歌验证器-状态';
    const DESCRIPTION = '';
    
    const QUERY = [
    ];


    const PARAMS = [];
    const SCHEMAS = [
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run()
    {
        $query = \DB::table('admin_user_google_manage')->where('admin_id', '=', 10000)->get(['authorize_state'])->toArray();
        return $this->lang->set(0, [], $query[0]);
    }

};
