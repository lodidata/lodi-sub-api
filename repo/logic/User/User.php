<?php

namespace Logic\User;

use Logic\Set\SystemConfig;
use Model\Admin\Funds;
use Model\Admin\GameMenu;
use Model\Admin\Message;
use Model\FundsDealLog;
use Model\Profile;
use Model\UserData;
use Model\UserLevel;
use Respect\Validation\Validator as V;
use DB;
use Utils\Utils;

/**
 * 用户模块
 */
class User extends \Logic\Logic
{

    protected $username = '';

    protected $password = '';

    protected $userId = 0;
    //登录来源对应
    protected $platformIds = [
        'pc'      => 1,
        'h5'      => 2,
        'android' => 4,
        'ios'     => 3,
    ];

    /**
     * 账号密码注册
     *
     * @param  [type] $username  [description]
     * @param  [type] $password  [description]
     * @param  string $invitCode [description]
     * @param  string $agentData [注册代码时默认的返佣率]
     *
     * @return [type]            [description]
     */
    public function register($username, $password, $invitCode = '', $agentData = '', $isTest = 0)
    {
        return $this->registerByMobile($username, $password, $telCode = '', $telphoneCode = '', $mobile = '', $invitCode, $checkType = 2, 0, $agentData, $isTest);
    }

    /**
     * 试玩用户注册
     *
     * @param  string $token  验证码token
     * @param  int $code  用户输入的验证码
     *
     * @return [type] [description]
     */
    public function registerByTryToPlay($token = '', $code = '')
    {
        $key = \Logic\Define\CacheKey::$perfix['tryToPlay'] . \Utils\Client::getIp();
        $count = (int)$this->redis->get($key);
        if ($count >= 5) { //超过次数就需要输入图形验证码
            $captcha = new \Logic\Captcha\Captcha($this->ci);
            if (!$captcha->validateImageCode($token, $code)) {
                return $this->lang->set(105);
            }
        }

        $this->redis->incr($key);
        $this->redis->expire($key, 86400);
        //试玩用户注册
        return $this->registerTrialByMobile($username = 'Guest' . intval(time()));
    }

    /**
     * 试玩用户注册
     *
     * @param   string  $username   用户名
     * @return [type] [description]
     */
    public function registerTrialByMobile($username)
    {
        $this->userId = 0;
        $this->username = $username;

        try {
            $this->db->getConnection()->beginTransaction();
            if ($this->db->getConnection()->transactionLevel()) {
                // 创建钱包表
                $walletId = \Model\TrialFunds::create(['comment' => $username])->id;
                //获取来源   android  若渠道名CHANNELNAME为gf  则是标识 APPLICATIONID 是唯一，否则  渠道名就是唯一
                $origins = ['pc' => 1, 'h5' => 2, 'ios' => 3, 'android' => 4];
                $origin = isset($this->request->getHeaders()['HTTP_PL']) && is_array($this->request->getHeaders()['HTTP_PL']) ? current($this->request->getHeaders()['HTTP_PL']) : '';
                $origin_memo = isset($this->request->getHeaders()['HTTP_CHANNELNAME']) && is_array($this->request->getHeaders()['HTTP_CHANNELNAME']) ? current($this->request->getHeaders()['HTTP_CHANNELNAME']) : '';
                if ($origin_memo == 'gf') {
                    $origin_memo = isset($this->request->getHeaders()['HTTP_APPLICATIONID']) && is_array($this->request->getHeaders()['HTTP_APPLICATIONID']) ? current($this->request->getHeaders()['HTTP_APPLICATIONID']) : '';
                }
                // 创建账号
                $userId = \Model\TrialUser::create([
                    'wallet_id'     => $walletId,
                    'name'          => $username,
                    'origin'        => isset($origins[$origin]) ? $origins[$origin] : 0,
                    'origin_memo'   => $origin_memo,
                ])->id;
                $this->userId = $userId;

                // 提交事务
                $this->db->getConnection()->commit();

                return $this->lang->set(0, [], ['uid' => $userId]);
            }
        } catch (\Exception $e) {
            $this->db->getConnection()->rollback();
            $this->logger->error("注册失败 " . $e->getMessage());
        }

        return $this->lang->set(108);
    }


    /**
     * 手机号码注册
     *
     * @param  [type]  $username     [description]
     * @param  [type]  $password     [description]
     * @param  [type]  $telCode      [description]
     * @param  [type]  $telphoneCode [description]
     * @param  [type]  $mobile       [description]
     * @param  string $invitCode [description]
     * @param  integer $checkType [description]
     *
     * @return [type]                [description]
     */
    public function registerByMobile($username, $password, $telCode, $telphoneCode, $mobile, $invitCode = '', $checkType = 1, $tags = 0, $agentData = '', $isTest = 0)
    {

        $this->userId = 0;
        $this->username = $username;
        $this->password = $password;

        if ($checkType == 1) {
            if ($telphoneCode != '+86') {
                $validate_params = compact(['username', 'password', 'telCode', 'telphoneCode', 'mobile']);
                $validate_rules  = [
                    'username'     => V::username()
                        ->noWhitespace()->length(6, 16)
                        ->setName($this->lang->text("username")),
                    'password'     => V::password()
                        ->setName($this->lang->text("password")),
                    'mobile'       => V::mobile()
                        ->setName($this->lang->text("telphone")),
                    'telCode'      => V::captchaTextCode()
                        ->setName($this->lang->text("sms code")),
                    'telphoneCode' => V::telephoneCode()
                        ->setName($this->lang->text("telphone code"))
                ];

                $validator = $this->validator->validate($validate_params, $validate_rules);
            } else {
                $validate_params = compact(['username', 'password', 'telCode', 'telphoneCode', 'mobile']);
                $validate_rules  = [
                    'username'     => V::username()
                        ->noWhitespace()->length(6, 16)
                        ->setName($this->lang->text("username")),
                    'password'     => V::password()
                        ->setName($this->lang->text("password")),
                    'mobile'       => V::chinaMobile()
                        ->setName($this->lang->text("telphone")),
                    'telCode'      => V::captchaTextCode()
                        ->setName($this->lang->text("sms code")),
                    'telphoneCode' => V::telephoneCode()
                        ->setName($this->lang->text("telphone code"))
                ];

                $validator = $this->validator->validate($validate_params, $validate_rules);
            }
        } else {
            $validate_params = compact(['username', 'password']);
            $validate_rules  = [
                'username'  => V::username()
                    ->setName($this->lang->text("username")),
                'password'  => V::password()
                    ->setName($this->lang->text("password"))
            ];

            $validator = $this->validator->validate($validate_params, $validate_rules);
        }

        if ($invitCode) {
            $validate_params['invitCode'] = $invitCode;
            $validate_rules['invitCode']  = V::invitCode()
                ->setName($this->lang->text("invit Code"));
        }

        if (!$validator->isValid()) {
            return $validator;
        }

        // 验证账号是否已注册
        if (
            \Model\User::where('name', $username)
            ->count() > 0
        ) {
            return $this->lang->set(107, [], [], [
                'count' => \Model\User::where('name', $username)
                    ->count(), 'username' => $username,
            ]);
        }

        //限制注册人数
        //        if(!$this->registerLimitCountIp()){
        //            return $this->lang->set(188);
        //        }

        //ip限制开关
        if (!$this->registerLimitIp()) {
            return $this->lang->set(189);
        }

        $mobileEn = '';
        if ($checkType == 1) {
            // 验证手机号码是否被使用过
            $mobileEn = \Utils\Utils::RSAEncrypt($mobile);
            if (
                \Model\User::where('mobile', $mobileEn)
                ->count() > 0
            ) {
                return $this->lang->set(104);
            }

            // 验证手机验证码
            $captcha = new \Logic\Captcha\Captcha($this->ci);
            if($this->registerVerify()){
                if (!$captcha->validateTextCode($telphoneCode . $mobile, $telCode)) {
                    return $this->lang->set(106, [], [], ['mobile' => $telphoneCode . $mobile]);
                }
            }

        }

        // 查找代码号ID (改人人代理？？？)
        try {
            $this->db->getConnection()
                ->beginTransaction();

            $conf = $this->ci->get('settings')['website'];

            if ($this->db->getConnection()
                ->transactionLevel()
            ) {

                // 创建钱包表
                $walletId = \Model\Funds::create(['comment' => $username])->id;
                // 创建子钱包
                $partners = GameMenu::where('pid', '!=', 0)
                    ->where('type', '!=', 'ZYCPSTA')
                    ->where('type', '!=', 'ZYCPCHAT')
                    ->where('switch', 'enabled')
                    ->groupBy('alias')
                    ->get()->toArray();
                if (!empty($partners)) {
                    foreach ($partners as $v) {
                        $v = (array)$v;
                        //$fundsChildData = ['pid' => $walletId, 'game_type' => $v['alias'], 'name' => $v['name']];
                        $fundsChildData = ['pid' => $walletId, 'game_type' => $v['alias'], 'name' => $v['alias']];
                        \Model\FundsChild::create($fundsChildData);
                    }
                }

                // 获取默认层级
                $levelId = \Model\UserLevel::orderBy('level', 'ASC')->value('level');
                $levelId = $levelId ?? 1;
                if (isset($conf['registerTags']) && $tags == 0 && $isTest == 0) {
                    $tags = $conf['registerTags'];
                } else if ($isTest == '1') {
                    $tags = 4;
                }
                //获取来源   android  若渠道名CHANNELNAME为gf  则是标识 APPLICATIONID 是唯一，否则  渠道名就是唯一
                $origins = ['pc' => 1, 'h5' => 2, 'ios' => 3, 'android' => 4];
                $origin = isset($this->request->getHeaders()['HTTP_PL']) && is_array($this->request->getHeaders()['HTTP_PL']) ? current($this->request->getHeaders()['HTTP_PL']) : '';
                $origin_memo = isset($this->request->getHeaders()['HTTP_CHANNELNAME']) && is_array($this->request->getHeaders()['HTTP_CHANNELNAME']) ? current($this->request->getHeaders()['HTTP_CHANNELNAME']) : '';
                if ($origin_memo == 'gf') {
                    $origin_memo = isset($this->request->getHeaders()['HTTP_APPLICATIONID']) && is_array($this->request->getHeaders()['HTTP_APPLICATIONID']) ? current($this->request->getHeaders()['HTTP_APPLICATIONID']) : '';
                }
                $rakeBack = SystemConfig::getModuleSystemConfig('rakeBack');
                // 创建账号
                $param = $this->request->getParams();
                $userId = \Model\User::create([
                    'telphone_code' => $telphoneCode,
                    'wallet_id'     => $walletId,
                    'mobile'        => $mobileEn,
                    'name'          => $username,
                    'invit_code'    => $invitCode,
                    'tags'          => $tags,
                    'password'      => $password,
                    'ranting'       => $levelId,
                    'source'        => 'register',
                    'origin'        => isset($origins[$origin]) ? $origins[$origin] : 0,
                    'origin_memo'   => $origin_memo,
                    'agent_switch'   => $rakeBack['agent_switch'],
                    'email' => isset($param['email']) && trim($param['email']) ? \Utils\Utils::RSAEncrypt($param['email']) : "",
                    'wechat' => isset($param['weixin']) && trim($param['weixin']) ? \Utils\Utils::RSAEncrypt($param['weixin']) : '',
                    'is_verify' => $this->registerVerify() ? 1 :0
                    //                    'is_test'=> $isTest,
                ])->id;
                $this->userId = $userId;

                // 更新会员标签统计
                if ($tags != 0) {
                    DB::table('label')->where('id', $tags)->update(['sum' => DB::raw('sum+1')]);
                }

                // 创建账号信息
                \Model\Profile::create([
                    'nickname'  => '',
                    'mobile'  => $mobile,
                    'user_id' => $userId,
                    'email' => isset($param['email']) && trim($param['email']) ? \Utils\Utils::RSAEncrypt($param['email']) : '',
                    'weixin' => isset($param['weixin']) && trim($param['weixin']) ? \Utils\Utils::RSAEncrypt($param['weixin']) : '',
                    'qq' => isset($param['qq']) && trim($param['qq']) ? \Utils\Utils::RSAEncrypt($param['qq']) : '',
                    'name' => isset($param['true_name']) && trim($param['true_name']) ? $param['true_name'] : '',
                ]);

                \Model\UserData::insert([
                    'user_id' => $userId,
                    'total_bet' => 0,
                ]);

                \Model\UserDml::insert([
                    'user_id' => $userId,
                ]);

                // 写入注册日志
                \Model\UserLog::create([
                    'user_id'   => $userId,
                    'name'      => $username,
                    'platform'  => isset($_SERVER['HTTP_PL']) ? isset($this->platformIds[$_SERVER['HTTP_PL']]) ? $this->platformIds[$_SERVER['HTTP_PL']] : 1 : 1,
                    'log_value' => $this->lang->text("register success"),
                    'status'    => 1,
                    'log_type'  => 8,
                ]);

                $bindEmail = isset($param['email']) && trim($param['email']) ? 1 : 0;
                $bindMobile = trim($mobile) ? 1 : 0;
                // 创建安全中心表
                \Model\SafeCenter::create(['user_id' => $userId, 'type' => 1, 'mobile' => $bindMobile, 'email' => $bindEmail]);
                if ($bindMobile) {   //绑定手机活动
                    $activity = new \Logic\Activity\Activity($this->ci);
                    $activity->bindInfo($userId, 1);
                }
                if ($bindEmail) { //绑定邮箱活动
                    $activity = new \Logic\Activity\Activity($this->ci);
                    $activity->bindInfo($userId, 2);
                }

                // 创建人人代理数据
                unset($rakeBack['agent_switch']);
                $agentData = $agentData ? $agentData : json_encode($rakeBack);
                $agent = new \Logic\User\Agent($this->ci);
                $agent_id = $param['agent_id'] ?? 0; //后台选择添加上级代理
                $lang = $agent->addAgent(['user_id' => $userId, 'bkge' => $agentData], $invitCode, $agent_id);
                if ($lang->getState() != 135) {
                    $this->db->getConnection()
                        ->rollback();
                    return $lang;
                }

                $profit = $agent->getProfit($userId);
                if ($profit) {
                    DB::table('user_agent')->where('user_id', $userId)->update(['profit_loss_value' => $profit]);
                }

                // 提交事务
                $this->db->getConnection()->commit();

                $levelMsg = ['id' => $userId, 'name' => $username, 'ranting' => 0, 'wallet_id' => $walletId];
                $this->upgradeLevelMsg($levelMsg);

                //注册送彩金
                $this->registerSendGift($userId, $mobile);
                //注册统计
                $this->thirdSendMsg($userId);
                return $this->lang->set(0, [], ['uid' => $userId]);
            }
        } catch (\Exception $e) {
            $this->db->getConnection()
                ->rollback();
            $this->logger->error("注册失败 " . $e->getMessage());

            echo $e->getMessage();
            die;
        }

        return $this->lang->set(108);
    }

