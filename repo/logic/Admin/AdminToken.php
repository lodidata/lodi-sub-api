<?php

namespace Logic\Admin;

use Logic\Admin\Cache\AdminRedis;
use Logic\Set\SystemConfig;
use Model\Admin\AdminUser;
use Utils\Client;
use Illuminate\Database\Capsule\Manager as Capsule;
use lib\exception\BaseException;
use DB;
use Utils\Utils;
use Utils\GeoIP;

/**
 * json web token
 * 保证web api验证信息不被篡改
 *
 * @auth *^-^*<dm>
 * @copyright XLZ CO.
 * @package
 * @date 2017/5/3 8:59
 */
class AdminToken extends \Logic\Logic
{

    const KEY = 'this is secret use for jwt';

    const EXPIRE = 86400 * 7;

    protected $Db;

    protected $adminAuth;

    protected $playLoad = [
        'uid' => 0, // 0 匿名用户
        'rid' => 0, //role id, 0 默认权限
        'type' => 1, // 1 普通用户; 2 平台用户
        'nick' => '',
        'ip' => '0.0.0.0',
        'client_id' => '',
    ];

    public function __construct($ci)
    {
        parent::__construct($ci);
        $this->Db = new Capsule();
        $this->Db->setFetchMode(\PDO::FETCH_ASSOC);
        $this->adminAuth = new AdminAuth($ci);
    }

