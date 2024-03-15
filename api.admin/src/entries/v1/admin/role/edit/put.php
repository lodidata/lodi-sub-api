<?php

use Logic\Admin\Log;
use Model\Admin\Role;
use Logic\Admin\BaseController;
use lib\validate\BaseValidate;

return new class() extends BaseController {
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

    public function run($id)
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

        /*============================日志操作代码================================*/
        $info = DB::table('admin_user_role')
            ->find($id);
        /*============================================================*/
        //修改
        $res = DB::table('admin_user_role')
            ->where('id', $id)
            ->update(['auth' => $auth, 'role' => $role, 'member_control' => json_encode($memberControl), 'operator' => $this->playLoad['uid'], 'list_auth' => $list_auth]);

        /*============================日志操作代码================================*/
        $authRole = (array)\DB::table('admin_user_role_auth')->where('status', 1)->get([
            'id',
            'pid',
            'name',
        ])->toArray();
        $authRoleArr = [];
        foreach ($authRole as $value) {
            $authRoleArr[$value->id] = (array)$value;
        }
        $oldRole = explode(',', $info->auth);
        $newRole = explode(',', $auth);
        // 减少的权限
        $reduceRole = array_diff($oldRole, $newRole);
        foreach ($reduceRole as $v) {
            $type = "编辑";
            $str = "角色名称：{$info->role}-[{$authRoleArr[$v]['name']}]权限由[开启]改为[关闭]";
            $sta = $res !== false ? 1 : 0;
            (new Log($this->ci))->create(null, null, Log::MODULE_USER, '管理员角色', '管理员角色', $type, $sta, $str);
        }
        // 增加的权限
        $addRole = array_diff($newRole, $oldRole);
        foreach ($addRole as $v) {
            $type = "编辑";
            $str = "角色名称：{$info->role}-[{$authRoleArr[$v]['name']}]权限由[关闭]改为[开启]";
            $sta = $res !== false ? 1 : 0;
            (new Log($this->ci))->create(null, null, Log::MODULE_USER, '管理员角色', '管理员角色', $type, $sta, $str);
        }

        /*============================================================*/


    }

};