    public function newRegister($username, $password, $bankId, $bankAccount, $name, $mobile, $telCode, $invitCode = '')
    {

        $this->userId = 0;
        $this->username = $username;
        $this->password = $password;
        $validate_params = compact(['username', 'name', 'password', 'bankAccount', 'mobile', 'telCode']);
        $validate_rules  = [
            'username'     => V::name()
                ->noWhitespace()
                ->setName($this->lang->text("username validate")),
            'name'         => V::name()
                ->setName($this->lang->text("name validate")),
            'password'     => V::password()
                ->setName($this->lang->text("password")),
            'bankAccount'  => V::bankAccounts()
                ->setName($this->lang->text("Bank card number")),
            'mobile'       => V::mobile()
                ->setName($this->lang->text("telphone")),
            'telCode'      => V::captchaTextCode()
                ->setName($this->lang->text("sms code"))
        ];
        if ($invitCode) {
            $validate_params['invitCode'] = $invitCode;
            $validate_rules['invitCode']  = V::invitCode()
                ->setName($this->lang->text("invit Code"));
        }
        $validator = $this->validator->validate($validate_params, $validate_rules);

        if (!$validator->isValid()) {
            return $validator;
        }

        $mobileEn  = '';
        $checkType = 1;
        if ($checkType == 1) {
            // 验证手机号码是否被使用过
            $mobileEn = \Utils\Utils::RSAEncrypt($mobile);
            if (
                \Model\User::where('mobile', $mobileEn)
                ->count() > 0
            ) {
                return $this->lang->set(104);
            }

            // 验证手机验证码
            $captcha = new \Logic\Captcha\Captcha($this->ci);
            if($this->registerVerify()){
                if ( !$captcha->validateTextCode($mobile, $telCode)) {
                    return $this->lang->set(106, [], [], ['mobile' => $mobile]);
                }
            }

        }

        // 验证账号是否已注册
        if (\Model\User::getAccountExist($username)) {
            return $this->lang->set(4002);
        }

        //限制注册人数
        if (!$this->registerLimitCountIp()) {
            return $this->lang->set(188);
        }

        //ip限制开关
        if (!$this->registerLimitIp()) {
            return $this->lang->set(189);
        }

        if (empty(\Model\Bank::where('id', $bankId)->first())) {
            return $this->lang->set(122);
        }

        if (\Model\BankUser::where('state', '!=', 'delete')->where('card', \Utils\Utils::RSAEncrypt($bankAccount))->first()) {
            return $this->lang->set(120);
        }

        // 查找代码号ID (改人人代理？？？)
        try {
            $this->db->getConnection()
                ->beginTransaction();

            $conf = $this->ci->get('settings')['website'];

            if ($this->db->getConnection()
                ->transactionLevel()
            ) {

                // 创建钱包表
                $walletId = \Model\Funds::create(['comment' => $username])->id;
                // 创建子钱包
                $partners = GameMenu::where('pid', '!=', 0)
                    ->where('type', '!=', 'ZYCPSTA')
                    ->where('type', '!=', 'ZYCPCHAT')
                    ->where('switch', 'enabled')
                    ->groupBy('alias')
                    ->get()->toArray();
                if (!empty($partners)) {
                    foreach ($partners as $v) {
                        $v = (array)$v;
                        //$fundsChildData = ['pid' => $walletId, 'game_type' => $v['alias'], 'name' => $v['name']];
                        $fundsChildData = ['pid' => $walletId, 'game_type' => $v['alias'], 'name' => $v['alias']];
                        \Model\FundsChild::create($fundsChildData);
                    }
                }

                // 获取默认层级
                $levelId = \Model\UserLevel::orderBY('level', 'ASC')->value('level');
                $levelId = $levelId ? $levelId : 0;

                //获取来源   android  若渠道名CHANNELNAME为gf  则是标识 APPLICATIONID 是唯一，否则  渠道名就是唯一
                $origins = ['pc' => 1, 'h5' => 2, 'ios' => 3, 'android' => 4];
                $origin = isset($this->request->getHeaders()['HTTP_PL']) && is_array($this->request->getHeaders()['HTTP_PL']) ? current($this->request->getHeaders()['HTTP_PL']) : '';
                $origin_memo = isset($this->request->getHeaders()['HTTP_CHANNELNAME']) && is_array($this->request->getHeaders()['HTTP_CHANNELNAME']) ? current($this->request->getHeaders()['HTTP_CHANNELNAME']) : '';
                if ($origin_memo == 'gf') {
                    $origin_memo = isset($this->request->getHeaders()['HTTP_APPLICATIONID']) && is_array($this->request->getHeaders()['HTTP_APPLICATIONID']) ? current($this->request->getHeaders()['HTTP_APPLICATIONID']) : '';
                }
                $rakeBack = SystemConfig::getModuleSystemConfig('rakeBack');
                // 创建账号
                $param = $this->request->getParams();
                $userData = [
                    'wallet_id'     => $walletId,
                    'name'          => $username,
                    'invit_code'    => $invitCode,
                    'mobile'        => $mobileEn,
                    //'tags'          => $tags,
                    'password'      => $password,
                    'ranting'       => $levelId,
                    'source'        => 'register',
                    'origin'        => isset($origins[$origin]) ? $origins[$origin] : 0,
                    'origin_memo'   => $origin_memo,
                    'agent_switch'   => $rakeBack['agent_switch'],
                    'email' => isset($param['email']) && trim($param['email']) ? \Utils\Utils::RSAEncrypt($param['email']) : "",
                    'wechat' => isset($param['weixin']) && trim($param['weixin']) ? \Utils\Utils::RSAEncrypt($param['weixin']) : '',
                    'is_verify' => $this->registerVerify() ? 1 :0
                    //                    'is_test'=> $isTest,
                ];
                $channel_id = $param['channel_id'];
                if (!empty($channel_id)) {
                    $checkChannel = DB::table('channel_management')->where('number', $channel_id)->first();
                    if (!empty($checkChannel)) {
                        $userData['channel_id'] = $channel_id;
                    }
                }
                $userId = \Model\User::create($userData)->id;
                $this->userId = $userId;

                // 创建账号信息
                \Model\Profile::create([
                    'nickname'  => '',
                    'user_id' => $userId,
                    'mobile'  => $mobileEn,
                    'email' => isset($param['email']) && trim($param['email']) ? \Utils\Utils::RSAEncrypt($param['email']) : '',
                    'weixin' => isset($param['weixin']) && trim($param['weixin']) ? \Utils\Utils::RSAEncrypt($param['weixin']) : '',
                    'qq' => isset($param['qq']) && trim($param['qq']) ? \Utils\Utils::RSAEncrypt($param['qq']) : '',
                    'name' => isset($param['name']) && trim($param['name']) ? $param['name'] : '',
                ]);

                \Model\UserData::insert([
                    'user_id' => $userId,
                    'total_bet' => 0,
                ]);

                \Model\UserDml::insert([
                    'user_id' => $userId,
                ]);

                // 写入注册日志
                \Model\UserLog::create([
                    'user_id'   => $userId,
                    'name'      => $username,
                    'platform'  => isset($_SERVER['HTTP_PL']) ? isset($this->platformIds[$_SERVER['HTTP_PL']]) ? $this->platformIds[$_SERVER['HTTP_PL']] : 1 : 1,
                    'log_value' => $this->lang->text("register success"),
                    'status'    => 1,
                    'log_type'  => 8,
                ]);
                $role = 1;
                $state = 'enabled';
                $count = \Model\BankUser::where('user_id', $userId)
                    ->where('role', $role)
                    ->where('state', $state)
                    ->count();

                if ($count >= \Model\BankUser::MAX_CARD_NUM) {
                    return $this->lang->set(121, [\Model\BankUser::MAX_CARD_NUM]);
                }

                \Model\BankUser::create([
                    'user_id'   => $userId,
                    'bank_id'   => $bankId,
                    'name'      => $name,
                    'card'      => \Utils\Utils::RSAEncrypt($bankAccount),
                    //'address'  => $address,
                    'fee'       => 0,
                    'role'      => $role,
                ]);
                // 创建安全中心表
                \Model\SafeCenter::create(['user_id' => $userId, 'type' => 1, 'mobile' => 1, 'email' => 0, 'bank_card' => 1]);
                \Model\Profile::where('user_id', $userId)->whereRaw('name is null')->update(['name' => $name]);
                (new \Logic\Activity\Activity($this->ci))->bindInfo($this->auth->getUserId(), 3);

                // 创建人人代理数据
                unset($rakeBack['agent_switch']);
                $agentData = '';
                $agentData = $agentData ? $agentData : json_encode($rakeBack);
                $agent = new \Logic\User\Agent($this->ci);
                $lang = $agent->addAgent(['user_id' => $userId], $invitCode);
                if ($lang->getState() != 135) {
                    $this->db->getConnection()
                        ->rollback();
                    return $lang;
                }

                $profit = $agent->getProfit($userId);
                if ($profit) {
                    DB::table('user_agent')->where('user_id', $userId)->update(['profit_loss_value' => $profit]);
                }

                // 提交事务
                $this->db->getConnection()->commit();

                $levelMsg = ['id' => $userId, 'name' => $username, 'ranting' => 0, 'wallet_id' => $walletId];
                $this->upgradeLevelMsg($levelMsg);

                //注册送彩金
                $this->registerSendGift($userId, $mobile);
                //注册统计
                $this->thirdSendMsg($userId);

                //推广注册成功弹窗
                $direct_config = SystemConfig::getModuleSystemConfig('direct');
                //推广开关打开且赠送金额不为0时添加弹窗
                if ($invitCode && $direct_config['direct_switch'] && $direct_config['cash_promotion_register']['send_amount']) {
                    $sup_agent = \Model\UserAgent::where('code', $invitCode)->first();
                    if (!$sup_agent) {
                        $agent_id = DB::table('agent_code')->where('code', $invitCode)->value('agent_id');
                        $sup_agent = \Model\UserAgent::where('user_id', $agent_id)->first();
                    }
                    $this->redis->set('inviteRigisterWindow_' . $sup_agent->user_id, 1);
                    $this->redis->set('inviteRigisterFinishWindow_' . $userId, 1);
                }

                return $this->lang->set(0, [], ['uid' => $userId]);
            }
        } catch (\Exception $e) {
            $this->db->getConnection()
                ->rollback();
            $this->logger->error("注册失败 " . $e->getMessage());

            echo $e->getMessage();
            die;
        }

        return $this->lang->set(108);
    }



