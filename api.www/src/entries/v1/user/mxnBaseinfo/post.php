<?php

use Respect\Validation\Validator as V;
use Utils\Www\Action;
return new class extends Action
{
    const TOKEN = true;
    const TITLE = "个人中心-个人资料-完善资料";
    const TAGS = "个人中心";
    const PARAMS = [
       "name"           => "string() #姓名",
       "avatar"         => "int() #头像id",
       "gender"         => "int() #性别 (1:男,2:女,3:保密)",
       "city"           => "int() #城市ID",
       "address"        => "string() #详细地址",
       "nationality"    => "int() #国籍",
       "birth_place"    => "int() #出生地",
       "birth"          => "string() #出生日期 2018-08-23",
       "qq"             => "string() #qq",
       "wechat"         => "string() #wechat",
       "nickname"       => "string() #nickname昵称",
       "skype"          => "string() #skype",
       "mobile"         => "string() #mobile",
       "email"          => "string() #email",
       "verify_code"    => "string() #手机或者邮箱验证码",
   ];
    const SCHEMAS = [
   ];

    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $userId   = $this->auth->getUserId();
        $info     = \Model\Profile::where('user_id', $userId)->first();
        $profileParams = [
            'name'          => trim($this->request->getParam('name')),
            'nickname'      => trim($this->request->getParam('nickname')),
            'gender'        => trim($this->request->getParam('gender')),
            'qq'            => trim($this->request->getParam('qq')),
            'weixin'        => trim($this->request->getParam('wechat')),
            'skype'         => trim($this->request->getParam('skype')),
            'region_id'     => trim($this->request->getParam('city')),
            'address'       => trim($this->request->getParam('address')),
            'nationality'   => trim($this->request->getParam('nationality')),
            'birth_place'   => trim($this->request->getParam('birth_place')),
            'birth'         => trim($this->request->getParam('birth')),
            'email'         => trim($this->request->getParam('email')),
            'mobile'        => trim($this->request->getParam('mobile')),
        ];
        $mobile = $profileParams['mobile'];
        //修改手机号 或绑定手机号
        if($mobile){
            $verifyCode      = trim($this->request->getParam('verify_code'));

            // 验证手机号码是否被使用过
            $mobileEn = \Utils\Utils::RSAEncrypt($mobile);
            if(\Model\User::where('mobile', $mobileEn)
                    ->count() > 0) {
                return $this->lang->set(104);
            }
            // 验证手机验证码
            $captcha = new \Logic\Captcha\Captcha($this->ci);
            if (!$captcha->validateTextCode($mobile, $verifyCode)) {
                return $this->lang->set(106, [], [], ['mobile' => $mobile]);
            }
        }

        $profileParams = array_filter($profileParams);
        if(!$profileParams){
            return $this->lang->set(0);
        }

        $profileParams = \Utils\Utils::RSAPatch($profileParams,1);

        \Model\Profile::where('user_id', $userId)->update($profileParams);

        if($mobile) {
            \Model\User::where('id', $userId)
                ->update(['mobile' => $profileParams['mobile']]);
        }

        //注意顺序 要放到最后
        if (empty($info['mobile']) && $mobile) {
            \Model\SafeCenter::where('user_id', $userId)
                ->update(['mobile' => 1]);
            $activity = new \Logic\Activity\Activity($this->ci);
            $activity->bindInfo($userId, 1);
        }

        return $this->lang->set(0);
    }
};