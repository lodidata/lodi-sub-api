<?php

use Logic\Admin\BaseController;
use Logic\Admin\Log;
use Respect\Validation\Validator as V;

return new class() extends BaseController {
    const TITLE = '修改管理員';
    const DESCRIPTION = '';

    const QUERY = [

    ];

    const PARAMS = [];
    const SCHEMAS = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run($id = null)
    {
        $this->checkID($id);

        $params = $this->request->getParams();

        if (isset($params['state']) && is_numeric($params['state'])) {

            /*============================日志操作代码================================*/
            $info = DB::table('admin_user')
                ->find($id);
            /*============================================================*/

            //修改状态
            $res = DB::table('admin_user')
                ->where('id', $id)
                ->update(['status' => $params['state']]);
            if ($params['state'] == 0) (new Logic\Admin\AdminAuth($this->ci))->deleteAdminWithToken($id);

            /*============================日志操作代码================================*/
            $status = [
                '1' => '启用',
                '0' => '停用',
            ];
            $sta = $res !== false ? 1 : 0;
            (new Log($this->ci))->create($id, $info->username, Log::MODULE_USER, '管理员列表', '管理员列表', $status[$params['state']], $sta, "账号：{$info->username}");
            /*============================================================*/

            if ($res === false) {
                return $this->lang->set(-2);
            }
        }

        if (isset($params['pw-new']) && !empty($params['pw-new'])) {

            //修改密码
            $pwd    = $params['pw-new'];

            //校验密码强度
            if (!$this->checkAdminPass($pwd)) {
                return $this->lang->set(11040);
            }

            list($password, $salt)         = $this->makePW($pwd);

            /*============================日志操作代码================================*/
            $info = DB::table('admin_user')
                ->find($id);
            /*============================================================*/

            $res = DB::table('admin_user')
                ->where('id', $id)
                ->update(['password' => $password, 'salt' => $salt]);

            if ($res === false) {
                return $this->lang->set(-2);
            }
            /*============================日志操作代码================================*/
            $sta = $res !== false ? 1 : 0;
            (new Log($this->ci))->create($id, $info->username, Log::MODULE_USER, '管理员列表', '管理员列表', '修改密码', $sta, "账号：{$info->username}");
            /*============================================================*/
        }

        //修改pin密码
        if (!empty($params['pin_pw'])) {
            $pin_pw = $params['pin_pw'];

            $validator = $this->validator->validate($this->request, [
                'pin_pw' => V::noWhitespace()->length(6,16)->setName('pin密码'),
            ]);

            if (!$validator->isValid()) {
                return $validator;
            }

            list($pin_password, $pin_salt) = $this->makePW($pin_pw);

            /*============================日志操作代码================================*/
            $info = DB::table('admin_user')
                ->find($id);
            /*============================================================*/

            $res = DB::table('admin_user')
                ->where('id', $id)
                ->update(['pin_password' => $pin_password,'pin_salt'=>$pin_salt]);

            if ($res === false) {
                return $this->lang->set(-2);
            }
            /*============================日志操作代码================================*/
            $sta = $res !== false ? 1 : 0;
            (new Log($this->ci))->create($id, $info->username, Log::MODULE_USER, '管理员列表', '管理员列表', '修改pin密码', $sta, "账号：{$info->username}");
            /*============================================================*/
        }


        $resultEnd = true;

        //3个可修改字段
        $data = [];
        foreach (['truename', 'part', 'job'] as $field) {
            if (empty($params[$field])) {
                continue;
            }

            $data[$field] = $params[$field];
        }
        if ($data) {
            /*============================日志操作代码================================*/
            $info = DB::table('admin_user')
                ->find($id);
            /*============================================================*/

            //修改个人资料
            $res = DB::table('admin_user')
                ->where([
                    'id' => $id,
                ])
                ->update($data);

            if ($res === false) {
                $resultEnd = false;
            }

        }

        if (!empty($params['role'])) {
            //修改用户角色
            $role = $params['role'];

            $oldRole = (array)DB::table('admin_user_role_relation')
                ->select('rid')
                ->where([
                    'uid' => $id,
                ])->first();

            $numOlds = DB::table('admin_user_role_relation')->where([
                'rid' => $oldRole['rid']
            ])->count();
            DB::table('admin_user_role')
                ->where([
                    'id' => $oldRole,
                ])
                ->update(['num' => $numOlds - 1]);

            $res = DB::table('admin_user_role_relation')
                ->where([
                    'uid' => $id,
                ])
                ->update(['rid' => $role]);

            $num = DB::table('admin_user_role_relation')->where([
                'rid' => $role
            ])->count();
            DB::table('admin_user_role')
                ->where([
                    'id' => $role,
                ])
                ->update(['num' => $num]);

            if ($res === false) {
                $resultEnd = false;
            }
        }
        /*============================日志操作代码================================*/
        $sta = $resultEnd != false ? 1 : 0;
        (new Log($this->ci))->create($id, $info->username, Log::MODULE_USER, '管理员列表', '管理员列表', '编辑', $sta, "账号：{$info->username}");
        /*============================================================*/


        return $this->lang->set(0);
    }

};
