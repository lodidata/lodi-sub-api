<?php

use Logic\Admin\BaseController;
use Logic\Admin\Cache\AdminRedis;
use lib\validate\BaseValidate;
use Respect\Validation\Validator as V;
return new class() extends BaseController
{
    const TITLE       = '重新设置pin密码';
    const DESCRIPTION = '周期性';
    
    const QUERY       = [];
    
    const PARAMS      = [
        'old_pin_pw'     => 'string(required) #旧的pin密码',
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
        $old_pin_pw     = $params['old_pin_pw'];
        $pin_pw         = $params['pin_pw'];
        $pin_pw_confirm = $params['pin_pw_confirm'];

        if($pin_pw != $pin_pw_confirm){
            return $this->lang->set(886,['pin密码前后不一致']);
        }

        if($old_pin_pw == $pin_pw){
            return $this->lang->set(886,['pin密码不能和原密码一样']);
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

        if($user[0]['pin_password'] != md5(md5($old_pin_pw).$user[0]['pin_salt'])){
            return $this->lang->set(886,['old pin password is incorrect']);
        }

        $this->resetPinPassword($this->playLoad['uid'],$pin_pw);

        return $this->lang->set(0);

    }

    /**
     * @param $id
     * @param $password
     * @return int
     */
    protected function resetPinPassword($id, $pin_pw)
    {
        list($pin_password, $pin_salt) = $this->makePW($pin_pw);

        $res = DB::table('admin_user')
            ->where('id',$id)
            ->where('is_master','<>',1)
            ->update(['pin_password' => $pin_password,'pin_salt'=>$pin_salt,'reset_pin_time' => date('Y-m-d H:i:s')]);
        if($res !== false){

            return $this->lang->set(0);
        }
        return $this->lang->set(-2);

    }




};
