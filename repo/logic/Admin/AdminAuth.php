<?php

namespace Logic\Admin;

use Logic\Set\SystemConfig;
use Model\Admin\AdminUser;
use Logic\Admin\Cache\AdminRedis;
use lib\exception\BaseException;
use Illuminate\Database\Capsule\Manager as Db;
use Utils\Client;

class AdminAuth extends \Logic\Logic
{

    const PREFIX_ADMIN = 'admin.cache.manager.';
    const ADMIN_USER = self::PREFIX_ADMIN . 'admin_user:';

    const KEY_ADMIN_USER = 'admin_user_cache';
    const KEY_ADMIN_USER_LPGIN_IP = 'admin_user_cache_login_ip';
    const KEY_REFRESH_TOKEN = 'admin_refresh_token';
    const KEY_ROLE_AUTH = 'admin_role_auth';
    const KEY_ROLE_AUTH_FLAT = 'admin_role_auth_flat';
    const KEY_ROLE_MEMBER_CONTROL = 'admin_role_member_control';

    const DEFAULT_EXPIRE = 3600 * 24 * 15;

    protected $adminRedisLogic;

    // 是否需要判断权限
    protected $needAuth = true;

    public function __construct($ci)
    {
        parent::__construct($ci);

        $this->adminRedisLogic = new AdminRedis($ci);
    }

    /**
     * 权限鉴定
     */
    public function authorize($rid, $flat = false)
    {
        return true;
        /**
         * @TODO:权限校验
         */
        $routes = $this->getRoutes($rid, $flat);
        $action = strtolower($this->request->getMethod());
        $origin = $_SERVER['HTTP_X_REQUEST_URI'] ?? getallheaders()['X-Request-Uri'] ?? null;
        if ($origin) {
            $route = $origin == '/' ? '/' : trim(parse_url($origin, PHP_URL_PATH), '/');
            if ($route) {
                if (isset($routes[$route])) {

                    $actions = $routes[$route];
                    $in = true;
                    switch ($action) {
                        // 删除权限
                        case 'delete':
                            $in = in_array('delete', $actions);
                            break;
                        // 查询权限
                        case 'get':
                            $in = in_array('fetch', $actions);
                            break;
                        // 添加权限
                        case 'post':
                            $in = in_array('insert', $actions);
                            break;
                        // 修改权限
                        case 'patch':
                            $in = in_array('update', $actions);
                            break;
                        // 添加/修改权限
                        case 'put':
                            // 区分insert update
                            /**
                             * 这里routes可能存在两种情况。如下:
                             *  # 添加/修改银行账户
                             *  PUT /cash/bank/account/?id\d  /cash/bank/account/me
                             *  # 修改银行账户状态
                             *  PATCH /cash/bank/account/?id\d  /cash/bank/account
                             * 这里pattern相同，array_column处理时返回的array只会保存最后一个，如上述，只会保存patch的记录，
                             * ['/cash/bank/account/?id\d'=>'/cash/bank/account']
                             * 如此就无法取到put真正需要的entry：put路径/cash/bank/account/me。如果RouteMap以entry为key，也同样会遇到这样的情况。
                             * 因此我们使用下面的方法：
                             * method+entry使key变得唯一
                             */
                            $entry = $this->context->entry;
                            $method = $entry->action;
                            $Map = array_filter(array_map(function ($route) use ($method) {
                                // 使用空格分隔比较合适，避免与url常用ascii字冲突
                                if (in_array($method, $route->method)) {
                                    return $method . ' ' . $route->entry . ' ' . $route->pattern;
                                }

                                return null;
                            }, $this->routes), function ($e) use ($entry, $method) {
                                return stripos($e, $method . ' ' . $entry->index) !== false;
                            });
                            $Map = explode(' ', array_pop($Map));
                            $Pattern = array_pop($Map);
                            // 修改
                            if (preg_match('~(/?|/*)~i', $Pattern)) {
                                $in = in_array('update', $actions);
                            } else {
                                $in = in_array('insert', $actions);
                            }
                            break;
                    }
                    if (!$in) {
                        $newResponse = $this->response->withStatus(405);
                        throw new BaseException($this->request, $newResponse);
                    }
//                            $this->X405();
//                            quit();
                }
            }
        }


    }


