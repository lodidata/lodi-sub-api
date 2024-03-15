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
    
    const PARAMS = [
    ];
    const SCHEMAS = [];
    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run()
    {
        $param = $this->request->getParams();

        if (DB::table('admin_user_google_manage')->where('admin_id', '=', $param['admin_id'])->update(array('authorize_state' => $param['state'])))
            return $this->lang->set(0);
        return $this->lang->set(-2);
    }
};