    public function okeRegister($username, $password, $mobile, $email, $verifyCode, $invitCode = '')
    {

        $redis_key = md5($username . $password . $mobile . $email . $verifyCode . $invitCode);
        //禁止频繁操作 限定最快3秒一次
        $lock = $this->redis->setnx($redis_key, 1);
        $this->redis->expire($redis_key, 3);

        if (!$lock) {
            return $this->lang->set(886, ['frequent requests, please try again later']);
        }

        $this->userId = 0;
        $this->username = $username;
        $this->password = $password;
        $validate_params = compact(['username', 'password']);
        $validate_rules  = [
            'username'     => V::username()
                ->noWhitespace()
                ->setName($this->lang->text("username validate")),
            'password'     => V::password()
                ->setName($this->lang->text("password")),
        ];
        if ($invitCode) {
            $validate_params['invitCode'] = $invitCode;
            $validate_rules['invitCode']  = V::invitCode()
                ->setName($this->lang->text("invit Code"));
        }
        $validator = $this->validator->validate($validate_params, $validate_rules);

        if (!$validator->isValid()) {
            return $validator;
        }

        $mobileEn  = '';
        $emailEn = '';
        if ($mobile) {
            // 验证手机号码是否被使用过
            $mobileEn = \Utils\Utils::RSAEncrypt($mobile);
            if (
                \Model\User::where('mobile', $mobileEn)
                ->count() > 0
            ) {
                return $this->lang->set(104);
            }
            // 验证手机验证码
            $captcha = new \Logic\Captcha\Captcha($this->ci);
            if($this->registerVerify()){
                if (!$captcha->validateTextCode($mobile, $verifyCode)) {
                    return $this->lang->set(106, [], [], ['mobile' => $mobile]);
                }
            }

        } elseif ($email) {
            // 验证邮箱码是否被使用过
            $emailEn = \Utils\Utils::RSAEncrypt($email);
            if (
                \Model\User::where('email', $emailEn)
                ->count() > 0
            ) {
                return $this->lang->set(186);
            }
            // 验证手机验证码
            $captcha = new \Logic\Captcha\Captcha($this->ci);
            if (!$captcha->validateRegisterTextCodeByEmail($emailEn, $verifyCode)) {
                return $this->lang->set(187, [], [], ['email' => $email]);
            }
        }

        // 验证账号是否已注册
        if (\Model\User::getAccountExist($username)) {
            return $this->lang->set(4002);
        }

        //限制注册人数
        if (!$this->registerLimitCountIp()) {
            return $this->lang->set(188);
        }

        //ip限制开关
        if (!$this->registerLimitIp()) {
            return $this->lang->set(189);
        }

        // 查找代码号ID (改人人代理？？？)
        try {
            $this->db->getConnection()
                ->beginTransaction();

            $conf = $this->ci->get('settings')['website'];

            if ($this->db->getConnection()
                ->transactionLevel()
            ) {

                // 创建钱包表
                $walletId = \Model\Funds::create(['comment' => $username])->id;
                // 创建子钱包
                $partners = GameMenu::where('pid', '!=', 0)
                    ->where('type', '!=', 'ZYCPSTA')
                    ->where('type', '!=', 'ZYCPCHAT')
                    ->where('switch', 'enabled')
                    ->groupBy('alias')
                    ->get()->toArray();
                if (!empty($partners)) {
                    foreach ($partners as $v) {
                        $v = (array)$v;
                        //$fundsChildData = ['pid' => $walletId, 'game_type' => $v['alias'], 'name' => $v['name']];
                        $fundsChildData = ['pid' => $walletId, 'game_type' => $v['alias'], 'name' => $v['alias']];
                        \Model\FundsChild::create($fundsChildData);
                    }
                }

                // 获取默认层级
                $levelId = \Model\UserLevel::orderBY('level', 'ASC')->value('level');
                $levelId = $levelId ? $levelId : 0;

                //获取来源   android  若渠道名CHANNELNAME为gf  则是标识 APPLICATIONID 是唯一，否则  渠道名就是唯一
                $origins = ['pc' => 1, 'h5' => 2, 'ios' => 3, 'android' => 4];
                $origin = isset($this->request->getHeaders()['HTTP_PL']) && is_array($this->request->getHeaders()['HTTP_PL']) ? current($this->request->getHeaders()['HTTP_PL']) : '';
                $origin_memo = isset($this->request->getHeaders()['HTTP_CHANNELNAME']) && is_array($this->request->getHeaders()['HTTP_CHANNELNAME']) ? current($this->request->getHeaders()['HTTP_CHANNELNAME']) : '';
                if ($origin_memo == 'gf') {
                    $origin_memo = isset($this->request->getHeaders()['HTTP_APPLICATIONID']) && is_array($this->request->getHeaders()['HTTP_APPLICATIONID']) ? current($this->request->getHeaders()['HTTP_APPLICATIONID']) : '';
                }
                $rakeBack = SystemConfig::getModuleSystemConfig('rakeBack');
                // 创建账号
                $param = $this->request->getParams();
                $userData = [
                    'wallet_id'     => $walletId,
                    'name'          => $username,
                    'invit_code'    => $invitCode,
                    'mobile'        => $mobileEn,
                    //'tags'          => $tags,
                    'password'      => $password,
                    'ranting'       => $levelId,
                    'source'        => 'register',
                    'origin'        => isset($origins[$origin]) ? $origins[$origin] : 0,
                    'origin_memo'   => $origin_memo,
                    'agent_switch'   => $rakeBack['agent_switch'],
                    'wechat' => '',
                    'is_verify' => $this->registerVerify() ? 1 :0
                    //                    'is_test'=> $isTest,
                ];
                $channel_id = $param['channel_id'] ?? '';
                if (!empty($channel_id)) {
                    $checkChannel = DB::table('channel_management')->where('number', $channel_id)->first();
                    if (!empty($checkChannel)) {
                        $userData['channel_id'] = $channel_id;
                    }
                }
                $userId = \Model\User::create($userData)->id;
                $this->userId = $userId;

                // 创建账号信息
                \Model\Profile::create([
                    'nickname'  => '',
                    'user_id' => $userId,
                    'mobile'  => $mobileEn,
                    'email' => $emailEn,
                    'weixin' => isset($param['weixin']) && trim($param['weixin']) ? \Utils\Utils::RSAEncrypt($param['weixin']) : '',
                    'qq' => isset($param['qq']) && trim($param['qq']) ? \Utils\Utils::RSAEncrypt($param['qq']) : '',
                    'name' => isset($param['name']) && trim($param['name']) ? $param['name'] : '',
                ]);

                \Model\UserData::insert([
                    'user_id' => $userId,
                    'total_bet' => 0,
                ]);

                \Model\UserDml::insert([
                    'user_id' => $userId,
                ]);

                // 写入注册日志
                \Model\UserLog::create([
                    'user_id'   => $userId,
                    'name'      => $username,
                    'platform'  => isset($_SERVER['HTTP_PL']) ? isset($this->platformIds[$_SERVER['HTTP_PL']]) ? $this->platformIds[$_SERVER['HTTP_PL']] : 1 : 1,
                    'log_value' => $this->lang->text("register success"),
                    'status'    => 1,
                    'log_type'  => 8,
                    'version'   => \Utils\Client::getHeader('HTTP_VERSION') ? current(\Utils\Client::getHeader('HTTP_VERSION')) : NULL,
                ]);
                $role = 1;
                $state = 'enabled';
                $count = \Model\BankUser::where('user_id', $userId)
                    ->where('role', $role)
                    ->where('state', $state)
                    ->count();

                if ($count >= \Model\BankUser::MAX_CARD_NUM) {
                    return $this->lang->set(121, [\Model\BankUser::MAX_CARD_NUM]);
                }
                // 创建安全中心表
                \Model\SafeCenter::create(['user_id' => $userId, 'type' => 1, 'mobile' => $mobileEn ? 1 : 0, 'email' => $emailEn ? 1 : 0, 'bank_card' => 0]);
                (new \Logic\Activity\Activity($this->ci))->bindInfo($userId, 1);

                // 创建人人代理数据
                unset($rakeBack['agent_switch']);
                $agentData = '';
                $agentData = $agentData ? $agentData : json_encode($rakeBack);
                $agent = new \Logic\User\Agent($this->ci);
                $lang = $agent->addAgent(['user_id' => $userId], $invitCode);
                if ($lang->getState() != 135) {
                    $this->db->getConnection()->rollback();
                    return $lang;
                }
                $profit = $agent->getProfit($userId);
                if ($profit) {
                    DB::table('user_agent')->where('user_id', $userId)->update(['profit_loss_value' => $profit]);
                }

                //如果是邀请码注册，绑定完代理关系后，分别给推广者和被推广者发放直推奖励
                if (!empty($invitCode)) {
                    $obj = new \Logic\Recharge\Recharge($this->ci);
                    $ck_data = $obj->directRegAward($invitCode, $userId, $username, $walletId);
                    if ($ck_data['code'] != 0) {
                        $this->logger->error("直推注册发放奖励失败：user_id=" . $userId . '===' . $ck_data['msg']);
                    }
                }

                // 提交事务
                $this->db->getConnection()->commit();

                $levelMsg = ['id' => $userId, 'name' => $username, 'ranting' => 0, 'wallet_id' => $walletId];
                $this->upgradeLevelMsg($levelMsg);

                //推广注册成功弹窗
                $direct_config = SystemConfig::getModuleSystemConfig('direct');
                //推广开关打开且赠送金额不为0时添加弹窗
                if ($invitCode && $direct_config['direct_switch'] && $direct_config['cash_promotion_register']['send_amount']) {
                    $sup_agent = \Model\UserAgent::where('code', $invitCode)->first();
                    if (!$sup_agent) {
                        $agent_id = DB::table('agent_code')->where('code', $invitCode)->value('agent_id');
                        $sup_agent = \Model\UserAgent::where('user_id', $agent_id)->first();
                    }
                    $this->redis->set('inviteRigisterWindow_' . $sup_agent->user_id, 1);
                    $this->redis->set('inviteRigisterFinishWindow_' . $userId, 1);
                }

                //注册送彩金
                $this->registerSendGift($userId, $mobile);
                //注册统计
                $this->thirdSendMsg($userId);

                $this->redis->del($redis_key);
                return $this->lang->set(0, [], ['uid' => $userId]);
            }
        } catch (\Exception $e) {
            $this->redis->del($redis_key);
            $this->db->getConnection()
                ->rollback();
            $this->logger->error("注册失败 " . $e->getMessage());

            echo $e->getMessage();
            die;
        }

        return $this->lang->set(108);
    }

