<?php
/**
 * Created by PhpStorm.
 * User: ben
 * Date: 2019/3/13
 * Time: 18:47
 */
use Utils\Www\Action;
use Respect\Validation\Validator as V;
return new class extends Action
{
    const TOKEN = true;
    const TITLE = "进入保险箱";
    const TAGS = "钱包";
    const PARAMS = [
        "password" => "string() #保险箱密码"
    ];
    const SCHEMAS = [
    ];

    public function run() {

        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $validator = $this->validator->validate($this->request, [
            'password' => V::intVal()->noWhitespace()->length(4)->setName($this->lang->text('Safe code')),
        ]);

        if (!$validator->isValid()) {
            return $validator;
        }

        $userId = $this->auth->getUserId();
//        $userId = 2;
        $password = $this->request->getParam('password');
        $user = \Model\User::where('id', $userId)->first();
        $funds = \Model\Funds::where('id', $user['wallet_id'])->first();

        if (empty($funds['freeze_password'])) {
            return $this->lang->set(2300);
        }
        if(password_verify($password,$funds['freeze_password'])){
            return [];
        }else{
            return $this->lang->set(2301);
        }
    }
};
