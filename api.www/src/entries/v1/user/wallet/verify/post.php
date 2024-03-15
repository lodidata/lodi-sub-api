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
    const TITLE = "校验验证码";
    const TAGS = "钱包";
    const PARAMS = [
        "code" => "string() #验证码",
        'phone'=> "string() #手机号",
    ];
    const SCHEMAS = [
    ];

    public function run() {

        $mobile       = trim($this->request->getParam('phone'));
        $verify = $this->auth->verfiyToken();
        $userId     = $this->auth->getUserId();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $validator = $this->validator->validate($this->request, [
            'phone'      => V::mobile()
                                        ->setName($this->lang->text("telphone")),
            'code' => V::captchaTextCode()
                       ->setName($this->lang->text("captcha code")),
        ]);

        if (!$validator->isValid()) {
            return $validator;
        }
        $captcha = new \Logic\Captcha\Captcha($this->ci);
        if (!$captcha->validateTextCode($mobile, $this->request->getParam('code'))) {
            return $this->lang->set(106);
        }
        DB::table('user')->where('id',$userId)->update(['is_verify'=>1]);
        return [];
    }
};