    public function getRoutes($roleId, $flat = false)
    {

        $result = $this->adminRedisLogic->getRoleAuth($roleId);//先取缓存

        if (!$result) {
            $result = Db::table('admin_user_role')->where('id', $roleId)->first(['id', 'auth', 'member_control']);
            $result = (array)$result;
            if ($result) {
                $result = json_decode($result['auth'], true);
            }
        } else {
            $result = json_decode($result, true);
        }

        if (!$result) {

            $result = $this->authOrigin();// 都没有就返回原始值
        }
//        print_r($result);exit;

        // 二维转一维
        if ($flat) {

            $newResult = $this->adminRedisLogic->getRoleAuthFlat($roleId);//先取缓存
            if ($newResult) {
                $newResult = json_decode($newResult, true);
            } else {

                $newResult = [];
                // 一级
                $resultTop = array_column(isset($result['routes']) ? $result['routes'] : $result, 'action', 'path');
                // 二级
                {
                    $result = array_column(isset($result['routes']) ? $result['routes'] : $result, 'children');
                    foreach ($result as $item) {
                        foreach ($item as $value) {
                            array_push($newResult, $value);
                        }
                    }
                    $newResult = array_merge($resultTop ?? [], array_column($newResult, 'action', 'path'));
                }
                $this->adminRedisLogic->saveRoleAuthFlat($roleId, json_encode($newResult));
//                $this->saveBlat($roleId, json_encode($newResult));
            }

            $result = &$newResult;
        }
        if (is_string($result)) {
            $result = json_decode($result, true);
        }
        return $result;
    }

    /**
     * 获取管理员初始化权限
     * todo: 改为前端节点配置
     *
     * @param string $name
     * @return mixed
     */
    public function authOrigin($name = '../../config/adminroutes.json')
    {
        return json_decode(file_get_contents($name), true);
    }


    /**
     * @param int $roleId
     * @param bool $flat
     * @return bool|array
     */
    public function getAuths($roleId)
    {
        $auths = explode(',',\DB::table('admin_user_role')->where('id',$roleId)->value('auth'));
        $pid1 = \DB::table('admin_user_role_auth')->whereIn('id',$auths)->distinct()->pluck('pid')->toArray();
        $pid2 = \DB::table('admin_user_role_auth')->whereIn('id',$pid1)->distinct()->pluck('pid')->toArray();
        return array_merge($pid1,$pid2);
    }

    //用户所有拥有的菜单权限id
    public function getAllAuths($roleId)
    {
        return explode(',',\DB::table('admin_user_role')->where('id',$roleId)->value('auth'));
    }

    /**
     * @param int $roleId
     * @return array|bool|mixed|string
     */
    public function getMemberControls(int $roleId)
    {
        /**
         * @var Cache $cache
         */
        $userRole = $this->getRoleMemberControl($roleId);
        if (!$userRole) {
            $userRole = Db::table('admin_user_role')->where('id', $roleId)->first(['id', 'auth', 'member_control']);
            $userRole = (array)$userRole;
            if (!empty($userRole['member_control'])) {
                $userRole = json_decode($userRole['member_control'], true);
            }
        } else {
            $userRole = json_decode($userRole, true);
        }

        if (!$userRole) {

            // 返回原始值
            $userRole = (new SystemConfig($this->ci))->memberControls();

        }

        return $userRole;
    }


    /**
     * @param int $roleId
     * @return bool|string
     */
    public function getRoleMemberControl($roleId)
    {
        $key = self::KEY_ROLE_MEMBER_CONTROL . ':' . $roleId;

        return $this->ci->redis->get($key);
    }