    public function mxnRegister($username, $password, $invitCode = '', $mobile = '', $verifyCode = '')
    {

        $redis_key = md5($username . $password . $invitCode . $mobile . $verifyCode);
        //禁止频繁操作 限定最快3秒一次
        $lock = $this->redis->setnx($redis_key, 1);
        $this->redis->expire($redis_key, 3);

        if (!$lock) {
            return $this->lang->set(886, ['frequent requests, please try again later']);
        }

        $this->userId = 0;
        $this->username = $username;
        $this->password = $password;
        $validate_params = compact(['username', 'password']);
        $validate_rules  = [
            'username'     => V::mxnUsername()
                ->noWhitespace()
                ->setName($this->lang->text("username validate")),
            'password'     => V::mxnPassword()
                ->setName($this->lang->text("password"))
        ];
        if ($invitCode) {
            $validate_params['invitCode'] = $invitCode;
            $validate_rules['invitCode']  = V::invitCode()
                ->setName($this->lang->text("invit Code"));
        }
        $validator = $this->validator->validate($validate_params, $validate_rules);

        if (!$validator->isValid()) {
            return $validator;
        }

        $mobileEn  = '';
        if ($mobile) {
            // 验证手机号码是否被使用过
            $mobileEn = \Utils\Utils::RSAEncrypt($mobile);
            if (
                \Model\User::where('mobile', $mobileEn)
                ->count() > 0
            ) {
                return $this->lang->set(104);
            }
        }

        // 验证账号是否已注册
        if (\Model\User::getAccountExist($username)) {
            return $this->lang->set(4002);
        }

        //限制注册人数
        if (!$this->registerLimitCountIp()) {
            return $this->lang->set(188);
        }

        //ip限制开关
        if (!$this->registerLimitIp()) {
            return $this->lang->set(189);
        }

        // 查找代码号ID (改人人代理？？？)
        try {
            $this->db->getConnection()
                ->beginTransaction();

            $conf = $this->ci->get('settings')['website'];

            if ($this->db->getConnection()
                ->transactionLevel()
            ) {

                // 创建钱包表
                $walletId = \Model\Funds::create(['comment' => $username])->id;
                // 创建子钱包
                $partners = GameMenu::where('pid', '!=', 0)
                    ->where('type', '!=', 'ZYCPSTA')
                    ->where('type', '!=', 'ZYCPCHAT')
                    ->where('switch', 'enabled')
                    ->groupBy('alias')
                    ->get()->toArray();
                if (!empty($partners)) {
                    foreach ($partners as $v) {
                        $v = (array)$v;
                        //$fundsChildData = ['pid' => $walletId, 'game_type' => $v['alias'], 'name' => $v['name']];
                        $fundsChildData = ['pid' => $walletId, 'game_type' => $v['alias'], 'name' => $v['alias']];
                        \Model\FundsChild::create($fundsChildData);
                    }
                }

                // 获取默认层级
                $levelId = \Model\UserLevel::orderBY('level', 'ASC')->value('level');
                $levelId = $levelId ? $levelId : 0;

                //获取来源   android  若渠道名CHANNELNAME为gf  则是标识 APPLICATIONID 是唯一，否则  渠道名就是唯一
                $origins = ['pc' => 1, 'h5' => 2, 'ios' => 3, 'android' => 4];
                $origin = isset($this->request->getHeaders()['HTTP_PL']) && is_array($this->request->getHeaders()['HTTP_PL']) ? current($this->request->getHeaders()['HTTP_PL']) : '';
                $origin_memo = isset($this->request->getHeaders()['HTTP_CHANNELNAME']) && is_array($this->request->getHeaders()['HTTP_CHANNELNAME']) ? current($this->request->getHeaders()['HTTP_CHANNELNAME']) : '';
                if ($origin_memo == 'gf') {
                    $origin_memo = isset($this->request->getHeaders()['HTTP_APPLICATIONID']) && is_array($this->request->getHeaders()['HTTP_APPLICATIONID']) ? current($this->request->getHeaders()['HTTP_APPLICATIONID']) : '';
                }
                $rakeBack = SystemConfig::getModuleSystemConfig('rakeBack');
                // 创建账号
                $param = $this->request->getParams();
                $userData = [
                    'wallet_id'     => $walletId,
                    'name'          => $username,
                    'invit_code'    => $invitCode,
                    'mobile'        => $mobileEn,
                    //'tags'          => $tags,
                    'password'      => $password,
                    'ranting'       => $levelId,
                    'source'        => 'register',
                    'origin'        => isset($origins[$origin]) ? $origins[$origin] : 0,
                    'origin_memo'   => $origin_memo,
                    'agent_switch'   => $rakeBack['agent_switch'],
                    'wechat' => '',
                    //                    'is_test'=> $isTest,
                ];
                $channel_id = $param['channel_id'] ?? '';
                if (!empty($channel_id)) {
                    $checkChannel = DB::table('channel_management')->where('number', $channel_id)->first();
                    if (!empty($checkChannel)) {
                        $userData['channel_id'] = $channel_id;
                    }
                }
                $userId = \Model\User::create($userData)->id;
                $this->userId = $userId;

                // 创建账号信息
                \Model\Profile::create([
                    'nickname'  => '',
                    'user_id' => $userId,
                    'weixin' => isset($param['weixin']) && trim($param['weixin']) ? \Utils\Utils::RSAEncrypt($param['weixin']) : '',
                    'qq' => isset($param['qq']) && trim($param['qq']) ? \Utils\Utils::RSAEncrypt($param['qq']) : '',
                    'name' => isset($param['name']) && trim($param['name']) ? $param['name'] : '',
                    'mobile'        => $mobileEn,
                ]);

                \Model\UserData::insert([
                    'user_id' => $userId,
                    'total_bet' => 0,
                ]);

                \Model\UserDml::insert([
                    'user_id' => $userId,
                ]);

                // 写入注册日志
                \Model\UserLog::create([
                    'user_id'   => $userId,
                    'name'      => $username,
                    'platform'  => isset($_SERVER['HTTP_PL']) ? isset($this->platformIds[$_SERVER['HTTP_PL']]) ? $this->platformIds[$_SERVER['HTTP_PL']] : 1 : 1,
                    'log_value' => $this->lang->text("register success"),
                    'status'    => 1,
                    'log_type'  => 8,
                ]);
                $role = 1;
                $state = 'enabled';
                $count = \Model\BankUser::where('user_id', $userId)
                    ->where('role', $role)
                    ->where('state', $state)
                    ->count();

                if ($count >= \Model\BankUser::MAX_CARD_NUM) {
                    return $this->lang->set(121, [\Model\BankUser::MAX_CARD_NUM]);
                }
                // 创建安全中心表
                \Model\SafeCenter::create(['user_id' => $userId, 'type' => 1, 'mobile' => $mobileEn ? 1 : 0, 'email' => 0, 'bank_card' => 0]);
                (new \Logic\Activity\Activity($this->ci))->bindInfo($userId, 3);

                // 创建人人代理数据
                unset($rakeBack['agent_switch']);
                $agentData = '';
                $agentData = $agentData ? $agentData : json_encode($rakeBack);
                $agent = new \Logic\User\Agent($this->ci);
                $lang = $agent->addAgent(['user_id' => $userId], $invitCode);
                if ($lang->getState() != 135) {
                    $this->db->getConnection()
                        ->rollback();
                    return $lang;
                }
                $profit = $agent->getProfit($userId);
                if ($profit) {
                    DB::table('user_agent')->where('user_id', $userId)->update(['profit_loss_value' => $profit]);
                }

                //如果是邀请码注册，绑定完代理关系后，分别给推广者和被推广者发放直推奖励
                if (!empty($invitCode)) {
                    $obj = new \Logic\Recharge\Recharge($this->ci);
                    $ck_data = $obj->directRegAward($invitCode, $userId, $username, $walletId);
                    if ($ck_data['code'] != 0) {
                        $this->logger->error("直推注册发放奖励失败：user_id=" . $userId . '===' . $ck_data['msg']);
                    }
                }

                // 提交事务
                $this->db->getConnection()->commit();

                $levelMsg = ['id' => $userId, 'name' => $username, 'ranting' => 0, 'wallet_id' => $walletId];
                $this->upgradeLevelMsg($levelMsg);

                //注册送彩金
                //$this->registerSendGift($userId, $mobile);

                //注册统计
                $this->thirdSendMsg($userId);

                $this->redis->del($redis_key);
                return $this->lang->set(0, [], ['uid' => $userId]);
            }
        } catch (\Exception $e) {
            $this->redis->del($redis_key);
            $this->db->getConnection()
                ->rollback();
            $this->logger->error("注册失败 " . $e->getMessage());

            echo $e->getMessage();
            die;
        }

        return $this->lang->set(108);
    }

