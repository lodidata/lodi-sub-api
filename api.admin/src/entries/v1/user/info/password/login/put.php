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

return new class() extends BaseController
{
    const TITLE       = '修改用户登录密码';
    const DESCRIPTION = '会员管理';
    
    const QUERY       = [
        'id' => 'int() #用户id',
    ];
    
    const PARAMS      = [
        'password' => 'string #新用户密码',
        'repassword'    => 'string() #确认密码'
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
                'password'=>'require|length:6,20',
                'repassword'=>'require|confirm:password'
            ],
            []
        ))->paramsCheck('',$this->request,$this->response);

        $params = $this->request->getParams();
        $password = trim($params['password']);
        if (preg_match("/[ '.,:;*?~`!@#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/", $password)) {

            return createRsponse($this->response, 200, 10, '密码不能含有标点符号及特殊字符');
        }

        $salt = $this->generate_char(6);
        $password = md5(md5($password) . $salt);

        $data = [
            'salt' => $salt,
            'password' => $password,
            'tp_password_initial' => 0,
        ];

        $user = \Model\Admin\User::find($id);
        $user->setTarget($id,$user->name);
        $res = $user->save($data);
        if(!$res)
            return $this->lang->set(-2);
        return $this->lang->set(0);
    }

    /**
     *
     * @param
     *            生成随机字符串
     * @return string|unknown
     */
    public function generate_char($length = 6, $chars = null)
    {
        // 密码字符集，可任意添加你需要的字符
        $chars = $chars ?? "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_[]{}<>~`+=,.;:?|";
        $password = "";
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[mt_rand(0, strlen($chars) - 1)];
        }

        return $password;
    }
};
