<?php
use Utils\Www\Action;
use Respect\Validation\Validator as V;

return new class extends Action
{
    const TOKEN       = true;
    const TITLE       = '安全中心-短信验证';
    const DESCRIPTION = '需要先请求图形验证码接口 返回状态102成功';
    const TAGS = "安全中心";
    const PARAMS      = [
        //'code' => 'int(required) # 图形验证码',
       // 'token' => 'string(required) # 图形验证码串',
        'telphone' => 'string(required) # 手机号码',
        //'telphone_code' => 'string(required) # 国家区号',
    ];
    const SCHEMAS     = [
    ];

    public function run() {

        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $validator = $this->validator->validate($this->request, [
            'telphone'      => V::mobile()
                ->setName($this->lang->text("telphone")),
            // 'telphone_code' => V::noWhitespace()->length(2,6)->setName('国家区号'),
           // 'token' => V::alnum()->noWhitespace()->length(32)->setName($this->lang->text("token code")),
           // 'code' => V::intVal()->noWhitespace()->length(4)->setName($this->lang->text("captcha code")),
        ]);

        if (!$validator->isValid()) {
            return $validator;
        }
        $userId = $this->auth->getUserId();
        $captcha = new \Logic\Captcha\Captcha($this->ci);
        //$telphoneCode = $this->request->getParam('telphone_code');
        //$mobile = $telphoneCode.$this->request->getParam('telphone');
        $mobile = $this->request->getParam('telphone');
        $user = \Model\User::where('id', $userId)->first();

        if (!empty($user['mobile'])) {
            // return $this->lang->set(22);
            //$mobile = $user['telphone_code'].\Utils\Utils::RSADecrypt($user['mobile']);
            $mobile = \Utils\Utils::RSADecrypt($user['mobile']);
        }

        return $captcha->sendTextCode($mobile);
    }
/*    public function run() {

        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $validator = $this->validator->validate($this->request, [
            'telphone'      => V::mobile()
                ->setName($this->lang->text("telphone")),
            // 'telphone_code' => V::noWhitespace()->length(2,6)->setName('国家区号'),
           // 'token' => V::alnum()->noWhitespace()->length(32)->setName($this->lang->text("token code")),
           // 'code' => V::intVal()->noWhitespace()->length(4)->setName($this->lang->text("captcha code")),
        ]);

        if (!$validator->isValid()) {
            return $validator;
        }
        $userId = $this->auth->getUserId();
        $captcha = new \Logic\Captcha\Captcha($this->ci);
        //$telphoneCode = $this->request->getParam('telphone_code');
        //$mobile = $telphoneCode.$this->request->getParam('telphone');
        $mobile = $this->request->getParam('telphone');
        if ($captcha->validateImageCode($this->request->getParam('token'), $this->request->getParam('code'))) {
            $user = \Model\User::where('id', $userId)->first();

            if (!empty($user['mobile'])) {
                // return $this->lang->set(22);
                //$mobile = $user['telphone_code'].\Utils\Utils::RSADecrypt($user['mobile']);
                $mobile = \Utils\Utils::RSADecrypt($user['mobile']);
            }

            return $captcha->sendTextCode($mobile);
        }
        return $this->lang->set(105);
    }*/
};