    /**
     * 随机字符串
     * @param int $length
     * @return string
     */
    function generateChars($length = 2)
    {
        // 密码字符集，可任意添加你需要的字符
        $chars_list = 'abcdefghijklmnopqrstuvwxyz';
        $chars = '';
        for ($i = 0; $i < $length; $i++) {
            $chars .= $chars_list[mt_rand(0, strlen($chars_list) - 1)];
        }
        return $chars;
    }

    /**
     * 随机数字
     * @param int $length
     * @return string
     */
    function generateNumber($length = 4)
    {
        // 密码字符集，可任意添加你需要的字符
        $chars_list = '0123456789';
        $chars = '';
        for ($i = 0; $i < $length; $i++) {
            $chars .= $chars_list[mt_rand(0, strlen($chars_list) - 1)];
        }
        return $chars;
    }

    /**
     * 竖版注册  创建密码
     * @return string
     */
    function createPassword()
    {
        //2个字母 4个数字
        return $this->generateChars(2) . $this->generateNumber(4);
    }

    /**
     * 竖版注册  创建账号
     * @return string
     */
    function createUsername()
    {
        //2个字母 12个数字
        return $this->generateChars(2) . $this->generateNumber(12);
    }

    public function verticalRegister($mobile, $withdrawPwd = '', $bankId, $bankAccount, $name, $surname, $gender, $telCode, $invitCode = '', $line = '', $username = '', $password = '')
    {

        $key = "$mobile.$bankId.$bankAccount.$name.$surname.$gender.$telCode.$invitCode.$line.$username.$password";
        $redis_key = md5($key);
        //禁止频繁操作 限定最快3秒一次
        $lock = $this->redis->setnx($redis_key, 1);
        $this->redis->expire($redis_key, 3);

        if (!$lock) {
            return $this->lang->set(886, ['frequent requests, please try again later']);
        }
        $this->userId = 0;
        $validate_params = compact(['name', 'surname', 'bankAccount', 'mobile', 'telCode']);
        $validate_rules  = [
            'name'         => V::name()
                ->setName($this->lang->text("name validate")),
            'surname'     => V::name()
                ->setName($this->lang->text("surname validate")),
            'bankAccount'  => V::bankAccounts()
                ->setName($this->lang->text("Bank card number")),
            'mobile'       => V::mobile()
                ->setName($this->lang->text("telphone"))
        ];
        if ($invitCode) {
            $validate_params['invitCode'] = $invitCode;
            $validate_rules['invitCode']  = V::invitCode()
                ->setName($this->lang->text("invit Code"));
        }
        if ($username) {
            $validate_params['username'] = $username;
            $validate_rules['username']  = V::username()
                ->noWhitespace()
                ->setName($this->lang->text("username validate"));
        }
        if ($withdrawPwd) {
            $validate_params['withdraw_pwd'] = $withdrawPwd;
            $validate_rules['withdraw_pwd']  = V::withdrawPwd()
                ->noWhitespace()
                ->setName($this->lang->text("pin validate"));
        }
        if ($password) {
            $validate_params['password'] = $password;
            $validate_rules['password']  = V::password()
                ->noWhitespace()
                ->setName($this->lang->text("password"));
        }
        $validator = $this->validator->validate($validate_params, $validate_rules);

        if (!$validator->isValid()) {
            return $validator;
        }

        // 验证账号是否已注册
        if ($username) {
            if (\Model\User::getAccountExist($username)) {
                return $this->lang->set(4002);
            }
        } else {
            $username = Utils::creatUsername();
        }

        $mobileEn  = '';
        $checkType = 1;
        $truename  = $name . ' ' . $surname;
        if ($checkType == 1) {
            // 验证手机号码是否被使用过
            $mobileEn = \Utils\Utils::RSAEncrypt($mobile);
            if (
                \Model\User::where('mobile', $mobileEn)
                ->count() > 0
            ) {
                return $this->lang->set(104);
            }

            // 验证手机验证码
            $captcha = new \Logic\Captcha\Captcha($this->ci);
            if($this->registerVerify()){
                if ( !$captcha->validateTextCode($mobile, $telCode)) {
                    return $this->lang->set(106, [], [], ['mobile' => $mobile]);
                }
            }

        }

        //限制注册人数
        if (!$this->registerLimitCountIp()) {
            return $this->lang->set(188);
        }

        //ip限制开关
        if (!$this->registerLimitIp()) {
            return $this->lang->set(189);
        }

        if (empty(\Model\Bank::where('id', $bankId)->first())) {
            return $this->lang->set(122);
        }

        if (\Model\BankUser::where('state', '!=', 'delete')->where('card', \Utils\Utils::RSAEncrypt($bankAccount))->first()) {
            return $this->lang->set(120);
        }

        // 查找代码号ID (改人人代理？？？)
        try {
            $this->db->getConnection()
                ->beginTransaction();

            if ($this->db->getConnection()
                ->transactionLevel()
            ) {

                // 创建钱包表 设置取款密码
                if ($withdrawPwd) {
                    $withdraw_data = ['comment' => $username, 'password' => $withdrawPwd];
                } else {
                    $withdraw_data = ['comment' => $username];
                }
                $walletId = \Model\Funds::create($withdraw_data)->id;

                // 创建子钱包
                $partners = GameMenu::where('pid', '!=', 0)
                    ->where('type', '!=', 'ZYCPSTA')
                    ->where('type', '!=', 'ZYCPCHAT')
                    ->where('switch', 'enabled')
                    ->groupBy('alias')
                    ->get()->toArray();
                if (!empty($partners)) {
                    foreach ($partners as $v) {
                        $v = (array)$v;
                        //$fundsChildData = ['pid' => $walletId, 'game_type' => $v['alias'], 'name' => $v['name']];
                        $fundsChildData = ['pid' => $walletId, 'game_type' => $v['alias'], 'name' => $v['alias']];
                        \Model\FundsChild::create($fundsChildData);
                    }
                }

                $password = $password ?: $this->createPassword();

                // 获取默认层级
                $levelId = \Model\UserLevel::orderBY('level', 'ASC')->value('level');
                $levelId = $levelId ? $levelId : 0;

                //获取来源   android  若渠道名CHANNELNAME为gf  则是标识 APPLICATIONID 是唯一，否则  渠道名就是唯一
                $origins = ['pc' => 1, 'h5' => 2, 'ios' => 3, 'android' => 4];
                $origin = isset($this->request->getHeaders()['HTTP_PL']) && is_array($this->request->getHeaders()['HTTP_PL']) ? current($this->request->getHeaders()['HTTP_PL']) : '';
                $origin_memo = isset($this->request->getHeaders()['HTTP_CHANNELNAME']) && is_array($this->request->getHeaders()['HTTP_CHANNELNAME']) ? current($this->request->getHeaders()['HTTP_CHANNELNAME']) : '';
                if ($origin_memo == 'gf') {
                    $origin_memo = isset($this->request->getHeaders()['HTTP_APPLICATIONID']) && is_array($this->request->getHeaders()['HTTP_APPLICATIONID']) ? current($this->request->getHeaders()['HTTP_APPLICATIONID']) : '';
                }
                $rakeBack = SystemConfig::getModuleSystemConfig('rakeBack');
                // 创建账号
                //$password = $this->createPassword();
                $param = $this->request->getParams();
                $userData = [
                    'wallet_id'     => $walletId,
                    'name'          => $username,
                    'invit_code'    => $invitCode,
                    'mobile'        => $mobileEn,
                    //'tags'          => $tags,
                    'password'      => $password,
                    'ranting'       => $levelId,
                    'source'        => 'register',
                    'origin'        => isset($origins[$origin]) ? $origins[$origin] : 0,
                    'origin_memo'   => $origin_memo,
                    'agent_switch'  => $rakeBack['agent_switch'],
                    'isVertical' => 1,
                    'is_verify' => $this->registerVerify() ? 1 :0
                ];
                $channel_id = $param['channel_id'];
                if (!empty($channel_id)) {
                    $checkChannel = DB::table('channel_management')->where('number', $channel_id)->first();
                    if (!empty($checkChannel)) {
                        $userData['channel_id'] = $channel_id;
                    }
                }
                $userId = \Model\User::create($userData)->id;
                $this->userId = $userId;

                // 创建账号信息
                \Model\Profile::create([
                    'nickname'  => '',
                    'user_id'   => $userId,
                    'mobile'    => $mobileEn,
                    'gender'    => (int)$gender,
                    'name'      => $truename,
                ]);

                \Model\UserData::insert([
                    'user_id'   => $userId,
                    'total_bet' => 0,
                ]);

                \Model\UserDml::insert([
                    'user_id' => $userId,
                ]);

                // 写入注册日志
                \Model\UserLog::create([
                    'user_id'   => $userId,
                    'name'      => $username,
                    'platform'  => isset($_SERVER['HTTP_PL']) ? isset($this->platformIds[$_SERVER['HTTP_PL']]) ? $this->platformIds[$_SERVER['HTTP_PL']] : 1 : 1,
                    'log_value' => $this->lang->text("register success"),
                    'status'    => 1,
                    'log_type'  => 8,
                ]);
                $role  = 1;
                $state = 'enabled';
                /*$count = \Model\BankUser::where('user_id', $userId)
                    ->where('role', $role)
                    ->where('state', $state)
                    ->count();

                if ($count >= \Model\BankUser::MAX_CARD_NUM) {
                    return $this->lang->set(121, [\Model\BankUser::MAX_CARD_NUM]);
                }*/

                \Model\BankUser::create([
                    'user_id'   => $userId,
                    'bank_id'   => $bankId,
                    'name'      => $truename,
                    'card'      => \Utils\Utils::RSAEncrypt($bankAccount),
                    //'address'  => $address,
                    'fee'       => 0,
                    'role'      => $role,
                ]);
                // 创建安全中心表
                \Model\SafeCenter::create(['user_id' => $userId, 'type' => 1, 'mobile' => 1, 'email' => 0, 'bank_card' => 1, 'withdraw_password' => 1]);
                (new \Logic\Activity\Activity($this->ci))->bindInfo($userId, 3);

                // 创建人人代理数据
                $agent = new \Logic\User\Agent($this->ci);
                $lang  = $agent->addAgent(['user_id' => $userId], $invitCode);
                if ($lang->getState() != 135) {
                    $this->db->getConnection()
                        ->rollback();
                    return $lang;
                }
                $profit = $agent->getProfit($userId);
                if ($profit) {
                    DB::table('user_agent')->where('user_id', $userId)->update(['profit_loss_value' => $profit]);
                }

                //如果是邀请码注册，绑定完代理关系后，分别给推广者和被推广者发放直推奖励
                if (!empty($invitCode)) {
                    $obj = new \Logic\Recharge\Recharge($this->ci);
                    $ck_data = $obj->directRegAward($invitCode, $userId, $username, $walletId);
                    if ($ck_data['code'] != 0) {
                        $this->logger->error("直推注册发放奖励失败：user_id=" . $userId . '===' . $ck_data['msg']);
                    }
                }

                // 提交事务
                $this->db->getConnection()->commit();

                $levelMsg = ['id' => $userId, 'name' => $username, 'ranting' => 0, 'wallet_id' => $walletId];
                $this->upgradeLevelMsg($levelMsg);

                //注册送彩金
                $this->registerSendGift($userId, $mobile);
                //注册统计
                $this->thirdSendMsg($userId);

                $this->redis->del($redis_key);
                return $this->lang->set(0, [], ['uid' => $userId, 'username' => $username, 'password' => $password, 'withdraw_pwd' => $withdrawPwd]);
            }
        } catch (\Exception $e) {
            $this->redis->del($redis_key);
            $this->db->getConnection()
                ->rollback();
            $this->logger->error("注册失败 " . $e->getMessage());

            echo $e->getMessage();
            die;
        }

        return $this->lang->set(108);
    }

