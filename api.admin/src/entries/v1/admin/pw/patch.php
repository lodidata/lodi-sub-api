<?php
/**
 * vegas2.0
 *
 * @auth *^-^*<dm>
 * @copyright XLZ CO.
 * @package
 * @date 2017/4/10 9:47
 */

use Logic\Admin\BaseController;
use Logic\Admin\Cache\AdminRedis;
use lib\validate\BaseValidate;
return new class() extends BaseController
{
//    const STATE       = \API::DRAFT;
    const TITLE       = '修改登录密码';
    const DESCRIPTION = '注意：非提款密码。清除后前端删除此用户的cache，退出到登入界面';
    
    const QUERY       = [];
    
    const PARAMS      = [
        'user_name' => 'string(required) #登录用户名',
        'old-pw'    => 'string(required) #旧密码',
        'new-pw'    => 'string(required) #新密码'
    ];
    const SCHEMAS     = [

    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run()
    {

        (new BaseValidate([
            ['old-pw','require|length:6,32'],
            ['new-pw','require|length:6,32'],
//            ['codes','require'],
        ],
            [],
            ['old-pw'=>'旧密码','new-pw'=>'新密码']
        ))->paramsCheck('',$this->request,$this->response);
        $params = $this->request->getParams();
        $pw     = $params['new-pw'];
//{"old-pw":"123456","new-pw":"123456","user_name":"test777"}

        $user = \Model\Admin\AdminUser::where('id',$this->playLoad['uid'])->get()->toArray();

        if(!$user){
            return $this->lang->set(10014);
        }


        if ($user[0]['password'] != md5(md5($params['old-pw']) . $user[0]['salt'])) {
                return $this->lang->set(10046);
        }

//        (new AdminRedis($this->ci))->removeAdminUserCache($this->playLoad['uid']);

         $this->resetPassword($this->playLoad['uid'], $pw);

        return $this->lang->set(0);

    }

    /**
     * @param $id
     * @param $password
     * @return int
     */
    protected function resetPassword($id, $password)
    {

        list($password, $salt) = $this->makePW($password);


        $res = DB::table('admin_user')
            ->where('id',$id)
            ->where('is_master','<>',1)
            ->update(['password'=>$password,'salt'=>$salt]);
        if($res !== false){
            (new AdminRedis($this->ci))->removeAdminUserCache($id);
            (new AdminRedis($this->ci))->removeAdminUser($id);
            return $this->lang->set(0);
        }
        return $this->lang->set(-2);

    }




};
