<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/3/29
 * Time: 11:28
 */

namespace Logic\Admin;

use Logic\Set\SystemConfig;
use Model\Admin\AdminUser;
use Utils\Admin\Action;
use Utils\Encrypt;
use Logic\Admin\AdminToken;
use Logic\Admin\AdminAuth;
use lib\exception\BaseException;

class BaseController extends Action
{
    protected $needAuth = true;

    protected $playLoad = [
        'uid' => 0, // 0 匿名用户
        'rid' => 0, //role id, 0 默认权限
        'type' => 1, // 1 普通用户; 2 平台用户
        'nick' => '',
        'ip' => '0.0.0.0',
        'client_id' => '',
    ];
    protected $adminToken;
    /**
     * @var string 图片域名
     */
    protected $pictureDoman;

    public function init($ci)
    {
        parent::init($ci);
        $this->adminToken = new AdminToken($this->ci);
        $this->before();

        $this->isNeedResetPassword();
        $this->isNeedResetPinPassword();
        $this->pictureDoman = $this->ci->get('settings')['upload']['dsn'][$this->ci->get('settings')['upload']['useDsn']]['domain'];
    }

    /**
     * 是否需要重设密码
     * @throws BaseException
     */
    public function isNeedResetPassword(){
        //不需要判断是否重设密码
        if(isset($this->NoValidResetPassword) && $this->NoValidResetPassword==1) return;

        $uid = $this->playLoad['uid'];
        if($uid){
            $reset_password = \DB::table('admin_user')->where('id', $uid)->value('reset_password');
            //重置密码（1：已重置，0：未重置）
            //没有重置密码的 需要重置
            if(!$reset_password){
                $newResponse = $this->response->withStatus(200);
                $newResponse = $newResponse->withJson([
                    'state' => 11050,
                    'message' => $this->lang->text(11050),
                ]);
                throw new BaseException($this->request, $newResponse);
            }
        }
    }

    /**
     * 是否需要重设pin密码
     * @throws BaseException
     */
    public function isNeedResetPinPassword(){
        $admin_pin_password_config = SystemConfig::getModuleSystemConfig('admin_pin_password');
        //开关关了，不需要判断
        if(empty($admin_pin_password_config['status']) || empty($admin_pin_password_config['reset_status'])){
            return;
        }
        //不需要判断是否重设pin密码
        if(isset($this->NoValidResetPinPassword) && $this->NoValidResetPinPassword==1) return;

        $uid = $this->playLoad['uid'];
        if($uid){
            //重置周期，1:周，2：月
            switch($admin_pin_password_config['reset_period']){
                case 1:
                    $days        = date('w') == 0 ? 6 : date('w') - 1;
                    //本周一
                    $period_time = date('Y-m-d',time()-$days*86400);
                    break;
                case 2:
                    //本月1号
                    $period_time = date('Y-m-01');
                    break;
            }

            $reset_pin_time = \DB::table('admin_user')->where('id', $uid)->value('reset_pin_time');

            //如果充值密码的时间比规定的时间小
            if(strtotime($reset_pin_time) < strtotime($period_time)){
                $newResponse = $this->response->withStatus(200);
                $newResponse = $newResponse->withJson([
                    'state' => 11051,
                    'message' => $this->lang->text(11051),
                ]);
                throw new BaseException($this->request, $newResponse);
            }
        }
    }

//需求平台那边依据token拿权限列表
    public function superAuthList()
    {
        $auth = new AdminAuth($this->ci);
        $user = AdminUser::where('id', $this->playLoad['uid'])
            ->first(['id', 'status', 'username', 'truename', 'nick', 'email', 'telephone', 'mobile', 'part', 'job', 'memo as comment', 'logintime']);
        if (!$user) {
            return $this->lang->set(-1);
        }
        //特别权限
        if (in_array($this->playLoad['rid'], [888888, 999999])) {
            $pid1 = \DB::table('admin_user_role_auth')->where('pid', 0)->pluck('id')->toArray();
            $pid2 = \DB::table('admin_user_role_auth')->whereIn('pid', $pid1)->pluck('id')->toArray();
            $router = array_merge($pid1, $pid2);
            $memberControls = $auth->getMemberControls(intval($user['role']));
            return ['list' => $user->toArray(), 'route2' => $router, 'member_control' => $memberControls];
        }
        $router = $auth->getAuths(intval($this->playLoad['rid']));
        $memberControls = $auth->getMemberControls(intval($user['role']));
        return ['list' => $user->toArray(), 'route2' => $router, 'member_control' => $memberControls];
    }

    //删除TOken
    public function deleteAdminWithToken($uid)
    {
        $auth = new AdminAuth($this->ci);
        return $auth->deleteAdminWithToken(intval($uid));
    }

    /**
     * 校验token
     * @throws BaseException
     */
    public function verifyToken()
    {

        $this->playLoad = $this->adminToken->verifyToken();
    }

    public static function getRequestDir()
    {
        global $app;
        $ver = ['v1', 'v2', 'v3', 'v4'];
        $dir = explode('/', $app->getContainer()->request->getUri()->getPath());
        $res = [];
        foreach ($dir as $v) {
            if ($v == $ver) continue;
            if (!is_numeric($v)) {
                $res[] = $v;
            }
        }
        return implode('/', $res);
    }