    /**
     * 生成json web token(jwt)
     *
     * 由三部分组成：分别是处理过Header Payload Signature，各段之间通过点号(.)相连，形成：xxxxx.yyyyy.zzzzz
     * header，基本不变:
     * {
     *      "alg": "HS256",
     *      "typ": "JWT"
     * }
     *
     * Payload，形式：
     * {
     *      "iss": "lxz",
     *      "exp": "125444741",
     *      "usr": "app",
     *      "uuid": "8sdf7878"
     * }
     *
     * @param array $userData
     * @param string $publicKey 用户公钥
     * @param int $ext
     *
     * @return string
     */
    public function createToken(array $data = [], $publicKey = self::KEY, $ext = self::EXPIRE, $digital = '')
    {
        $user = AdminUser::where('username', $data['username'])
            ->first(['id', 'status', 'salt', 'password', 'username', 'truename', 'nick', 'email', 'telephone', 'mobile', 'part', 'job', 'memo as comment', 'logintime']);
        if (empty($user)) {
            return $this->lang->set(10046);
        }
        if ($user['status'] !== 1) {
            return $this->lang->set(10549);
        }
        if ($user['password'] != md5(md5($data['password']) . $user['salt']) && !defined('SUPER_USER')) {
            if($this->pwdErrorLimit($user->id)){
                return $this->lang->set(886, ['今日错误次数超过10次，禁止登录后台']);
            }
            return $this->lang->set(10046);
        }
        unset($user['salt']);
        unset($user['password']);

        $query = \DB::table('admin_user_google_manage')->where('admin_id', 10000)->first();
        //谷歌验证器是否开启
//        if ($query->authorize_state == 1 && !defined('SUPER_USER')) {
        if ($query->authorize_state == 1) {
            $GoA = new GoogleAuthenticator();
            //验证谷歌授权码
            if (isset($data['googleCode']) && $data['googleCode'] != '') {
                if ($goUser = \DB::table('admin_user_google_manage')->where('admin_id', $user['id'])->first()) {
                    if (!$GoA->verifyCode($goUser->secret, (string)$data['googleCode'], 2)) {  //校验googleCode
                        return $this->lang->set(10047);
                    }
                    \DB::table('admin_user_google_manage')->where('id', $goUser->id)->update(['login_rate' => \DB::raw('login_rate + ' . 1), 'authorize_state' => 1]);
                } else {
                    return $this->lang->set(-2);
                }

            } else {
                //管理员是否绑定谷歌验证器
                $goUser = \DB::table('admin_user_google_manage')->where('admin_id', $user['id'])->first();
                if ($goUser && $goUser->authorize_state == 1) {
                    return $this->lang->set(0, [], array('status' => 1));
                } else {
                    $secret = $GoA->createSecret();
                    $qrCodeUrl = $GoA->getQRCodeGoogleUrl($data['username'], $secret);
                    if ($goUser) {
                        \DB::table('admin_user_google_manage')->where('id', $goUser->id)->update(['secret' => $secret]);
                    } else {
                        $googleAdminArr = array(
                            'id' => null,
                            'admin_id' => $user['id'],
                            'admin_name' => $data['username'],
                            'secret' => $secret
                        );
                        \DB::table('admin_user_google_manage')->insert($googleAdminArr);
                    }
                    return $this->lang->set(0, [], array('status' => 0,'qrCodeUrl' => $qrCodeUrl));
                }
            }
//        } elseif(!defined('SUPER_USER')) {
        } else {
            //未开启谷歌验证器时校验登录界面验证码
            $val = (new \Logic\Captcha\Captcha($this->ci))->validateImageCode($data['token'], $data['code']);
            if (!$val) {
                //return $this->lang->set(10045);
            }
        }

        $role = $this->Db->table('admin_user_role_relation')->where('uid', $user['id'])->value('rid');
        // 如果缺少role，则为0
        $user['role'] = $role ?? 0;

        $userData = [
            'uid' => self::fakeId(intval($user['id']), $digital),
            'role' => self::fakeId(intval($user['role']), $digital),
            'nick' => $data['username'],
            'type' => 1, //普通用户
            'ip' => Client::getIp(),//'192.168.10.171'
            'mac' => Client::ClientId(),
        ];

        // 1、生成header
        $header = ['alg' => "HS256", 'typ' => "JWT"];
        $header = base64_encode(json_encode($header));
        // 2、生成payload
        $payload = base64_encode(json_encode(array_merge(["iss" => "lxz", "exp" => time() + $ext], $userData)));
        // 3、生成Signature
        $signature = hash_hmac('sha256', $header . '.' . $payload, $publicKey, false);
        $token = $header . '.' . $payload . '.' . $signature;
        //若IP变动则自动需要重新登陆
        $saveToken = $header . '.' . $payload . '.' . $signature;
        $routes = $this->adminAuth->getAuths(intval($user['role']));
        $all_routes = $this->adminAuth->getAllAuths(intval($user['role']));
        //权限特殊处理
        $special_auths = explode(',',\DB::table('admin_user_role')->where('id',intval($user['role']))->value('auth'));
        $special_routes = [];
        if(!empty($special_auths)){
            $special_routes = array_values(array_intersect($special_auths,[177,179,181]));
            foreach($special_routes as $key => &$val){
                $val = (int)$val;
            }
        }

        $memberControls = $this->adminAuth->getMemberControls(intval($user['role']));

        // fixme 踢下线需要cache
        $this->adminAuth->saveAdminWithToken($user['id'], $saveToken, $ext);
        // save refresh token
        $rs['refresh_token'] = uniqid();
        $rs['expire'] = time() + $ext;

        $this->adminAuth->saveRefreshToken($rs['refresh_token'], $saveToken, $ext);

        DB::table('admin_user')->where('id', $user['id'])->update(['loginip' => $userData['ip'], 'logintime' => date('Y-m-d H:i:s')]);
        $this->adminAuth->saveAdminWithLoginIP($user['id']);
        $this->adminAuth->updateUser($user['id'], null,
            ['logintime' => date('Y-m-d H:i:s'), 'updated' => date('Y-m-d H:i:s')]);

        // 4、返回结果
        return $this->lang->set(1, [], ['token' => $token, 'list' => $user, 'route2' => $routes, 'all_routes'=>$all_routes, 'special_routes' => $special_routes,
            'member_control' => $memberControls, 'refresh_token' => $rs['refresh_token'], 'expire' => $rs['expire']]);
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
        $name = \Logic\Define\CacheKey::$perfix['pwdErrorLimit'] . '_admin_' . $uid;
        //有效期 从错误开始 24小时
        $secends = 3600 * 24;
        $errorTimes = $this->redis->get($name);

        if (!empty($errorTimes)) {
            $this->redis->incr($name);
            if ($this->redis->get($name) >= 10) {
                return true;//禁止登录
            }
        } else {
            $this->redis->setex($name, $secends, 1);
        }
        return false;
    }

