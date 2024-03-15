<?php

use Logic\Admin\BaseController;
use Logic\Admin\Cache\AdminRedis;
use lib\validate\BaseValidate;
use Respect\Validation\Validator as V;
return new class() extends BaseController
{
//    const STATE       = \API::DRAFT;
    const TITLE       = '重设登录密码，设置pin密码';
    const DESCRIPTION = '第一次登录需要';
    
    const QUERY       = [];
    
    const PARAMS      = [
        'pw'             => 'string(required) #密码',
        'pw_confirm'     => 'string(required) #确认密码',
        'pin_pw'         => 'string(required) #pin密码',
        'pin_pw_confirm' => 'string(required) #pin密码确认密码'
    ];

    const SCHEMAS     = [

    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];
    //不需要验证是否重设密码
    protected $NoValidResetPassword    = 1;
    //不需要验证是否重设pin密码
    protected $NoValidResetPinPassword = 1;

    public function run()
    {
        $params = $this->request->getParams();
        $pw             = $params['pw'];
        $pw_confirm     = $params['pw_confirm'];
        $pin_pw         = $params['pin_pw'];
        $pin_pw_confirm = $params['pin_pw_confirm'];

        if($pw != $pw_confirm){
            return $this->lang->set(4003);
        }

        if($pin_pw != $pin_pw_confirm){
            return $this->lang->set(886,['pin密码前后不一致']);
        }

        //校验密码强度
        if (!$this->checkAdminPass($pw)) {
            return $this->lang->set(11040);
        }

        $validator = $this->validator->validate($this->request, [
            'pin_pw' => V::noWhitespace()->length(6,16)->setName('pin密码'),
        ]);

        if (!$validator->isValid()) {
            return $validator;
        }

        $user = \Model\Admin\AdminUser::where('id',$this->playLoad['uid'])->get()->toArray();

        if(!$user){
            return $this->lang->set(10014);
        }

        if(md5(md5($pw).$user[0]['salt']) == $user[0]['password']){
            return $this->lang->set(886,['密码不能和老密码一样']);
        }

        if(md5(md5($pin_pw).$user[0]['pin_salt']) == $user[0]['pin_password']){
            return $this->lang->set(886,['pin密码不能和老pin密码一样']);
        }

        $this->resetPassword($this->playLoad['uid'], $pw, $pin_pw);

        return $this->lang->set(0);

    }

    /**
     * @param $id
     * @param $password
     * @return int
     */
    protected function resetPassword($id, $password, $pin_pw)
    {
        list($password, $salt)         = $this->makePW($password);
        list($pin_password, $pin_salt) = $this->makePW($pin_pw);

        $res = DB::table('admin_user')
            ->where('id',$id)
            ->where('is_master','<>',1)
            ->update(['password'=>$password,'salt'=>$salt,'pin_password' => $pin_password,'pin_salt'=>$pin_salt,'reset_password'=>1,'reset_pin_time' => date('Y-m-d H:i:s')]);
        if($res !== false){
            (new AdminRedis($this->ci))->removeAdminUserCache($id);
            (new AdminRedis($this->ci))->removeAdminUser($id);
            return $this->lang->set(0);
        }
        return $this->lang->set(-2);

    }




};
