<?php
use Utils\Www\Action;
use Respect\Validation\Validator as V;

return new class extends Action
{
    const TOKEN      = true;
    const TITLE       = '安全中心-绑定&更新验证邮箱';
    const DESCRIPTION = '返回状态180 绑定成功';
    const TAGS = "安全中心";
    const PARAMS      = [
        'email' => 'string(required) #邮箱',
        'code'  => 'string(required) #验证码'

    ];
    const SCHEMAS     = [
    ];


    public function run() {
        //$captcha = new \Logic\Captcha\Captcha($this->ci);
        // $captcha->sendTextCodeByEmail(1, $this->request->getParam('email'));
        // exit;

        $validator = $this->validator->validate($this->request, [
            'email' => V::email()->noWhitespace()->length(6, 50)->setName($this->lang->text("email address")),
            //'code' => V::captchaTextCode(),
        ]);

        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $userId = $this->auth->getUserId();
        //$captcha = new \Logic\Captcha\Captcha($this->ci);
        $profile = \Model\Profile::where('user_id', $userId)->first();

        // 判断是否在更新邮箱
        /*$safety = $this->redis->get(\Logic\Define\CacheKey::$perfix['userSafety'].'_3_'.$userId);
        if (!empty($profile['email']) && empty($safety)) {
            return $this->lang->set(173);
        }*/

        /*if (empty($profile['email'])) {
            // $lang = $captcha->validateTextCodeByEmail($this->auth->getUserId(), $this->request->getParam('code'));
            if (!$captcha->validateTextCodeByEmail($this->auth->getUserId(), $this->request->getParam('code'))) {
                return $this->lang->set(123);
            }
        }*/

        // return $this->lang->set(105);
        
        $email = \Utils\Utils::RSAEncrypt($this->request->getParam('email'));
        \Model\Profile::where('user_id', $userId)->update(['email' => $email]);

        if (empty($profile['email'])) {
            \Model\SafeCenter::where('user_id', $userId)->update(['email' => 1]);
            $activity = new \Logic\Activity\Activity($this->ci);
            $activity->bindInfo($userId, 2);
        }
        return $this->lang->set(180);
    }
};
