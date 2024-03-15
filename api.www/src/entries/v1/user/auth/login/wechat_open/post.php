<?php

use Utils\Www\Action;
use Respect\Validation\Validator as Validator;
use Logic\User\User as User;
use Logic\Define\Lang;
use Logic\Captcha\Captcha;
use Model\TpLogin;
use Utils\Utils;

return new class extends Action
{
    const HIDDEN = true;
    const TITLE = '用户微信第三方平台登录';
    const DESCRIPTION = '用户微信第三方平台登录';
    const TAGS = "登录注册";
    const PARAMS = [
        'type'          => 'string(required) #登录类型 android ios',
        'app_id'        => 'string(required) #微信app_id',
        'openid'        => 'string(required) #用户open_id',
        'access_token'  => 'string(required) #授权信息token',
        'telphone_code' => 'string() #手机区号',
        'telphone'      => 'string() #用户手机号码',
        'code'          => 'string() #手机验证码',
        'bind_mobile'   => 'int() #是否绑定手机 1是 0否',
        'agent_code'    => 'string(required) #代理code',
    ];
    const SCHEMAS = [
        'auth' => [
            'token'         => 'string #Token 字串',
            'expiration'    => 'int #生命周期',
            'socketToken'   => 'string #socket链接token',
            'socketLoginId' => 'string #socket链接id',
            'uuid'          => 'string #uuid',
            'displayName'   => 'string #用户昵称',
        ]
    ];

    public function run() {
        $verify_params = [
            'type'         => Validator::notEmpty()
                                       ->noWhitespace()
                                       ->setName('type'),
            'app_id'       => Validator::notEmpty()
                                       ->noWhitespace()
                                       ->setName('app_id'),
            'openid'       => Validator::notEmpty()
                                       ->noWhitespace()
                                       ->setName('openid'),
            'access_token' => Validator::notEmpty()
                                       ->noWhitespace()
                                       ->setName('access_token'),
        ];

        $bind_mobile = $this->request->getParam('bind_mobile', false);

        //绑定手机号
        if ($bind_mobile === '1') {
            $verify_params = array_merge($verify_params, [
                'telphone_code' => Validator::telephoneCode()
                                            ->setName($this->lang->text("telphone code")),
                'telphone'      => $this->request->getParam('bind_mobile') != '+86' ? Validator::mobile()
                                                                                               ->setName($this->lang->text("telphone")) : Validator::chinaMobile()
                                                                                                                            ->setName($this->lang->text("telphone")),
                'code'          => Validator::captchaTextCode()
                                            ->setName($this->lang->text("captcha code")),
            ]);
        }

        $validator = $this->validator->validate($this->request, $verify_params);

        if (!$validator->isValid()) {
            return $validator;
        }

        $access_token = $this->request->getParam('access_token');
        $openid = $this->request->getParam('openid');

        $result = $this->getWechatInfo($access_token, $openid);

        //微信认证失败，直接返回错误信息
        if ($result instanceof Lang) {
            return $result;
        }

        $user = $this->getUser($openid);

        //已有绑定账号，直接登录
        if ($user) {
            return $this->auth->loginById($user['user_id'], 1);
        }

        /**
         * 微信认证成功
         * 账号未绑定，且没有提交绑定信息
         */
        if ($bind_mobile === false) {
            return [
                'status' => 0,
            ];
        }

        /**
         * 微信认证成功
         * 账号未绑定，不绑定手机直接注册
         */
        if ($bind_mobile === '0') {
            $user = $this->register($openid, $result);

            return $this->auth->loginById($user->id, 1);
        }

        /**
         * 微信认证成
         * 绑定手机同时注册
         */
        if ($bind_mobile === '1') {
            $captcha = new Captcha($this->ci);
            $mobile = $this->request->getParam('telphone_code') . $this->request->getParam('telphone');

            if (!$captcha->validateTextCode($mobile, $this->request->getParam('code'))) {
                return $this->lang->set(106);
            }

            $mobile = \Utils\Utils::RSAEncrypt($this->request->getParam('telphone'));

            $existUser = \Model\User::where('mobile', $mobile)
                                    ->first();

            if ($existUser) {
                $haveBeenBind = TpLogin::where('user_id', $existUser['id'])
                                       ->first();

                if ($haveBeenBind) {
                    return $this->lang->set(25);
                }

                $this->bindWithUser($openid, $result, $existUser);

                $user = $existUser;
            } else {
                $user = $this->register($openid, $result);
            }

            \Model\User::where('id', $user->id)
                       ->update([
                           'mobile'        => $mobile,
                           'telphone_code' => $this->request->getParam('telphone_code'),
                       ]);

            \Model\SafeCenter::where('user_id', $user->id)
                             ->update([
                                 'mobile' => '1',
                             ]);

            \Model\Profile::where('user_id', $user->id)
                          ->update([
                              'mobile' => $mobile,
                          ]);

            $displayName = mb_strlen($user->name) > 4 ? mb_substr($user->name, 0, 4) . '*****' : $user->name;

            $authInfo = $this->auth->loginById($user['id'], 1)
                                   ->getData();

            $authInfo['auth']['displayName'] = $displayName;

            return $this->lang->set(12, [], $authInfo);
        }
    }

    /**
     * 获取配置信息
     *
     * @param int $appId
     * @param string $type
     *
     * @return mixed;
     */
    protected function getConfig($appId, $type) {
        $configs = $this->ci->get('settings')['weixinCredentialOpenplatform'];

        foreach ($configs as $v) {
            if ($v['app_id'] == $appId && $type == $v['type']) {
                return $v;
            }
        }

        return false;
    }

    /**
     * 获取微信用户资料
     *
     * @param $accessToken
     * @param $openId
     *
     * @return mixed
     */
    protected function getWechatInfo($accessToken, $openId) {
        $config = $this->getConfig($this->request->getParam('app_id'), $this->request->getParam('type'));
        if (!$config) {
            return $this->lang->set(16);
        }

        try {
            //从微信获取用户信息
            $url = $config['user_info_url'] . 'access_token=' . $accessToken . '&openid=' . $openId . '&lang=zh_CN';
            $response = Requests::get($url, [], ['timeout' => 10]);
            $data = json_decode($response->body, true);

            if (isset($data['errcode']) && $data['errcode'] != 0) {
                return $this->lang->set(18);
            }

            return $data;
        } catch (\Exception $e) {
            return $this->lang->set(13);
        }
    }

    /**
     * @param $openId
     *
     * @return mixed
     */
    protected function getUser($openId) {
        $user = TpLogin::where('status', 1)
                       ->where('type', $this->request->getParam('type'))
                       ->where('union_id', $openId)
                       ->selectRaw('user_id, avatar, nick_name, province, city, country, open_id')
                       ->first();

        if ($user) {
            $user = $user->toArray();
        }

        return $user;
    }

    protected function register($openid, $wechatInfo) {
        $user = new User($this->ci);

        //防止用户名重复
        while (true) {
            $username = 'wx_' . strtolower(Utils::randStr(8));
            $password = Utils::randStr(5) . Utils::randStr(5);
            $invitCode = $this->request->getParam('agent_code');

            $result = $user->register(
                $username,
                $password,
                $invitCode
            );

            if ($result instanceof Lang && !$result->getState()) {
                break;
            }
        }

        TpLogin::create([
            'user_id'   => $user->getUserId(),
            'open_id'   => $openid,
            'avatar'    => $wechatInfo['headimgurl'] ?? '',
            'status'    => 1,
            'bind_time' => date('Y-m-d H:i:s'),
            'type'      => $this->request->getParam('type'),
            'nick_name' => $wechatInfo['nickname'] ?? '',
            'country'   => $wechatInfo['country'] ?? '',
            'province'  => $wechatInfo['province'] ?? '',
            'city'      => $wechatInfo['city'] ?? '',
            'union_id'  => $openid,
            'fromwhere' => ['ios' => 2, 'android' => 1, 'h5' => 3, 'pc' => 4][$this->request->getParam('type')],
        ]);

        //用户资料绑定活动
        (new \Logic\Activity\Activity($this->ci))->bindInfo($user->getUserId(), '');

        return \Model\User::where('id', $user->getUserId())
                          ->first();
    }

    protected function bindWithUser($openid, $wechatInfo, $user) {
        TpLogin::create([
            'user_id'   => $user['id'],
            'open_id'   => $openid,
            'avatar'    => $wechatInfo['headimgurl'] ?? '',
            'status'    => 1,
            'bind_time' => date('Y-m-d H:i:s'),
            'type'      => $this->request->getParam('type'),
            'nick_name' => $wechatInfo['nickname'] ?? '',
            'country'   => $wechatInfo['country'] ?? '',
            'province'  => $wechatInfo['province'] ?? '',
            'city'      => $wechatInfo['city'] ?? '',
            'union_id'  => $openid,
            'fromwhere' => ['ios' => 2, 'android' => 1, 'h5' => 3, 'pc' => 4][$this->request->getParam('type')],
        ]);

        //用户资料绑定活动
        (new \Logic\Activity\Activity($this->ci))->bindInfo($user['id'], '');

        return $user;
    }
};