    /**
     * 校验权限
     * @throws BaseException
     */
    public function authorize()
    {
        if ($this->playLoad['rid'] == 888888) { //系统管理员最大权限
            return true;
        }
        if ($this->playLoad['rid'] == 999999) { //系统管理员仅限查询权限
            if ($this->request->getMethod() == 'GET') return true;
            return false;
        }
        $dir = self::getRequestDir();
        $allow = \DB::table('admin_user_role_auth')
            ->where('method', $this->request->getMethod())
            ->where('path', $dir)->value('id');
        if (!$allow) return true;
        $role_id = $this->playLoad['rid'];
        $auth = \DB::table('admin_user_role')->where('id', $role_id)->value('auth');

        if (!in_array($allow, explode(',', $auth))) {
            $newResponse = $this->response->withStatus(401);
            $newResponse = $newResponse->withJson([
                'state' => -1,
                'message' => '您无权限操作，请联系管理员添加',
                'ts' => time(),
            ]);
            throw new BaseException($this->request, $newResponse);
        }

        return true;
    }

    public function checkID($id)
    {
        if (empty($id)) {
            $newResponse = $this->response->withStatus(200);
            $newResponse = $newResponse->withJson([
                'state' => -1,
                'message' => 'id不能为空',
                'ts' => time(),
            ]);
            throw new BaseException($this->request, $newResponse);
        }

        if (is_numeric($id) && is_int($id + 0) && ($id + 0) > 0) {
            return true;
        }

        $newResponse = $this->response->withStatus(200);
        $newResponse = $newResponse->withJson([
            'state' => -1,
            'message' => 'id必须为正整数',
            'ts' => time(),
        ]);
        throw new BaseException($this->request, $newResponse);
    }

    public function makePW($password)
    {
        $salt = Encrypt::salt();

        return [md5(md5($password) . $salt), $salt];
    }

    /**
     * 【管理员角色】角色权限设置（不同客服角色对会员各个资料详细权限控制）：
     *  真实姓名（只显示姓/显示全名/修改姓名）、银行卡号（显示全部/显示部分）、通讯资料隐藏/显示（比如邮箱、QQ、微信号等）
     *
     * @param array $data
     * @param int|null $roleId
     * @return array
     */
    public function roleControlFilter(array &$data, int $roleId = null)
    {
        static $names, $cards, $privacies, $rid = null;

        if ($names == null) {
            $names = ['truename', 'accountname'];
        }
        if ($cards == null) {
            $cards = ['card', 'idcard'];
        }
        if ($privacies == null) {
            $privacies = ['email', 'mobile', 'qq', 'weixin', 'skype', 'telephone'];
        }
        if ($rid == null) {
            if (!$roleId) {
                $roleId = $this->playLoad['rid'];
//                $roleId = 1;

            }
            $controls = (new AdminAuth($this->ci))->getMemberControls($roleId);
//            print_r($controls);exit;
        }

        if (isset($controls)) {
            foreach ($data as $key => &$item) {
                if (is_array($item)) {
                    $item = $this->roleControlFilter($item, $roleId);
                } else {
                    // 如果无姓名权限，真实姓名只显示姓
                    if (in_array($key, $names, true)) {
                        if (!$controls['true_name'] && mb_strlen($item)) {
                            $item = strpos($item, ' ') !== false ? explode(' ', $item)[0] . ' ***' : mb_substr($item, 0,
                                    mb_strlen($item) == strlen($item) ? 2 : 1) . '**';
                        }
                    }
                    // 如果无卡号权限，显示两边的部份
                    if (in_array($key, $cards, true)) {
                        if (!$controls['bank_card'] && is_numeric($item)) {
                            $card = trim(chunk_split($item, 4, ' '));
                            $cardChunk = explode(' ', $card);
                            $first = array_shift($cardChunk);
                            $last = array_pop($cardChunk);
                            $item = $first . '****' . $last;
                        }
                    }
                    // 如果无个人信息权限，只显示一部份
                    if (in_array($key, $privacies, true)) {
                        if (!$controls['address_book']) {
                            if (in_array($key, ['email', 'skype']) && strlen($item)) {
                                $item = '***' . strrchr($item, '@');
                            } else {
                                if (strlen($item) > 4) {
                                    $item = substr($item, 0, 2) . '***' . substr($item, -2, 2);
                                } elseif (strlen($item)) {
                                    $item = substr($item, 0, 1) . '**' . substr($item, -1, 1);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /** 校验管理员密码 必须 数字大小写字母特殊字体组成
     * @param $password
     * @return bool
     */
    public function checkAdminPass($password)
    {
        $pattern = "/^(?![A-Za-z0-9]+$)(?![a-z0-9\W]+$)(?![A-Za-z\W]+$)(?![A-Z0-9\W]+$)[a-zA-Z0-9\W]{8,16}$/";
        if (!preg_match($pattern, $password)) {
            return false;
        }
        return true;
    }


    public function __destruct()
    {
        // TODO: Implement __destruct() method.

    }

}