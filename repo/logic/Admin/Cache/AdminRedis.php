<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/3/27
 * Time: 15:59
 */

namespace Logic\Admin\Cache;

use Model\Admin\AdminUser;
use Illuminate\Database\Capsule\Manager as Db;
class AdminRedis extends \Logic\Logic{

    const PREFIX_ADMIN = 'admin.cache.manager.';
    const ADMIN_USER = self::PREFIX_ADMIN . 'admin_user:';
    const USER = self::PREFIX_ADMIN . 'user:';

    const KEY_ADMIN_USER          = 'admin_user_cache';
    const KEY_REFRESH_TOKEN       = 'admin_refresh_token';
    const KEY_ROLE_AUTH           = 'admin_role_auth';
    const KEY_ROLE_AUTH_FLAT      = 'admin_role_auth_flat';
    const KEY_ROLE_MEMBER_CONTROL = 'admin_role_member_control';

    const DEFAULT_EXPIRE = 3600 * 24 * 15;

    // 是否需要判断权限
    protected $needAuth = true;

    public function __construct($ci)
    {
        parent::__construct($ci);
    }

    /**
     * @param int $roleId
     * @return bool|string
     */
    public function getRoleAuth(int $roleId)
    {
        $key = self::KEY_ROLE_AUTH.':'.$roleId;

        return $this->redis->get($key);
    }

    /**
     * @param int $roleId
     * @return bool|string
     */
    public function getRoleAuthFlat(int $roleId)
    {
        $key = self::KEY_ROLE_AUTH_FLAT.':'.$roleId;

        return $this->redis->get($key);
    }

    /**
     * @param int    $roleId
     * @param string $auth
     * @param int    $expire
     * @return bool
     */
    public function saveRoleAuthFlat(int $roleId, string $auth, int $expire = self::DEFAULT_EXPIRE)
    {
        $key = self::KEY_ROLE_AUTH_FLAT.':'.$roleId;

         $this->redis->set($key, $auth);
         $this->redis->expire($key, $expire);

//         echo $key;echo "<pre/>";echo $this->redis->ttl($key);echo "<pre/>";echo $this->redis->get($key);
//         exit;
    }

    /**
     * @param int $uid
     * @return int
     */
    public function removeAdminUserCache(int $uid)
    {
        $key = self::KEY_ADMIN_USER.':'.$uid;

        return $this->redis->del($key);
    }

    public final function removeAdminUser(int $id)
    {
        $key = self::ADMIN_USER . $id;
        return $this->redis->del($key);
    }

    public final function removeUser(int $userId)
    {
        $key = self::USER . $userId;
        return $this->redis->del($key);
    }
}
