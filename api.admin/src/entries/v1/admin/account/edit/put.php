<?php

use Logic\Admin\BaseController;
use lib\validate\BaseValidate;
use Logic\Admin\Log;

return new class() extends BaseController {
    const TITLE = '新增管理员';
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
        (new BaseValidate([
            'username' => 'require|alphaNum',
            'password' => 'require|length:8,16',
            'truename' => 'require',
            'part' => 'require',
            'job' => 'require',
            'role' => 'require',
        ],
            [],
            ['username' => '管理员名称', 'password' => '密码', 'truename' => '真实姓名', 'part' => '部门',
                'job' => '职位', 'role' => '角色',
            ]
        ))->paramsCheck('', $this->request, $this->response);

        $data = $this->request->getParams();

        $username = $data['username'];
        $password = $data['password'];
        $truename = $data['truename'];
        $part = $data['part'];
        $job = $data['job'];
        $role = $data['role'];

        //校验密码强度
        if (!$this->checkAdminPass($password)) {
            return $this->lang->set(11040);
        }

        //先判断是否存在同名账号
        $userArr = DB::table('admin_user')->where('username', $username)->get()->toArray();

        if ($userArr) {
            return $this->lang->set(4002);
        }

        list($password, $salt) = $this->makePW($password);

        $data = [
            'salt' => $salt,
            'username' => $username,
            'password' => $password,
            'truename' => $truename,
            'part' => $part,
            'job' => $job,
            'is_master' => 0,
            'nick' => $this->playLoad['nick'],
        ];

        $uid = DB::table('admin_user')
            ->insertGetId($data);
        $sta = 0;
        if ($uid) {
            $sta = 1;
            DB::table('admin_user_role_relation')
                ->insert(['uid' => $uid, 'rid' => $role]);

            DB::table('admin_user_role')
                ->where('id', $role)
                ->increment('num');
        }
        /*============================日志操作代码================================*/
        $sta = $sta !== false ? 1 : 0;
        (new Log($this->ci))->create(null, null, Log::MODULE_USER, '管理员列表', '管理员列表', '新增管理员', $sta, "账号：$username");
        /*============================================================*/

        return [];
    }
};
