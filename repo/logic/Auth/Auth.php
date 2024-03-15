<?php

namespace Logic\Auth;

use Logic\Define\CacheKey;
use Logic\Set\SystemConfig;
use Slim\Container;
use Model\User;
use Model\UserLog;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Parser;
use Hashids\Hashids;
use DB;
use Model\TrialUser;

/**
 * 权限模块
 */
class Auth extends \Logic\Logic {


    protected $client;

    protected $sign = 'I am sign';

    /**
     * token过期时间
     * @var [type]
     */
    protected $expiration = 30 * 86400;

    protected $userId = 0;

    protected $trial_status = false;

    /**
     * 更换token
     * @var float
     */
    protected $expirationRadio = 0.1;

    /**
     * 平台
     * @var [type]
     */
    protected $platforms = ['pc', 'h5', 'android', 'ios'];

    /**
     * 默认平台
     * @var string
     */
    protected $defaultPlatform = 'pc';

    /**
     * 自动token续期
     * @var boolean
     */
    protected $autoRefreshToken = true;

    /**
     * 登录组
     * @var array
     */
    protected $platformGroups = [
        'pc'      => 1,
        'h5'      => 2,
        'android' => 3,
        'ios'     => 4,
    ];
    //登录来源对应
    protected $platformIds = [
        'pc'      => 1,
        'h5'      => 2,
        'android' => 4,
        'ios'     => 3,
    ];
    //登录类型
     protected $login_type = [
        'agent_switch' => 4  //代理登录后台
        ];
    /**
     * 获取userId;
     * @return [type] [description]
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * 获取是否是试玩;
     * @return [type] [description]
     */
    public function getTrialStatus()
    {
        return $this->trial_status;
    }