    /**
     * @param int $uid
     * @param array $user
     * @param int $ttl
     */
    public function saveAdminUser($uid, array $user, $ttl = 7200)
    {
        $key = self::ADMIN_USER . ':' . $uid;

        $this->redis->set($key, json_encode($user, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE));

        $this->redis->expire($key, $ttl);

    }

    /**
     * 根据用户id保存token
     */
    public function saveAdminWithToken($uid, $token = '', $ttl = 7200)
    {
        $key = self::KEY_ADMIN_USER . ':' . $uid;

        $this->redis->setex($key, $ttl, $token);

    }

    /**
     * 根据用户id保存用户登陆IP
     */
    public function saveAdminWithLoginIP($uid)
    {
        $key = self::KEY_ADMIN_USER_LPGIN_IP . ':' . $uid;

        $this->redis->set($key,Client::getIp());

    }

    /**
     * 根据用户获取用户登陆IP
     */
    public function getAdminWithLoginIP($uid)
    {
        $key = self::KEY_ADMIN_USER_LPGIN_IP . ':' . $uid;

        return $this->redis->get($key);

    }

    /*
     * @param uid 用户id
    * 根据用户id删除token 删除管理需要踢该管理员下线小需求
    */
    public function deleteAdminWithToken($uid)
    {

        $key = self::ADMIN_USER . ':' . $uid;

        $this->redis->del($key);

        return true;
    }


    /**
     * 根据用户id和token检验token是否有效
     */
    public function checkAdminWithToken($uid, $token = '')
    {

        $key = self::KEY_ADMIN_USER . ':' . $uid;
        $cur_token = $this->redis->get($key);
        if ($token !== $cur_token) {
            return false;
        }
        return true;
    }

    /**
     * 根据用户id和token检验token是否有效
     */
    public function emptyAdminWithToken($uid)
    {

        $key = self::KEY_ADMIN_USER . ':' . $uid;
        if (empty($this->redis->get($key))) {
            return true;
        }
        return false;
    }

    /**
     * 刷新token
     * @param $refreshToken
     * @param $accessToken
     * @param $expire
     * @return boolean
     */
    public function saveRefreshToken($refreshToken, $accessToken, $expire = self::DEFAULT_EXPIRE)
    {
        $cache = $this->redis;
        $key = self::KEY_REFRESH_TOKEN . ':' . $refreshToken;
        $cache->set($key, $accessToken);
        $cache->expire($key, $expire);

    }


    /**
     * update user and role
     * 更新用户的角色
     *
     * @param       $id
     * @param null $roleId
     * @param array $condition
     * @return mixed
     */
    public function updateUser($id, $roleId = null, $condition = [])
    {
        // id为1的用户为初始化用户，不可修改、删除、登入
        if ($id == 1) {
            return false;
        }
        if ($roleId) {
            Db::table('admin_user_role_relation')->where('uid', $id)->update(['rid' => $roleId]);

        }
        $rs = Db::table('admin_user')->where('id', $id)->update($condition);
//        print_r(AdminUser::toSql());

        if ($rs) {
            if ($roleId) {
                $id = intval($id);
//                $this->redis->del(self::ADMIN_USER . $id);
                $this->redis->del(self::KEY_ADMIN_USER . ':' . $id);
                $this->redis->del(self::KEY_ROLE_MEMBER_CONTROL . ':' . $id);

            }
        }

        return $rs;
    }


    public function test()
    {
        return $this->lang->set(0, [], ['uid' => 3]);
    }

    /**
     * 用户名、密码匹配
     *
     * @param $user
     * @param $password
     * @return int -1 用户名或密码错误 -2 账号被停用 -3 用户名或密码错误
     */
    public function matchUser($user, $password)
    {
        $user = AdminUser::where('status', '1')
            ->where('username', $user)
            ->find(1)
            ->toArray();

        if (is_array($user)) {

            if ($user['password'] != md5(md5($password) . $user['salt'])) {
                return $this->lang->set(10046);
            }
        }
        return $user;
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
}