    public function limitCurrentIp(string $username = '') {
        //ip白名单登录限制
        $settings = SystemConfig::getModuleSystemConfig('login');
        $ip_limit_whitelist = $settings['ip_limit_whitelist'] ;
        if ($ip_limit_whitelist) {
            $ipExist = (new \Model\IpLimit())->where('ip', Utils::RSAEncrypt(\Utils\Client::getIp()))
                ->count();

            if ($ipExist <= 0) {
//                return $this->lang->set(10614);
                return 10614;
            }
        }

        $ip_limit_china =  $settings['IP_limit'] ;
        if ($ip_limit_china) {
            $geoipObject = new GeoIP();
            $gi = $geoipObject->geoip_open(false, GEOIP_STANDARD);
            $country_code = $geoipObject->geoip_country_code_by_addr($gi, \Utils\Client::getIp());
            $country_name = $geoipObject->geoip_country_name_by_addr($gi, \Utils\Client::getIp());
            $geoipObject->geoip_close($gi);

            if ($country_code == 'CN' || $country_name == 'China') {

//                return $this->lang->set(10044);
                return 10044;
            }
        }
        return false;
    }

    /**
     * 伪uid
     *
     * @param int $uid
     * @param int $digital
     * @return int
     */
    public static function fakeId(int $uid, int $digital)
    {
        return ~$digital - $uid;
    }

    /**
     * 用户名、密码匹配
     *
     * @param $user
     * @param $password
     * @return int -1 用户名或密码错误 -2 账号被停用 -3 用户名或密码错误
     */
    public function matchUser($user_id, $password)
    {
        $user = AdminUser::where('status', '1')->find($user_id)->toArray();

        if (is_array($user)) {

            if ($user['password'] != md5(md5($password) . $user['salt'])) {
                return $this->lang->set(10046);
            }
        }
        return $user;
    }

