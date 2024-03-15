<?php

use Logic\Admin\BaseController;

return new class() extends BaseController
{
    const TITLE = '管理员授权开关';
    const DESCRIPTION = '';

    const QUERY = [
        'state' => 'int() #状态',
        'admin_id' => 'int() #状态'
    ];

    const SCHEMAS = [];
    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run()
    {
        $param = $this->request->getParams();

        if ( DB::delete("DELETE FROM admin_user_google_manage WHERE admin_id = {$param['admin_id']}"))
            return $this->lang->set(0);
        return $this->lang->set(-2);
    }
};
