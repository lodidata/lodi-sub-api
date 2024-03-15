<?php
use Utils\Www\Action;
use Respect\Validation\Validator as V;
use Logic\Define\Lang;
return new class extends Action
{
    const TITLE = '忘记登录密码-发送找验证码';
    const TAGS = "登录注册";
    const PARAMS = [
        "name"  => "string(required) #用户账号或手机号",
        'token' => 'string(required) #图形验证码token',
        'code'  => 'string(required) #图形验证码',
        'type'  => 'string(required) #mobile 手机验证 email 邮箱验证',
    ];
    const SCHEMAS = [];

    public function run() {
        $validator = $this->validator->validate($this->request, [
            'name'  => V::username()->setName($this->lang->text("username")),
            'code'  => V::noWhitespace()->length(4,4)->setName($this->lang->text("captcha code")),
            'token' => V::noWhitespace()->setName('token'),
            'type'  => V::noWhitespace()->in(['mobile', 'email'])->setName($this->lang->text("forget type")),
        ]);

        if (!$validator->isValid()) {
            return $validator;
        }

        $name        = $this->request->getParam('name');
        $token       = $this->request->getParam('token');
        $code        = $this->request->getParam('code');
        $type        = $this->request->getParam('type');

        //菲版去掉前缀0  泰版不去掉
        global $app;
        $site_type = $app->getContainer()->get('settings')['website']['site_type'];
        if($site_type == 'lodi'){
            $mobile = preg_replace('/^0+/','',$name);        //处理手机号前面的数字0
        }else{
            $mobile = $name;
        }
        $name_mobile =\Utils\Utils::RSAEncrypt($mobile);

        //先判断验证码
        $captcha = new \Logic\Captcha\Captcha($this->ci);
        if (!$captcha->validateImageCode($token, $code)) {
            return $this->lang->set(123);
        }

        $user        = \Model\User::where('name', $name)->orWhere('mobile', $name_mobile)->first();
        if (empty($user)) {
            //记录次数 判断是否是恶意行为
            \Utils\Client::addBlackIP();
            //迷惑别人
            return $this->lang->set(102);
            //return $this->lang->set(28);
        }
        $profile = \Model\Profile::where('user_id', $user['id'])->first();

        if (empty($profile['mobile']) && empty($profile['email'])) {
            return $this->lang->set(169);
        } else {
            $mobile = \Utils\Utils::RSADecrypt($user['mobile']);
            $email = \Utils\Utils::RSADecrypt($user['email']);
            // $type = !empty($mobile) ? 'mobile' : 'email';
        }

        if ($type == 'mobile' && empty($mobile)) {
            return $this->lang->set(176);
        }

        if ($type == 'email' && empty($email)) {
            return $this->lang->set(175); 
        }

        if ($type == 'mobile') {
            $res = $captcha->sendTextCode($mobile);
        } else {
            $res = $captcha->sendTextCodeByEmail($user['id'], $email);
        }

        // 记录token通过状态
        if (in_array($res->getState(), [0,102])) {
            $this->redis->setex(\Logic\Define\CacheKey::$perfix['userSafety'].'_9_'.$user['id'],5*60, 1);
        }

        return $res;
    }

    /*public function run() {
        $validator = $this->validator->validate($this->request, [
            'name' => V::username()->setName($this->lang->text("username")),
            'code' => V::noWhitespace()->length(4)->setName($this->lang->text("captcha code")),
            'token' => V::noWhitespace()->setName('token'),
            'type' => V::noWhitespace()->in(['mobile', 'email'])->setName($this->lang->text("forget type")),
        ]);

        if (!$validator->isValid()) {
            return $validator;
        }

        $name = $this->request->getParam('name');
        $token = $this->request->getParam('token');
        $code = $this->request->getParam('code');
        $type = $this->request->getParam('type');

        $user = \Model\User::where('name', $name)->first();
        if (empty($user)) {
            return $this->lang->set(51);
        }
        $profile = \Model\Profile::where('user_id', $user['id'])->first();
        $captcha = new \Logic\Captcha\Captcha($this->ci);
        if (empty($profile['mobile']) && empty($profile['email'])) {
            return $this->lang->set(169);
        } else {
            $mobile = \Utils\Utils::RSADecrypt($user['mobile']);
            $email = \Utils\Utils::RSADecrypt($user['email']);
            // $type = !empty($mobile) ? 'mobile' : 'email';
        }

        if ($type == 'mobile' && empty($mobile)) {
            return $this->lang->set(176);
        }

        if ($type == 'email' && empty($email)) {
            return $this->lang->set(175);
        }

        if (!$captcha->validateImageCode($token, $code)) {
            return $this->lang->set(123);
        }

        if ($type == 'mobile') {
            $res = $captcha->sendTextCode($mobile);
        } else {
            $res = $captcha->sendTextCodeByEmail($user['id'], $email);
        }

        // 记录token通过状态
        if (in_array($res->getState(), [0,102])) {
            $this->redis->setex(\Logic\Define\CacheKey::$perfix['userSafety'].'_9_'.$user['id'],5*60, 1);
        }

        return $res;
    }*/
};