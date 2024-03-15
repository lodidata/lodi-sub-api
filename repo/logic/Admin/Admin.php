<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/3/27
 * Time: 15:59
 */

namespace Logic\Admin;

use Slim\Container;
use Model\Admin\AdminUser;
use Model\UserLog;
use Model\Label;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Parser;

class Admin extends \Logic\Logic{

    public function test(){

        return $this->lang->set(10011);
    }
    /**
     * 用户名、密码匹配
     *
     * @param $user
     * @param $password
     * @return int -1 用户名或密码错误 -2 账号被停用 -3 用户名或密码错误
     */
    public function matchUser($user,$password)
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
     * @param $userId
     * @param $pinPassword
     * @return mixed
     * @throws \Exception
     */
    public static function validPinPassword($userId, $pinPassword){
        $user = AdminUser::select(['pin_password','pin_salt'])->find($userId)
            ->toArray();

        if (is_array($user)) {
            if ($user['pin_password'] != md5(md5($pinPassword) . $user['pin_salt'])) {
                throw new \Exception('',11053);
            }
            return;
        }
        throw new \Exception('',10014);
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
