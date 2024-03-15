<?php

use Utils\Www\Action;
use Respect\Validation\Validator as Validator;

return new class extends Action
{
    const TOKEN = true;
    const TITLE = '安全中心-绑定手机号';
    const DESCRIPTION = "返回状态180 绑定成功";
    const TAGS = "安全中心";
    const PARAMS = [
        'telphone_code' => 'string(required) #区号',
        'telphone'      => 'string(required) #用户手机号码',
        'code'          => 'string(required) #手机验证码',
        "bind_from"     => "string() #绑定来源 默认为空 微信wx",
        "bind_confirm"  => "string() #确认绑定 yes no 默认为空"
    ];
    const SCHEMAS = [
    ];

    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        if ($this->request->getParam('telphone_code') != '+86') {
            $validator = $this->validator->validate($this->request, [
                //'telphone_code' => Validator::telephoneCode()
                  //                          ->setName($this->lang->text("telphone code")),
                'telphone'      => Validator::mobile()
                                            ->setName($this->lang->text("telphone")),
                'code'          => Validator::captchaTextCode()
                                            ->setName($this->lang->text("captcha code")),
            ]);
        } else {
            $validator = $this->validator->validate($this->request, [
                'telphone_code' => Validator::telephoneCode()
                                            ->setName($this->lang->text("telphone code")),
                'telphone'      => Validator::chinaMobile()
                                            ->setName($this->lang->text("telphone")),
                'code'          => Validator::captchaTextCode()
                                            ->setName($this->lang->text("captcha code")),
            ]);
        }

        if (!$validator->isValid()) {
            return $validator;
        }

        $captcha = new \Logic\Captcha\Captcha($this->ci);
        //$mobile = $this->request->getParam('telphone_code') . $this->request->getParam('telphone');
        $mobile = $this->request->getParam('telphone');
        $userId = $this->auth->getUserId();
        $bind_from = $this->request->getParam('bind_from', false);
        $bind_confirm = $this->request->getParam('bind_confirm', false);

        if (!$captcha->validateTextCode($mobile, $this->request->getParam('code'))) {
            return $this->lang->set(106);
        }

        $user = \Model\User::where('id', $userId)
                           ->first();

        $id = 2;
        $safetyKey = $this->redis->get(\Logic\Define\CacheKey::$perfix['userSafety'] . '_' . $id . '_' . $userId);
        //可以随便换绑手机号
        /*if (!empty($user['mobile']) && empty($safetyKey)) {
            return $this->lang->set(172);
        }*/

        $mobile = \Utils\Utils::RSAEncrypt($this->request->getParam('telphone'));

        $exist_user = \Model\Profile::where('mobile', $mobile)
                                 ->first();

        if ($exist_user) {
            $display_name = mb_strlen($exist_user->name) > 4 ? mb_substr($exist_user->name, 0, 4) . '*****' : $exist_user->name;
        } else {
            $display_name = mb_strlen($user->name) > 4 ? mb_substr($user->name, 0, 4) . '*****' : $user->name;
        }


        if ($exist_user && !$bind_confirm && !$bind_from) {
            return $this->lang->set(171);
        }

        //微信绑定
        if ($exist_user && $bind_from == 'wx') {
            if ($exist_user->id == $user->id) {
                return [
                    'id'          => $exist_user->id,
                    'name'        => $display_name,
                    'message'     => $this->lang->text("User already associated"),
                    'has_related' => 1,
                ];
            } else {
                return [
                    'id'           => $exist_user->id,
                    'name'         => $display_name,
                    'message'      => $this->lang->text("User to be associated"),
                    'need_related' => 1,
                ];
            }
        }

        if ($exist_user && $bind_confirm = 'yes') {
            if ($exist_user->id == $user->id) {
                return [
                    'id'          => $exist_user->id,
                    'name'        => $display_name,
                    'message'     => $this->lang->text("User already associated"),
                    'has_related' => 1,
                ];
            }

            $wx_login = \Model\TpLogin::where('user_id', $userId)
                                      ->first();

            if (!$wx_login) {
                return $this->lang->set(182);
            }

            $openid = $wx_login->open_id ?? '';
            $avatar = $wx_login->avatar ?? '';


            \Model\TpLogin::where('open_id', $openid)
                          ->update([
                              'user_id' => $user->id,
                              'avatar'  => $avatar,
                          ]);

            $result = $this->auth->loginById($user->id);

            return $result;
        }

        \Model\User::where('id', $userId)
                   ->update(['mobile' => $mobile]);

        \Model\Profile::where('user_id', $userId)
                      ->update(['mobile' => $mobile]);

        \Model\SafeCenter::where('user_id', $userId)
                         ->update(['mobile' => 1]);

        if (empty($user['mobile'])) {
            $activity = new \Logic\Activity\Activity($this->ci);
            $activity->bindInfo($userId, 1);
        }

        return $this->lang->set(180);
    }
};