    /**
     * 查询用户信息
     *
     * @param  [type] $userId [description]
     *
     * @return [type]         [description]
     */
    public function getInfo($userId)
    {
        $info = \Model\Profile::where('user_id', $userId)
            ->select([
                'name as true_name',
                'nickname',
                'avatar',
                'region_id',
                'address',
                'gender',
                'weixin',
                'qq',
                'skype',
                'email',
                'mobile',
                'birth',
                'idcard',
                'updated',
                'avatar',
            ])
            ->first()
            ->toArray();
        // $info['idcard'] = \Model\Profile::getIdCard($info['idcard']);
        $user = \Model\User::where('id', $userId)
            ->select([
                'name as user_name',
                'wallet_id',
                'ranting',
                // 'first_account',
                'tags',
                'last_login',
                'agent_switch',
            ])
            ->first()
            ->toArray();

        if (!empty($info)) {
            $info['email'] = \Model\Profile::getEmail($info['email']);
            $info['idcard'] = \Model\Profile::getIdCard($info['idcard']);
            $info['mobile'] = \Model\Profile::getMobile($info['mobile']);
            $info['qq'] = \Model\Profile::getQQ($info['qq']);
            $info['weixin'] = \Model\Profile::getQQ($info['weixin']);
            $info['skype'] = \Model\Profile::getSkype($info['skype']);
            $info['wallet'] = (int)Funds::where('id', $user['wallet_id'])->value('balance');
            $info['share_wallet'] = (int)Funds::where('id', $user['wallet_id'])->value('share_balance');
            //层级信息
            $user_level = \Model\UserLevel::where('level', $user['ranting'])
                ->select([
                    'name',
                    'icon',
                ])
                ->first();
            $info['level_name'] = isset($user_level->name) ? $user_level->name : 'LV0';
            $info['level_icon'] = isset($user_level->icon) ? showImageUrl($user_level->icon) : '';
            empty($info['avatar']) && $info['avatar'] = 1;
            $info['avatar'] = showImageUrl("/avatar/{$info['avatar']}.png");
        }
        return $info + $user;
    }

    /**
     * 查询用户信息
     *
     * @param  [type] $userId [description]
     *
     * @return [type]         [description]
     */
    public function getUserInfo($userId)
    {
        $user = \Model\User::where('id', $userId)
            ->select([
                'name as user_name',
                'wallet_id',
                'ranting',
                // 'first_account',
                'tags',
                'last_login',
                'agent_switch',
            ])
            ->first()
            ->toArray();

        return $user;
    }

    /**
     * 查询试玩用户信息
     *
     * @param  [type] $userId [description]
     *
     * @return [type]         [description]
     */
    public function getTrialInfo($userId)
    {

        $user = \Model\TrialUser::where('id', $userId)
            ->select([
                'name as user_name',
                'name',
                'wallet_id',
                'ranting',
                // 'first_account',
                'tags',
                'last_login',
                'avatar',
            ])
            ->first()
            ->toArray();
        empty($user['avatar']) && $user['avatar'] = 1;
        return $user;
    }

