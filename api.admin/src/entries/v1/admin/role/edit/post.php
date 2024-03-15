<?php

use Logic\Admin\Log;
use Model\Admin\Role;
use Logic\Admin\BaseController;
use lib\validate\BaseValidate;

return new class() extends BaseController
{
    const TITLE = '新增修改角色';
    const DESCRIPTION = '';
    
    const QUERY = [

    ];

    const PARAMS = [];
    const SCHEMAS = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run()
    {
        $validate = new BaseValidate([
            'auth' => 'require',
            'role' => 'require|length:2,10',
        ], [
                'role.length' => '角色名称长度为2-10位',
                'auth' => '请至少勾选一个权限',
            ]
        );

        $validate->paramsCheck('', $this->request, $this->response);

        $data = $this->request->getParams();
        $auth = $data['auth'];
        $role = $data['role'];
        $list_auth = $data['list_auth'] ?? '';
        $memberControl = $data['member_control'];
        $memberControl['user_search_switch'] = $data['user_search_switch'];
        //role角色名不能重复
        $role_data = Role::where('role', $role)->value('id');
        if ($role_data) {
            return $this->lang->set(10019);
        }

        //新增
        $res = DB::table('admin_user_role')
            ->insert(['auth' => $auth, 'role' => $role, 'member_control' => json_encode($memberControl), 'operator' => $this->playLoad['uid'], 'list_auth' => $list_auth]);

        /*============================日志操作代码================================*/
        $type = "新增角色";
        $str = "角色名称：$role";
        $sta = $res !== false ? 1 : 0;
        (new Log($this->ci))->create(null, null, Log::MODULE_USER, '管理员角色', '管理员角色', $type, $sta, $str);
        /*============================================================*/
    }
};
