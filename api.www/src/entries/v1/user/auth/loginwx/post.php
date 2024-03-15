<?php
use Utils\Www\Action;
use Respect\Validation\Validator as V;
// use Requests;
use Model\TpLogin;
use Logic\User\User;
use Utils\Utils;
use Logic\Define\Lang;
use Model\SafeCenter;
return new class extends Action
{
    const HIDDEN = true;
    const TITLE = "用户扫描微信二维码登录";
    const DESCRIPTION = "用户扫描微信二维码登录";
    const TAGS = "登录注册";
    const PARAMS = [
       "code" => "string(required) #微信登录返回code码",
        'app_id'    => 'string(required) #微信app_id',
       "agent_code" => "string(required) #代理code"
   ];
    const SCHEMAS = [
       "auth" => [
           'token'         => 'string #Token 字串',
           'expiration'    => 'int #生命周期',
           'socketToken'   => 'string #socket链接token',
           'socketLoginId' => 'string #socket链接id',
           'uuid'          => 'string #uuid',
           "info"          => [
               'avatar'         => 'string #头像',
               'had_bind_phone' => 'int #绑定手机 1是 0否'
           ]
       ]
   ];


    public function run() {
        $validator = $this->validator->validate($this->request, [
            'code' => V::notEmpty()->noWhitespace()->setName('code'),
            'app_id' => V::notEmpty()->noWhitespace()->setName('app_id'),
            'agent_code' => V::noWhitespace()->setName('agent_code'),
        ]);


        if (!$validator->isValid()) {
            return $validator;
        }

        // 查询微信配置
        $config = $this->getConfig($this->request->getParam('app_id'));
        if (!$config) {
            return $this->lang->set(16);
        }

        // 查询用户token
        $tokenInfo = $this->getAccessToken($config['app_id'], $config['access_token_url'], $config['secret'], $this->request->getParam('code'));
        if (empty($tokenInfo) || !isset($tokenInfo['access_token'])  || !isset($tokenInfo['openid'])) {
            return $this->lang->set(17);
        }
        // $tokenInfo = ['access_token' => '12312312312', 'openid' => 'f12345678901', 'avatar' => '/wx.png', 'country' => '', 'province' => '', 'city' => '', ];

        // 查询用户信息
        $userInfo = $this->getUserInfo($config['user_info_url'], $tokenInfo['access_token'], $tokenInfo['openid'], $config['type']);

        $user = new User($this->ci);
        if (!isset($userInfo['user_id'])) {

            $registerSucc = false;
            // 防止生成用户名重复
            for($i = 0; $i < 3;$i++) {
                $res = $user->register(
                    $username = 'wx_'.strtolower(Utils::randStr(6)), 
                    $password = Utils::randStr(5) . Utils::randStr(5), 
                    $invitCode = $this->request->getParam('agent_code')
                );
                if ($res instanceof Lang && !$res->getState()) {
                    $registerSucc = true;
                    break;
                } 
                usleep(1000);
            }

            if (!$registerSucc) {
                return $this->lang->set(18, [], [], ['err' => $res]);
            }
            
            $fromWheres = ['ios' => 2, 'android' => 1, 'h5' => 3, 'pc' => 4];
            // 绑定用户
            TpLogin::create([
              'user_id' => $user->getUserId(),
              'open_id' => $tokenInfo['openid'],
              'avatar' => $tokenInfo['avatar'],
              'status' => 1,
              'bind_time' => time(),
              'type' => $config['type'],
              'nick_name' => $tokenInfo['avatar'],
              'country' => $tokenInfo['country'],
              'province' => $tokenInfo['province'],
              'city' => $tokenInfo['city'],
              'union_id' => $tokenInfo['openid'],
              'fromwhere' => $fromWheres[$this->auth->getCurrentPlatform()],
            ]);
        }

        // 自动登录
        $userId = isset($userInfo['user_id']) ? $userInfo['user_id'] : $user->getUserId();
        $res = $this->auth->loginById($userId);
        if ($res->getState()) {
            return $res;
        }

        $global = \Logic\Set\SystemConfig::getModuleSystemConfig('login');
        // 校验绑定手机开关
        if (isset($global['first_WeChat_binding']) && $global['first_WeChat_binding']) {
            $safe = \Model\SafeCenter::where('user_id', $userId)->first();
            $bindPhone = $safe['mobile'] != 1 ? 1 : 0;
        } else {
            $bindPhone = 0;
        }


        $info = [  
                    'info' => [
                        'avatar' => !empty($userInfo['avatar']) ? $userInfo['avatar'] : $tokenInfo['avatar'],
                        'had_bind_phone' => $bindPhone
                    ]
                ];
        $data = $res->getData();
        $auth = array_merge($data['auth'], $info);
        $data['auth'] = $auth;
        return $data;
    }

    /**
     * 获取配置信息
     */
    protected function getConfig($appId)
    {
        $configs = $this->ci->get('settings')['weixinCredentials'];
        foreach ($configs as $v) {
            if ($v['app_id'] == $appId) {
                return $v;
            }
        }

        return false;
    }

    /**
     * 获取访问token
     * @param string $code 
     * @return array (access_token, expires_in, openid)
     * 
     */
    protected function getAccessToken($appId, $weixinAccessTokenUrl, $secret, $code){
        $url = $weixinAccessTokenUrl . 'appid=' . $appId . '&secret=' . $secret . '&code=' . $code . '&grant_type=authorization_code';
        $response = Requests::get($url, [], ['timeout' => 10]);
        return json_decode($response->body , true);
    }

    /**
     * 查询用户信息
     */
    protected function getUserInfo($weixinUserInfoUrl, $accessToken, $openId, $type) {
        $tp = TpLogin::where('status', 1)->where('type', $type)->where('union_id', $openId)->selectRaw('user_id,avatar,nick_name,province,city,country,open_id')->first();

        if (empty($tp)) {
            $url = $weixinUserInfoUrl.'access_token=' . $accessToken . '&openid=' . $openId . '&lang=zh_CN';
            $response = Requests::get($url, [], ['timeout' => 10]);
            $data =  json_decode($response->body , true);
            return [
                'nick_name' => $data['nickname'] ?? '',
                'province' => $data['province'] ?? '',
                'country' => $data['country'] ?? '',
                'city' => $data['city'] ?? '',
                'avatar' => $data['headimgurl'] ?? '',
            ];
        }
        return $tp->toArray();
    }
};