    /**
     * token校验
     * @return array
     * @throws BaseException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function verifyToken()
    {

        if (!$this->playLoad['rid'] || !$this->playLoad['uid']) {

            $config = $this->ci->get('settings')['jsonwebtoken'];
            $token = $this->getToken($config['public_key']);;

        }
        return $this->playLoad;
    }

    /**
     * 获取头部token信息
     * @param string $publicKey
     * @throws BaseException
     * @throws \Interop\Container\Exception\ContainerException
     */
    protected function getToken($publicKey = self::KEY)
    {
        $header = $this->request->getHeaderLine('Authorization');
        $header = $header ? $header : $this->request->getQueryParam('token');
        $config = $this->ci->get('settings')['jsonwebtoken'];
        // 判断header是否携带token信息
        if (!$header) {
            $newResponse = createRsponse($this->response, 401, 10041, '缺少验证信息！');
            throw new BaseException($this->request, $newResponse);
        }
        $token = substr($header, 7);
        if ($token && $data = $this->decode($token, $publicKey)) {
            //判定IP是否在白名单之内
//            if($this->limitCurrentIp($data['nick']) !== false && !defined('SUPER_USER')) {
            if($this->limitCurrentIp($data['nick']) !== false) {
                //退出日志
                (new \Logic\Admin\Log($this->ci))->create(null, $data['nick'], \Logic\Admin\Log::MODULE_USER, '退出系统', '退出系统', $data['nick'].'退出系统',1,'IP未在白名单');
                $newResponse = createRsponse($this->response, 401, 10041, '未在IP白名单内');
                throw new BaseException($this->request, $newResponse);
            }
            $uid = $this->originId($data['uid'], $config['uid_digital']);
            $role = $this->originId($data['role'] ?? 0, $config['uid_digital']);
            if ($this->adminAuth->emptyAdminWithToken($uid)) {
                //退出日志
                (new \Logic\Admin\Log($this->ci))->create(null, $this->playLoad['nick'], \Logic\Admin\Log::MODULE_USER, '退出系统', '退出系统', $this->playLoad['nick'].'退出系统',1,'登陆失效');
                $newResponse = createRsponse($this->response, 401, 10041, '已退出');
                throw new BaseException($this->request, $newResponse);
            }
            //操作IP和登陆IP不一致
            //需求管理平台进入
            /*$allow = $this->redis->get(\Logic\Define\CacheKey::$perfix['UserAdminAccess'] . $uid);
            if(!$allow && $this->adminAuth->getAdminWithLoginIP($uid) != Client::getIp()) {
                //退出日志
                (new AdminRedis($this->ci))->removeAdminUserCache($uid);
                (new \Logic\Admin\Log($this->ci))->create(null, $data['nick'], \Logic\Admin\Log::MODULE_USER, '退出系统', '退出系统', $data['nick'].'退出系统',1,'IP变动 -- 与登陆IP不一致');
                $newResponse = createRsponse($this->response, 401, 10041, 'IP地址变动');
                throw new BaseException($this->request, $newResponse);
            }*/
            $cache = SystemConfig::getModuleSystemConfig('login');
            $login_check = $cache['Duplicate_LoginCheck'];
            if ($login_check && !$this->adminAuth->checkAdminWithToken($uid, $token)) {
                //退出日志
                (new \Logic\Admin\Log($this->ci))->create(null, $data['nick'], \Logic\Admin\Log::MODULE_USER, '退出系统', '退出系统', $data['nick'].'退出系统',1,'账号在其它地方登陆');
                $newResponse = createRsponse($this->response, 401, 10041, '账号在其它地方登陆');
                throw new BaseException($this->request, $newResponse);
            }

            //获取消息接口不更新token过期时间
            if($_SERVER['REQUEST_URI'] != '/message/num'){
                //更新缓存
                $this->adminAuth->saveAdminWithToken($uid, $token, $config['expire']);
            }

            $nick = $data['nick'];
            $this->playLoad = array_merge($this->playLoad, ['rid' => $role, 'uid' => $uid, 'nick' => $nick, 'ip' => Client::getIp()]);
            $GLOBALS['playLoad'] = $this->playLoad;
        } else {
            $newResponse = createRsponse($this->response, 401, 10041, '验证信息不合法！');
            throw new BaseException($this->request, $newResponse);
        }


    }

    /**
     * @param string $token
     * @param string $publicKey
     *
     * @return array|null
     * @see https://jwt.io/introduction/
     */
    public function decode($token, $publicKey = self::KEY)
    {
        if (substr_count($token, '.') != 2) {
            return null;
        }
        list($header, $payload, $signature) = explode('.', $token, 3);

        $_header = json_decode(base64_decode($header, true), true);
        $_payload = json_decode(base64_decode($payload, true), true);

        if (hash_hmac('sha256', $header . '.' . $payload, $publicKey, false) != $signature) {
            //退出日志
            (new \Logic\Admin\Log($this->ci))->create(null, $this->playLoad['nick'], \Logic\Admin\Log::MODULE_USER, '退出系统', '退出系统', $this->playLoad['nick'].'退出系统',1,'登陆失效');
            $newResponse = createRsponse($this->response, 401, 10041, '验证不通过！');
            throw new BaseException($this->request, $newResponse);
        }
        // 是否过期
//        if ($_payload['exp'] <= time()) {
//            //退出日志
//            (new \Logic\Admin\Log($this->ci))->create(null, $this->playLoad['nick'], \Logic\Admin\Log::MODULE_USER, '退出系统', '退出系统', $this->playLoad['nick'].'退出系统',1,'登陆失效');
//            $newResponse = createRsponse($this->response, 401, 10041, '登录超时！');
//            throw new BaseException($this->request, $newResponse);
//        }

        return $_payload;
    }

    /**
     * 原uid
     *
     * @param int $fakeId
     * @param int $digital
     * @return int
     */
    public function originId($fakeId, $digital)
    {
        return ~($fakeId + $digital);
    }

    /**
     * get user data
     *
     * @param string $publicKey
     * @return mixed|null
     */
    public function getPayload($publicKey = self::KEY)
    {
        $token = $this->getToken($publicKey);
        if (!$token) {
            return null;
        }

        return $token['payload'];
    }
}