    /**
     * 验证是否登录
     *
     * @return mixed
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function verfiyToken()
    {
        $authorization = isset($this->request->getHeaders()['HTTP_AUTHORIZATION']) ? current(
            $this->request->getHeaders()['HTTP_AUTHORIZATION']
        ) : ($this->request->getQueryParam('token') ?? '');

        if (empty($authorization)) {
            return $this->lang->set(11);
        }
        if (0 === stripos($authorization, 'Bearer ')) {
            $authorization = substr($authorization, 7);
        }

        try {
            $token = (new Parser())->parse($authorization);
            $trial_status = $token->getClaim('trial_status');
            $uid = $token->getClaim('uid');
            $plGid = $token->getClaim('plGid');
            $userLoginId = $token->getClaim('loginId');
            $createTime = $token->getClaim('time');
        } catch (\Exception $e) {
            $uid = 0;
        }

        // 取不出UID
        if (empty($uid)) {
            return $this->lang->set(58);
        }

        // 判断是否有禁用状态
        // $reject = $this->redis->hget(CacheKey::$perfix['userRejectLoginStatus'], $uid);
        // if (!empty($reject) && $reject == 'reject') {
        //     return $this->lang->set(23);
        // }

        // 取缓存
        $loginId = $this->redis->get(CacheKey::$perfix['token'] . '_' . $plGid . '_' . $uid);
        if (empty($loginId)) {
            return $this->lang->set(59);
        }

        // 判断登录ID是否一致
        if ($userLoginId != $loginId/* || $this->getCurrentPlatformGroupId() != $plGid*/) {
            return $this->lang->set(162);
        }

        //判断密码登陆失败，强制踢出用户登陆
        if ($trial_status == 'trial') {
            $user = TrialUser::where('id', $uid)->first();
            if(!$user){
                return $this->lang->set(3001);
            }
        } else {
            $user = User::where('id', $uid)->first();
            if (!$user || $user['state'] == 0) {
                return $this->lang->set(3001);
            }
        }

        //Token 无效
        if (!$token->verify(new Sha256(), $this->sign) || $token->isExpired()) {
            return $this->lang->set(59);
        }
        // 更换token
        if (time() - $createTime > $this->expiration * $this->expirationRadio) {
            list($token, $loginId, $time, $socketToken) = $this->getToken($uid);
            return $this->lang->set(
                99, [], [
                      'token'         => $token,
                      'expiration'    => $time + $this->expiration,
                      'socketToken'   => $socketToken,
                      'socketLoginId' => $loginId,
                  ]
            );
        }

        // 刷新最后登录时间
        $this->redis->hset(CacheKey::$perfix['userOnlineLastTime'], $uid, time());

        // 刷新token
        $this->redis->expire(CacheKey::$perfix['token'] . '_' . $plGid . '_' . $uid, $this->expiration);
        $this->userId = $uid;
        if ($trial_status == 'trial') {
            $this->trial_status = true;
        }

        return $this->lang->set(0, [], ['uid' => $uid]);
    }


    public function verfiyTokenForSb()
    {
        //沙巴用
        $authorization = isset($this->request->getHeaders()['HTTP_AUTHORIZATION']) ? current(
            $this->request->getHeaders()['HTTP_AUTHORIZATION']
        ) : ($this->request->getQueryParam('token') ?? '');

        if (empty($authorization)) {
            return $this->lang->set(11);
        }

        try {
            $token = (new Parser())->parse($authorization);
            $uid = $token->getClaim('uid');
            $plGid = $token->getClaim('plGid');
            $userLoginId = $token->getClaim('loginId');
            $createTime = $token->getClaim('time');
        } catch (\Exception $e) {
            $uid = 0;
        }

        // 取不出UID
        if (empty($uid)) {
            return $this->lang->set(58);
        }

        // 判断是否有禁用状态
        // $reject = $this->redis->hget(CacheKey::$perfix['userRejectLoginStatus'], $uid);
        // if (!empty($reject) && $reject == 'reject') {
        //     return $this->lang->set(23);
        // }

        // 更换token
        if (time() - $createTime > $this->expiration * $this->expirationRadio) {
            list($token, $loginId, $time, $socketToken) = $this->getToken($uid);
            return $this->lang->set(
                99, [], [
                      'token'         => $token,
                      'expiration'    => $time + $this->expiration,
                      'socketToken'   => $socketToken,
                      'socketLoginId' => $loginId,
                  ]
            );
        }

        $this->userId = $uid;

        return $this->lang->set(0, [], ['uid' => $uid]);
    }

    /**
     * 使用用户UID登录
     *
     * @param $uid
     * @param int $loginType
     *
     * @return mixed
     */
    public function loginById($uid, $loginType = 0)
    {
        $user = User::where('id', $uid)
                    ->first();

        if (empty($user)) {
            return $this->lang->set(51, [], [], ['uid' => $uid]);
        }

        return $this->baseLogin($user, $loginType);
    }

    /**
     * 试玩使用用户UID登录
     *
     * @param  [type] $uid [description]
     *
     * @return [type]      [description]
     */
    public function loginTrialById($uid)
    {
        $user = TrialUser::where('id', $uid)->first();
        if (empty($user)) {
            return $this->lang->set(51, [], [], ['uid' => $uid]);
        }
        return $this->baseTrialLogin($user);
    }


    /**
     * 登录接口
     *
     * @param string $username
     * @param string $password
     * @param int $loginType 登录类型  0：普通登录 1：微信登录 2：注册自动登录 4：代理后台登录
     * @param string $token
     * @param string $code
     * @param string $needCaptcha 是否需要验证码 ture:需要, false:不需要
     * @return array
     */
    public function login($username, $password, $loginType = 0, $token= '', $code = '', $needCaptcha=false, $uuid = '')
    {
        $user = User::where('name', '=', $username)
            ->select(['id','password','salt','isVertical','wallet_id','name','state','agent_switch'])->first();

        //手机号登录
        if(empty($user)) {
            $mobile = \Utils\Utils::RSAEncrypt($username);
            $user = User::where('mobile', '=', $mobile)
                ->select(['id','password','salt','isVertical','wallet_id','name','state','agent_switch'])
                ->first();
        }
        //代理登录
        if ($loginType == $this->login_type['agent_switch']){
               if(!$user->agent_switch) return $this->lang->set(196);
        }
        if (empty($user)) {
            //记录次数 判断是否是恶意行为
            \Utils\Client::addBlackIP();
            return $this->lang->set(51);
        }

        if($this->redis->get(CacheKey::$perfix['pwdErrorLimit'] . '_' . $user['id']) || $needCaptcha){
            $captcha = new \Logic\Captcha\Captcha($this->ci);
            if(!$token || !$code){
                return $this->lang->set(105);
            }

            if(!$captcha->validateImageCode($token, $code)){
                return $this->lang->set(105);
            }
        }
        $passwordError = false;
        if(!$this->verifyPass($user['password'], $password, $user['salt'], 0)){
            $passwordError = true;
            if($user['isVertical']){
                //竖版用户  取款密码登录
                if($this->verifyWithdrawPwd($user['wallet_id'], $password)){
                    $passwordError = false;
                }
            }
        }
        // 密码错误
        if ($passwordError) {
            // 登录密码错误次数判断
            $pwdError = $this->pwdErrorLimit($user['id']);
            UserLog::create(
                [
                    'user_id'   => $user['id'],
                    'name'      => $user['name'],
                    'log_value' => $this->lang->text('Login failed'),
                    'status'    => 0,
                    'log_type'  => 1,
                    'platform'  => isset($_SERVER['HTTP_PL']) ? isset($this->platformIds[$_SERVER['HTTP_PL']]) ? $this->platformIds[$_SERVER['HTTP_PL']] : 1 : 1,
                ]
            );

            $remainTimes = $pwdError['limit'] - $pwdError['times'];
            if ($remainTimes > 0) {
                //return $this->lang->set(52, [$pwdError['times'], $remainTimes]);
                return $this->lang->set(51);
            } else {
                //return $this->lang->set(53, [$pwdError['limit']]);
                return $this->lang->set(51);
            }
        }

        // 状态定义列表
        $states = [
            0 => 54,
            // 1 => 57,
            2 => 55,
            3 => 55,
            4 => 56,
        ];

        // 判断会员状态
        if (isset($states[$user['state']])) {
            //return $this->lang->set($states[$user['state']]);
            return $this->lang->set(51);
        }


        return $this->baseLogin($user, $loginType, $uuid);
    }

    /**
     * 创建token
     *
     * @param $userId
     *
     * @return array
     * @throws \Interop\Container\Exception\ContainerException
     */
    protected function getToken($userId, $trial_status = '')
    {
        $now = time();
        $loginId = uniqid();

        $plGid = $this->getCurrentPlatformGroupId();
        $plGid = 2;//只能登录一个平台 所以写死H5登录
        $token = (new Builder())->setIssuer('tc3ga.com')
                                ->setAudience('tc3ga.com')
                                ->setId('token', true)
                                ->setIssuedAt($now)
                                ->setNotBefore(60 + $now)
                                ->setExpiration($this->expiration + $now)
                                ->set('uid', $userId)
            // ->set('client', $origin)    //客户端
                                ->set('loginId', $loginId)//唯一ID
                                ->set('plGid', $plGid)// 登录平台组ID
                                ->set('time', $now)
                                ->set('trial_status', $trial_status)
                                ->sign(new Sha256(), $this->sign)
                                ->getToken()
                                ->__toString();

        $appId = $this->ci->get('settings')['pusherio']['app_id'];
        $appSecret = $this->ci->get('settings')['pusherio']['app_secret'];

        // 写入token
        $this->redis->setex(CacheKey::$perfix['token'] . '_' . $plGid . '_' . $userId, $this->expiration, $loginId);
        $hashids = new Hashids($appId . $appSecret, 8, 'abcdefghijklmnopqrstuvwxyz');
        return [$token, $loginId, $now, $hashids->encode($userId)];
    }

    /**
     * 试玩基础登录接口
     *
     * @param  object $user 试玩用户对象
     *
     * @return [type]       [description]
     */
    protected function baseTrialLogin($user)
    {
        // 创建token
        list($token, $loginId, $time, $socketToken) = $this->getToken($user['id'], $trial_status = 'trial');

        // 清险登录错误记录
        $this->redis->del(CacheKey::$perfix['pwdErrorLimit'] . '_' . $user['id']);
        $this->userId = $user['id'];
        return $this->lang->set(
            1, [],
            [
                'auth' => [
                    'token'         => $token,
                    'expiration'    => time() + $this->expiration,
                    'socketToken'   => $socketToken,
                    'socketLoginId' => $loginId,
                    'uuid'          => md5($this->sign . $user['id']),
                ],
            ]
        );
    }

    /**
     * 基础登录接口
     *
     * @param $user
     * @param int $loginType
     *
     * @return mixed
     */
    protected function baseLogin($user, $loginType = 0, $uuid = '')
    {
        //判断登录类型
        $loginType = [0 => 'Normal mode', 1 => 'Weixin mode', 2 => 'Register automatic login', 4 => 'agent automatic login'][$loginType];

        // 创建token
        list($token, $loginId, $time, $socketToken) = $this->getToken($user['id']);

        try{

            // 写入登录成功日志
            UserLog::create(
                [
                    'user_id'   => $user['id'],
                    'name'      => $user['name'],
                    'log_value' => $this->lang->text('Login success').'（' . $this->lang->text($loginType) . '）',
                    'status'    => 1,
                    'log_type'  => 1,
                    'platform'  => isset($_SERVER['HTTP_PL']) ? isset($this->platformIds[$_SERVER['HTTP_PL']]) ? $this->platformIds[$_SERVER['HTTP_PL']] : 2 : 2,
                    'version'   => \Utils\Client::getHeader('HTTP_VERSION') ? current(\Utils\Client::getHeader('HTTP_VERSION')) : NULL,

                ]
            );
            // 用户最后登录信息
            User::where('id', $user['id'])
                ->update(
                    [
                        'last_login' => time(),
                        'login_ip'   => DB::getIPv6(),
                    ]
                );
        } catch (\Exception $e){
            $this->logger->error('login error' . $e->getMessage());
        }


        //清除登录错误记录
        $this->redis->del(CacheKey::$perfix['pwdErrorLimit'] . '_' . $user['id']);

        //首次登录APP赠送
        $origins = ['pc'=>1,'h5'=>2,'ios'=>3,'android'=>4];
        $origin = isset($this->request->getHeaders()['HTTP_PL']) && is_array($this->request->getHeaders()['HTTP_PL']) ? current($this->request->getHeaders()['HTTP_PL']) : '';
        $uuid = isset($this->request->getHeaders()['HTTP_UUID']) && is_array($this->request->getHeaders()['HTTP_UUID']) ? current($this->request->getHeaders()['HTTP_UUID']) : '';
        $platform = isset($origins[$origin]) ? $origins[$origin] : 0;
        if(!empty($uuid) && ($platform == 3 || $platform == 4)){
            \Utils\MQServer::send('synAppLoginPrice', [
                'uuid'   => $uuid,
                'uid'    => $user['id'],
                'origin' => $platform,
            ]);
        }

        $this->userId = $user['id'];
        //设置H5版本缓存
        $h5_key = 'refresh_h5:'.$user['id'];
        $h5_version = \Logic\Set\SystemConfig::getModuleSystemConfig('market')['h5_version'];
        $h5_version = trim($h5_version);
        //缓存7天
        $this->ci->redis->setex($h5_key,604800,$h5_version);
        return $this->lang->set(
            1, [],
            [
                'auth' => [
                    'token'         => $token,
                    'expiration'    => time() + $this->expiration,
                    'socketToken'   => $socketToken,
                    'socketLoginId' => $loginId,
                    'uuid'          => md5($this->sign . $user['id']),
                ],
            ]
        );
    }

    /**
     * 退出登录（踢出登录）
     *
     * @param  [type] $uid [description]
     * @param  [type] $plGid  平台ID
     *
     * @return [type]      [description]
     */
    public function logout($uid, $plGid = null)
    {
        if (empty($uid)) {
            $verify = $this->verfiyToken();
            if ($verify->allowNext()) {
                $uid = $this->getUserId();
            } else {
                return $this->lang->set(60);
            }
        }

        $platformGroupsValues = $plGid === null ? array_unique(array_values($this->platformGroups)) : ((array)$plGid);
        foreach ($platformGroupsValues as $plGids) {
            $this->redis->hset(CacheKey::$perfix['userOnlineLastTime'], $uid, time() - 86400);
            $this->redis->del(CacheKey::$perfix['token'] . '_' . $plGids . '_' . $uid);
        }
        return $this->lang->set(2);
    }

    /**
     * 登录密码错误次数限制
     *
     * @param $user_id 用户id
     * @param $user_type 用户类型(会员:user,代理:agent)
     *
     * @return array(times:已输错次数,limit:最大错误次数)
     */
    protected function pwdErrorLimit($uid)
    {
        $name = CacheKey::$perfix['pwdErrorLimit'] . '_' . $uid;

        $limit = SystemConfig::getModuleSystemConfig('register')['password_login_fault'] ?? 100;

        //有效期 从错误开始 24小时
        $secends = 3600 * 24;
        $errorTimes = $this->redis->get($name);
        //var_dump($name,$errorTimes);die;
        if ($errorTimes > 0) {
            if ($errorTimes > $limit) {
                return ['times' => $errorTimes, 'limit' => $limit];
            }

            $this->redis->incr($name);
            if ($this->redis->get($name) >= $limit) {
                //停用账号
                User::where('id', '=', $uid)
                    ->update(['state' => 0]);
            }
        } else {
            $this->redis->setex($name, $secends, 1);
        }

        return ['times' => $this->redis->get($name), 'limit' => $limit];
    }


    /**
     * 验证当前用户密码
     *
     * @param string $current 当前密码
     * @param string $password 原密码
     * @param string $salt 散列码
     * @param int $vtype = 1 为自动登录，不验证密码
     *
     * @return boolean
     */
    public function verifyPass($current, $password, $salt, $vtype = 0)
    {
        return $vtype ? true : $current == md5(md5($password) . $salt);
    }

    /**
     * 竖版登录 验证取款密码
     * @param $walletId
     * @param $password
     * @return bool
     */
    public function verifyWithdrawPwd($walletId, $password){
        $funds = \Model\Funds::where('id', $walletId)->first();
        if (\Model\User::getPasword($password, $funds['salt']) != $funds['password']) {
            return false;
        }
        return true;
    }

    /**
     * 获取登录平台配置
     * @return [type] [description]
     */
    public function getPlatformGroups()
    {
        return $this->platformGroups;
    }

    /**
     * 获取当前登录平台ID
     * @return [type] [description]
     */
    public function getCurrentPlatformGroupId()
    {
        $pl = $this->getCurrentPlatform();
        return in_array(
            $pl, $this->platforms
        ) ? $this->platformGroups[$pl] : $this->platformGroups[$this->defaultPlatform];
    }

    /**
     * 获取当前登录平台
     * @return [type] [description]
     */
    public function getCurrentPlatform()
    {
        return isset($this->request->getHeaders()['HTTP_PL']) ? current(
            $this->request->getHeaders()['HTTP_PL']
        ) : $this->defaultPlatform;
    }

    /**
     * 判断是否移动端请求
     * @return boolean [description]
     */
    public function isMobilePlatform()
    {
        $pl = $this->getCurrentPlatform();
        return in_array($pl, ['h5', 'android', 'ios']) ? true : false;
    }

    /**
     * 判断是否H5端请求
     * @return boolean [description]
     */
    public function isH5Platform()
    {
        $pl = $this->getCurrentPlatform();
        return in_array($pl, ['h5']) ? true : false;
    }

    /**
     * 判断是否移动端请求
     * @return boolean [description]
     */
    public function isPcPlatform()
    {
        $pl = $this->getCurrentPlatform();
        return in_array($pl, ['pc']) ? true : false;
    }

    /**
     * 踢下线
     *
     * @param number $uid
     * @param string $type
     * @param number $expiration
     * @param string $key
     *
     * @return boolean
     */
    public function setOffLine($uid = 0, $type = 'user', $expiration = 3600, $key = 'offLine')
    {
        $key = $key . $this->getKEY($uid, $type);
        return ($this->redis->setex($key, $expiration, 1) && $this->rmCache($this->getKEY($uid, $type)));
    }

    /**
     * 生成缓存KEY
     *
     * @param number $uid
     * @param string $type
     *
     * @return string
     */
    protected function getKEY($uid = 0, $type = 'user')
    {
        if (isset($_REQUEST['origin']) && $_REQUEST['origin'] == 1)
            $this->client = 'pc';
        else
            $this->client = 'mobile';

        return md5($this->client . $type . $uid);
        //        return md5($type.$uid);
    }

    /**
     * 删除缓存
     *
     * @param string $key
     */
    protected function rmCache($key)
    {
        return $this->redis->del($key);
    }
}