<?php

use Logic\Admin\Log;
use Model\Admin\Role;
use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const TITLE       = '新增修改角色';
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

    public function run($id)
    {
        $this->checkID($id);

        //判断角色是否有使用
        $roleArr = DB::table('admin_user_role_relation')
                        ->select('id')
                         ->where('rid', $id)
                         ->get()
                         ->toArray();

        if($roleArr){
            return $this->lang->set(10020);
        }
        /*============================日志操作代码================================*/
        $info=DB::table('admin_user_role')
            ->find($id);
        /*============================================================*/

        //删除
        $res = DB::table('admin_user_role')
                ->where('id', $id)
                ->delete();

        if($res){
            /*============================日志操作代码================================*/
            $sta = $res !== false ? 1 : 0;
            (new Log($this->ci))->create(null, null, Log::MODULE_USER, '管理员角色', '管理员角色', "删除", $sta, "角色名称：{$info->role}");
            /*============================================================*/
            return $this->lang->set(0);
        }

        return $this->lang->set(-2);

    }


};
