<?php
/**
 * Created by PhpStorm.
 * User: ben
 * Date: 2019/3/13
 * Time: 18:47
 */
use Utils\Www\Action;
use Respect\Validation\Validator as Validator;
return new class extends Action
{
    const TOKEN = true;
    const TITLE = "校验验证码";
    const TAGS = "钱包";
    const PARAMS = [
        "code" => "string() #验证码",
        'phone'=> "string() #手机号",
        'password'=>"string() #密码"
    ];
    const SCHEMAS = [
    ];

    public function run() {

        $mobile       = trim($this->request->getParam('phone'));
        $password       = trim($this->request->getParam('password'));
        $verify = $this->auth->verfiyToken();
        $userId     = $this->auth->getUserId();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $validator = $this->validator->validate($this->request, [
            'phone'      => Validator::mobile()
                                        ->setName($this->lang->text("telphone")),
            'code' =>Validator::captchaTextCode()
                              ->setName($this->lang->text("captcha code")),
            'password' => Validator::password(),
        ]);

        if (!$validator->isValid()) {
            return $validator;
        }
        $captcha = new \Logic\Captcha\Captcha($this->ci);
        if (!$captcha->validateTextCode($mobile, $this->request->getParam('code'))) {
            return $this->lang->set(106);
        }
        $user=(array)DB::table('user')->where('id',$userId)->first(['password','salt']);
        if(!$this->auth->verifyPass($user['password'], $password, $user['salt'], 0)){
            return $this->lang->set(51);
        }
        DB::table('user')->where('id',$userId)->update(['is_verify'=>1,'mobile'=>\Utils\Utils::RSAEncrypt($mobile)]);
        \Model\Profile::where('user_id',$userId)->update(['mobile'=>\Utils\Utils::RSAEncrypt($mobile)]);
        return [];
    }
};