    /**
     * 验证密保
     *
     * @param int $user_id
     *            用户id
     * @param int $id
     *            问题id
     * @param string $value
     *            问题答案
     * @param string $name
     *            用户账号(忘记登录密码时，需要传)
     */
    public function verification($userId, $id, $value, $name = '')
    {
        if (empty($userId) || empty($id) || empty($value)) {
            return $this->lang->set(13);
        }

        $userInfo = \Model\Profile::where('user_id', $userId)
            ->first();
        $captcha = new \Logic\Captcha\Captcha($this->ci);
        switch ($id) {
            case 2:
                $user = \Model\User::where('id', $userId)
                    ->first();
                $telCode = $user['telphone_code'];
                $mobile = \Utils\Utils::RSADecrypt($userInfo['mobile']);
                if ($captcha->validateTextCode($telCode . $mobile, $value)) {
                    $this->redis->setex(\Logic\Define\CacheKey::$perfix['userSafety'] . '_' . $id . '_' . $userId, 300, 1);
                    return $this->lang->set(0);
                } else {
                    return $this->lang->set(123, [], [], ['error' => $telCode . $mobile]);
                }
                break;
            case 3:
                if ($captcha->validateTextCodeByEmail($userId, $value)) {
                    $this->redis->setex(\Logic\Define\CacheKey::$perfix['userSafety'] . '_' . $id . '_' . $userId, 300, 1);
                    return $this->lang->set(0);
                } else {
                    return $this->lang->set(123);
                }
                break;
            default:
                return $this->lang->set(13);
        }
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    /*
    *  等级弹窗数据
    * @param $user_id  int 用户ID
    * @param $upgrade  bool  当前层级是否刚升级上去
    * @return  $data array()  [popup=>'false'] 当前层级拥有的权限说明  popup为false表示无需弹窗，为true表示升级弹窗，才会有相应其它的数据
    */
    public function upgradeLevelWindows($user_id)
    {
        $data = [
            'popup' => false
        ];
        $pub = $this->redis->get('userlevelUpdateWindows' . $user_id);
        if (isset($pub) && $pub) {
            $ranting = \DB::table('user')->where('id', $user_id)->value('ranting');
            $level = \DB::table('user_level')->where('level', $ranting)->first();
            if ($level) {
                $data['popup'] = true;
                $data['message_id'] = $pub ?? 1;
                $data['level_name'] = $level->name;
                $data['level_icon'] = $level->icon;
                $data['privilege'] = $this->levelPrivilege((array) $level, true);
            }
            $this->redis->del('userlevelUpdateWindows' . $user_id);
        }
        return $data;
    }
    /*
     *  获取用户层级权限
     * @param $current_level  array 当前用户拥有层级
     * @param $upgrade  bool  当前层级是否刚升级上去
     * @return  $data array()  当前层级拥有的权限说明
     */
    public function LevelPrivilege(array $current_level, $upgrade = false)
    {
        $data = [];
        if ($upgrade) {
            $msg[1] = $this->lang->text("Get the promotion lottery") . ($current_level['promote_handsel'] / 100);
            $msg[2] = $this->lang->text("%s bonus for each offline recharge", [($current_level['transfer_handsel'] / 100)]);
            $msg[3] = $this->lang->text("Backwater condition upgrade");
            $msg[4] = $this->lang->text("The number of draw events increased to %s times", [$current_level['draw_count']]);
        } else {
            $msg[1] = $this->lang->text("Level reward") . ($current_level['promote_handsel'] / 100);
            $msg[2] = $this->lang->text("Level manual2") . ($current_level['transfer_handsel'] / 100) . '%';
            $msg[3] = $this->lang->text("backwater");
            $msg[4] = $this->lang->text("The number of lucky draw is %s", [$current_level['draw_count']]);
        }
        if ($current_level['promote_handsel'] > 0) {
            $data[] = ['name' => $msg[1], 'type' => 1];
        }
        if ($current_level['transfer_handsel'] > 0) {
            $data[] = ['name' => $msg[2], 'type' => 2];
        }
        if (isset($current_level['id']) && \DB::table('rebet_config')->where('status_switch', 1)->where('user_level_id', $current_level['id'])->value('id')) {
            $data[] = ['name' => $msg[3], 'type' => 3];
        }
        if ($current_level['draw_count']) {
            $data[] = ['name' => $msg[4], 'type' => 4];
        }
        return $data;
    }

    /*
     *  用户层级升级
     * @param $user_id  int 用户ID
     * @return  $profile array()  当前profile表的数据信息
     */
    public function upgradeLevelMsg(array $user)
    {
        if (!isset($user['ranting'])) {
            return false;
        }
        $ranting = $user['ranting']; // 用户等级
        $profile = (array)\DB::table('user_data')->where('user_id', $user['id'])->first();
        $profile['deposit_amount'] = $profile['deposit_amount'] ? $profile['deposit_amount'] : 0;
        $profile['order_amount'] = $profile['order_amount'] ? $profile['order_amount'] : 0;
        $level = \DB::table('user_level')->where('level', '>', $ranting)
            ->where('deposit_money', '<=', $profile['deposit_amount'])
            ->where('lottery_money', '<=', $profile['order_amount'])
            ->orderBy('level', 'ASC')->get()->toArray();
        if (!$profile || !$level) {
            return false;
        }
        try {
            $this->db->getConnection()->beginTransaction();
            $cur_level = [];
            $level_money = 0;
            $draw_count = 0;
//            $wallet = new \Logic\Wallet\Wallet($this->ci);
            foreach ($level as $val) {
                // 做下限制，以防并发等其它原因导致重复升级赠送
                if ($this->redis->get('userlevelUpdate' . $user['id'] . $val->level)) {
                    continue;
                }
                $this->redis->setex('userlevelUpdate' . $user['id'] . $val->level, 5 * 60, 1);
                //最终达到的级数，可能一下升级两级以下 -PS需要可能性小，也要考虑下
                //到达该级发送消息通道 以便弹窗提示 与赠送相应等级彩金
                $cur_level = $val;
                if (!$cur_level->level) return false;
                $level_money += $val->promote_handsel;
                $draw_count += is_numeric($val->draw_count) ? $val->draw_count : 0; //draw_count字段可能为json，为json时取为0
                //赠送相应等级彩金  写流水
                $money = $val->promote_handsel;
                if ($money <= 0) {
                    continue;
                }
                $memo = $this->lang->text("Upgrade to %s with %s yuan bonus", [$val->name, ($val->promote_handsel / 100)]);
                $deal_no = FundsDealLog::generateDealNumber();
                $data = [//记录
                    'award_date'  => date('Y-m-d',time()),
                    'user_id'     => $user['id'],
                    'user_name'   => $user['name'],
                    'money'       => $money,
                    'level'       => $val->level,
                    'status'      => 2,
                    'dml_amount'  => $money * $val->upgrade_dml_percent / 10000
                ];
                $monthly_id = \DB::table('user_level_winnings')->insertGetId($data);
//                $wallet->addMoney($user, $deal_no, $money, FundsDealLog::TYPE_LEVEL_MANUAL1, $memo, $money * $val->upgrade_dml_percent / 10000);
            }
            if (empty($cur_level)) {
                $this->db->getConnection()->rollback();
                return false;
            }
            //用户升级后当前的等级赠送金额
            if ($level_money) {
                //升级等级需要发送消息通知
                $level_money = $level_money / 100;
                $to_day = date('Y-m-d',time());
                /*switch ($user['ranting']) {
                    case 0:
                        //$content = ["Dear %s, %s's %s level prize money has arrived", $user['name'], $cur_level->name, $level_money];
                        $content = ["Dear Customer,We are pleased to inform you that your VIP level has been upgraded, and as a result, you are entitled to receive a VIP level upgrade reward. Upgrade Date: %s Promotion Bonus: %s", $to_day, $level_money];
                        $title   = ["%s lottery", $cur_level->name];
                        $title   = json_encode($title);
                        break;
                    default:
                        $content = ["Dear Customer,We are pleased to inform you that your VIP level has been upgraded, and as a result, you are entitled to receive a VIP level upgrade reward. Upgrade Date: %s Promotion Bonus: %s", $to_day, $level_money];
                        $title   = "Level reward";
                }*/
                $content = ["Dear Customer,We are pleased to inform you that your VIP level has been upgraded, and as a result, you are entitled to receive a VIP level upgrade reward. Upgrade Date: %s Promotion Bonus: %s", $to_day, $level_money];
                $title = json_encode(["%s lottery", $cur_level->name]);
                $content = json_encode($content);
                $insertId = Message::insertGetId([
                    'send_type' => 3,
                    'title' => $title,
                    'admin_uid' => 0,
                    'admin_name' => 0,
                    'recipient' => $user['name'],
                    'user_id' => $user['id'],
                    'type' => 2,
                    'status' => 1,
                    'content' => $content,
                    'created' => time(),
                    'updated' => time(),
                ]);
                \Utils\MQServer::send('user_message', ['filed' => 'id', 'inArray' => [$user['id']], 'type' => 1, 'm_id' => $insertId]);
            }
            //用户升级后赠送的抽奖次数金额
            if ($draw_count) {
                UserData::where('user_id', $user['id'])->update(['draw_count' => \DB::raw('draw_count+' . $draw_count)]);

                $this->luckyInti($user['id'], $draw_count);

                //升级等级需要发送消息通知
                switch ($user['ranting']) {
                    case 0:
                        $content = ["Dear %s, the number of lucky draw presented by %s has reached %s", $user['name'], $cur_level->name, $draw_count];
                        $title   = ["%s gift", $cur_level->name];
                        $title   = json_encode($title);
                        break;
                    default:
                        $content = ["Dear %s, congratulations on upgrading from LV%s to %s. The number of lucky draw has reached %s", $user['name'], $user['ranting'], $cur_level->name, $draw_count];
                        $title = "Promotion gift";
                }
                $content = json_encode($content);
                $insertId = Message::insertGetId([
                    'send_type' => 3,
                    'title' => $title,
                    'admin_uid' => 0,
                    'admin_name' => 0,
                    'recipient' => $user['name'],
                    'user_id' => $user['id'],
                    'type' => 2,
                    'status' => 1,
                    'content' => $content,
                    'created' => time(),
                    'updated' => time(),
                ]);
                \Utils\MQServer::send('user_message', ['filed' => 'id', 'inArray' => [$user['id']], 'type' => 1, 'm_id' => $insertId]);
            }
            if ($user['ranting'] > 0) {
                //升级消息通知
                $content  = ["Dear %s, congratulations on upgrading from LV%s to %s.", $user['name'], $user['ranting'], $cur_level->name];
                $content  = json_encode($content);
                $insertId = Message::insertGetId([
                    'send_type' => 3,
                    'title' => "Promotion news",
                    'admin_uid' => 0,
                    'admin_name' => 0,
                    'recipient' => $user['name'],
                    'user_id' => $user['id'],
                    'type' => 2,
                    'status' => 1,
                    'content' => $content,
                    'created' => time(),
                    'updated' => time(),
                ]);
                \Utils\MQServer::send('user_message', ['filed' => 'id', 'inArray' => [$user['id']], 'type' => 1, 'm_id' => $insertId]);
            }
            $this->redis->set('userlevelUpdateWindows' . $user['id'], 1);
            \Model\User::where('id', $user['id'])->update(['ranting' => $cur_level->level]);
            //当前等级用户加+1，之前用户等级-1
            \Model\UserLevel::where('level', $cur_level->level)->update(['user_count' => $cur_level->user_count + 1]);
            \Model\UserLevel::where('level', $user['ranting'])->update(['user_count' => \DB::raw('user_count-1')]);
            $this->db->getConnection()->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->getConnection()->rollback();
            return false;
        }
    }

    /*
     *  用户充值转卡彩金  现转卡彩金只针对线下
     * @param $user_id  int 用户ID
     * @return  $profile array()  当前profile表的数据信息
     */
    public function sendTransferHandsel($user, $order_number, $money)
    {
        $wallet = new \Logic\Wallet\Wallet($this->ci);
        $cent = UserLevel::where('level', $user['ranting'])->first();
        if (!$cent || $money <= 0)
            return false;
        $cent = $cent->toArray();
        $send_money = $money * $cent['transfer_handsel'] / 10000;
        if (!$send_money) {
            return false;
        }
        //升级等级需要发送消息通知
        $user['name'] = $user['user_name'];
        $content  = ["Dear %s, the %s yuan prize money for LV%s card transfer has arrived", $user['name'], ($send_money / 100), $user['ranting']];
        $content  = json_encode($content);
        $insertId = Message::insertGetId([
            'send_type' => 3,
            'title' => "Level manual2",
            'admin_uid' => 0,
            'admin_name' => 0,
            'recipient' => $user['name'],
            'user_id' => $user['id'],
            'type' => 2,
            'status' => 1,
            'content' => $content,
            'created' => time(),
            'updated' => time(),
        ]);
        \Utils\MQServer::send('user_message', ['filed' => 'id', 'inArray' => [$user['id']], 'type' => 1, 'm_id' => $insertId]);
        $memo = $this->lang->text("Offline recharge %s, LV%s card transfer bonus %s yuan", [($money / 100), $user['ranting'],  ($send_money / 100)]);
        $wallet->addMoney($user, $order_number, $send_money, FundsDealLog::TYPE_LEVEL_MANUAL2, $memo, $send_money * $cent['upgrade_dml_percent'] / 10000);
    }

    public function luckyInti($id, $draw_count)
    {
        $countSum = $this->redis->hget(\Logic\Define\CacheKey::$perfix['luckyCountSum'] . date('Y-m-d'), $id);
        if ($countSum === null) {
            /* 查询幸运转盘配置*/
            $luckyData = DB::table('active')->where('type_id', 6)->select(['id'])->first();
            if (empty($luckyData)) {
                $this->redis->hset(\Logic\Define\CacheKey::$perfix['luckyCountSum'] . date('Y-m-d'), $id, 0);
                return;
            }
            /* 查询幸运转盘rule*/
            $ruleData = DB::table('active_rule')->select(['limit_times'])->where('active_id', $luckyData->id)->first();
            if ($ruleData) {
                $num = $countSum + (int)$ruleData->limit_times + $draw_count;
            } else {
                $num = $countSum + $draw_count;
            }
            $this->redis->hset(\Logic\Define\CacheKey::$perfix['luckyCountSum'] . date('Y-m-d'), $id, $num);
        } else {
            $this->redis->hIncrBy(\Logic\Define\CacheKey::$perfix['luckyCountSum'] . date('Y-m-d'), $id, $draw_count);
        }
    }


    /**
     * 查询用户身份验证状态
     *
     * @param  [type] $userId [description]
     *
     * @return [type]         [description]
     */
    public function getUserAuthInfo($params, $userId)
    {
        $user = \Model\User::where($params, $userId)
            ->select(['auth_status'])
            ->first();
        return $user;
    }

    /**
     * 注册限制IP数
     * @return bool
     */
    public function registerLimitCountIp()
    {
        $system = SystemConfig::getModuleSystemConfig('system');
        if (!isset($system['register_limit_ip_count']) || $system['register_limit_ip_count'] == 0) {
            return  true;
        }
        $ip = \Utils\Client::getIp();
        $ip_count = \DB::table('user')->where('ip', \DB::raw("inet6_aton('$ip')"))->count();
        if ($ip_count >= $system['register_limit_ip_count']) {
            return false;
        }
        return true;
    }

    /**
     * IP开关设置
     */
    public function registerLimitIp()
    {
        $system = SystemConfig::getModuleSystemConfig('system');
        if (!isset($system['register_limit_ip_switch']) || $system['register_limit_ip_switch'] == 0 || empty($system['register_limit_ip_list'])) {
            return  true;
        }
        $ipList = explode(';', $system['register_limit_ip_list']);
        foreach ($ipList as $value) {
            $item = explode(',', $value);
            $ip = \Utils\Client::getIp();
            if ($ip == trim($item[0])) {
                $ip_count = \DB::table('user')->where('ip', \DB::raw("inet6_aton('$ip')"))->count();
                if ($ip_count > $item[1]) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * 注册送彩金
     * @param $user_id
     * @param $mobile
     * @return bool
     */
    public function registerSendGift($user_id, $mobile)
    {
        $info = \DB::table('user_register_send_gift')->where('mobile', $mobile)->first();
        if ($info) {
            $send_money = $info->gift;
            $play_code = $info->dml;
            $memo = "Activity gift {$info->gift}";
            $ip = \Utils\Client::getIp();
            $re = (new \Logic\Recharge\Recharge($this->ci))->handSendCoupon($user_id, $play_code, $send_money, $memo, $ip);
            if ($re) {
                \DB::table('user_register_send_gift')->where('mobile', $mobile)->update(['status' => 1, 'updateAt' => date('Y-m-d H:i:s')]);
            }
            return $re;
        }
        return true;
    }

    /**
     * 注册统计发送消息
     */
    public function statisticsRegisterMsg($userId, $appId, $devKey, $appsflyerId,$eventName)
    {
        $mqMsg = [
            'user_id' => $userId,
            'eventName' => $eventName,
            'app_id' => $appId,
            'dev_key' => $devKey,
            'appsflyer_id' => $appsflyerId
        ];
        \Utils\MQServer::send('user_statistics_req_dep', $mqMsg);
        return true;
    }

    /**
     * firebase注册发送消息
     * @param $userId
     * @param $eventName
     * @return bool
     */
    public function firebaseRegisterMsg($userId, $eventName){
        $firebase_app_id    = trim($this->request->getParam('firebase_app_id'));
        $api_secret         = trim($this->request->getParam('api_secret'));
        $app_instance_id    = trim($this->request->getParam('app_instance_id'));
        $fire_user_id       = trim($this->request->getParam('user_id'));

        if($firebase_app_id && $api_secret && $app_instance_id){
            $mqMsg = [
                'user_id'           => $userId,
                'eventName'         => $eventName,
                'firebase_app_id'   => $firebase_app_id,
                'api_secret'        => $api_secret,
                'app_instance_id'   => $app_instance_id,
                'fire_user_id'      => $fire_user_id
            ];
            \Utils\MQServer::send('user_statistics_req_dep', $mqMsg);
            return true;
        }
        return false;

    }

    /**
     * adjust 注册发送消息
     * @param $userId
     * @return bool
     */
    public function adjustRegisterMsg($userId,$eventName){
        $idfa       = trim($this->request->getParam('idfa'));
        $gps_adid   = trim($this->request->getParam('gps_adid'));
        $adid       = trim($this->request->getParam('adid'));
        $fire_adid  = trim($this->request->getParam('fire_adid'));
        $oaid       = trim($this->request->getParam('oaid'));
        $app_token  = trim($this->request->getParam('app_token'));
        $api_token  = trim($this->request->getParam('api_token'));
        $register_token       = trim($this->request->getParam('register'));
        $deposit_token        = trim($this->request->getParam('deposit'));
        $first_deposit_token  = trim($this->request->getParam('first_deposit'));
        $login_test_token     = trim($this->request->getParam('login_test'))??'';

        if(($adid || $idfa || $gps_adid || $fire_adid || $oaid) && $app_token && $api_token){
            $event_token_json = json_encode(['register' => $register_token, 'deposit'=> $deposit_token, 'first_deposit' => $first_deposit_token,'login_test' => $login_test_token]);

            $mqMsg = [
                'user_id'       => $userId,
                'eventName'     => $eventName,
                'app_token'     => $app_token,
                'api_token'     => $api_token,
                'event_token_json' => $event_token_json,
            ];

            $idfa && $mqMsg['idfa']             = $idfa;
            $gps_adid && $mqMsg['gps_adid']     = $gps_adid;
            $adid && $mqMsg['adid']             = $adid;
            $fire_adid && $mqMsg['fire_adid']   = $fire_adid;
            $oaid && $mqMsg['oaid']             = $oaid;

            \Utils\MQServer::send('user_statistics_req_dep', $mqMsg);
            return true;
        }
        return false;

    }

    /**
     * 第三方统计发送消息
     */
    public function thirdSendMsg($userId,$eventName='register'){
        $appId = $this->request->getParam('app_id');
        $devKey = $this->request->getParam('dev_key');
        $appsflyerId = $this->request->getParam('appsflyer_id');
        if (!empty($appId) && !empty($devKey) && !empty($appsflyerId)) {
            $this->statisticsRegisterMsg($userId, $appId, $devKey, $appsflyerId,$eventName);
        }
        $this->firebaseRegisterMsg($userId,$eventName);
        $this->adjustRegisterMsg($userId,$eventName);
    }

    /**
     * 游戏注意查询用户账号
     * @param $user_id
     * @return mixed
     */
    public function getGameUserNameById($user_id)
    {
        $user_name = $this->redis->hGet('game_user_name', $user_id);
        if (is_null($user_name)) {
            $user_name = \Model\User::getAccount($user_id);
            $this->redis->hSet('game_user_name', $user_id, $user_name);
            $this->redis->expire('game_user_name', 86400);
        }
        return $user_name;
    }

    /**
     * 注册验证码开关
     * @return bool|mixed
     */
    public function registerVerify(){
        return SystemConfig::getModuleSystemConfig('register')['register_verify_switch'] ?? true;
    }

    /**
     *  提现验证码开关
     * @return bool|mixed
     */
    public function withdrawVerify($userId){
        $withdraw_verify_switch= SystemConfig::getModuleSystemConfig('withdraw')['withdraw_verify_switch'] ?? false;
        if($withdraw_verify_switch){
            $is_verify=DB::table('user')->where('id',$userId)->value('is_verify');
            if(!$is_verify){
                $withdraw_verify=true;
            }
        }
        return $withdraw_verify ?? false;
    }
}
