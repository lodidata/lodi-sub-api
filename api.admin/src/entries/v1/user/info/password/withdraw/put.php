<?php
/**
 * vegas2.0
 *
 * @auth *^-^*<dm>
 * @copyright XLZ CO.
 * @package
 * @date 2017/4/15 18:03
 */

use Logic\Admin\BaseController;
use Logic\Admin\Log;
use Logic\GameApi\Common;

return new class() extends BaseController
{
//    const STATE       = \API::DRAFT;
    const TITLE       = '修改用户提款密码';
    const DESCRIPTION = '会员管理';
    
    const QUERY       = [
        'id' => 'int() #用户id',
    ];
    
    const PARAMS      = [
        'password' => 'string #新用户密码',
        'repassword'    => 'string #确认密码'
    ];
    const SCHEMAS = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];

    public function run($id=null)
    {
        $this->checkID($id);

        (new \lib\validate\BaseValidate(
            [
                'password'=>'require|number|length:4',
                'repassword'=>'require|confirm:password'
            ],
            []
        ))->paramsCheck('',$this->request,$this->response);

        $params = $this->request->getParams();
        $user = (new Common($this->ci))->getUserInfo($id);
        if(!$user)
            return $this->lang->set(10015);

        $salt     = \Model\User::getGenerateChar(6);;

        try{
            \Model\SafeCenter::where('user_id', $id)->update(['withdraw_password' => 1]);
            $funds = \Model\Admin\Funds::find($user['wallet_id']);
            $funds->setTarget($id,$user['name']);
            $funds->save([
                'salt' => $salt,
                'password' => md5(md5($params['password']).$salt)
            ]);
        }catch (\Exception $e){
            return $this->lang->set(-2);
        }
        return $this->lang->set(0);


    }

};
