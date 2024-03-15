<?php
use Utils\Www\Action;
use Respect\Validation\Validator as V;
/**
 * 发送短信
 */
return new class extends Action {
    const TITLE = "会员注册-发送手机验证码";
    const TAGS = "登录注册";
    const PARAMS = [
        "telphone"      => "string(required) #手机号码",
        //'telphone_code' => "string(required) #手机区号",
        //"token"         => "string(required) #图片验证码串",
        //"code"          => "string(required) #图片验证码",
   ];
    const SCHEMAS = [
   ];

    public function run() {

        $validator = $this->validator->validate($this->request, [
            'telphone' => V::mobile()->setName($this->lang->text("telphone")),
            //'token' => V::alnum()->noWhitespace()->length(32)->setName($this->lang->text("token code")),
            //'code' => V::intVal()->noWhitespace()->length(4)->setName($this->lang->text("captcha code")),
        ]);

        if (!$validator->isValid()) {
            return $validator;
        }

        $userId     = $this->auth->getUserId();

        $telphone = $this->request->getParam('telphone');
        $captcha = new \Logic\Captcha\Captcha($this->ci);
        $mobile  = \Utils\Utils::RSAEncrypt($telphone);
        if(isset($userId) && !empty($userId)){
            if (\Model\User::where('mobile', $mobile)->where('id','!=',$userId)->count()) {
                return $this->lang->set(104);
            }
        }else{
            if (\Model\User::where('mobile', $mobile)->count()) {
                return $this->lang->set(104);
            }
        }


        //$telphone_code = $this->request->getParam('telphone_code');
        return $captcha->sendTextCode($telphone);

        /*$captcha = new \Logic\Captcha\Captcha($this->ci);
        if ($captcha->validateImageCode($this->request->getParam('token'), $this->request->getParam('code'))) {
            $mobile = \Utils\Utils::RSAEncrypt($this->request->getParam('telphone'));
            if (\Model\User::where('mobile', $mobile)->count()) {
                return $this->lang->set(104);
            }

            return $captcha->sendTextCode($this->request->getParam('telphone'));
        }
        return $this->lang->set(105);*/
    }
};