<?php

use Logic\Admin\BaseController;

return new class() extends BaseController
{
    const TITLE = '谷歌登录开关';
    const DESCRIPTION = '';
    
    const QUERY = [
        'state' => 'int() #状态（通过uri传参）'
    ];
    
    const PARAMS = [
    ];
    const STATEs = [
    ];
    const SCHEMAS = [];
    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run()
    {
        $param = $this->request->getParams();

        if (DB::table('admin_user_google_manage')->where('admin_id', '=', 10000)->update(array('authorize_state' => $param['state'])))
            return $this->lang->set(0);
        return $this->lang->set(-2);
    }
};
