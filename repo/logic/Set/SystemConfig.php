<?php

namespace Logic\Set;

use Model\Admin\LogicModel;

class SystemConfig extends \Logic\Logic
{
    public static $cache = [];
    /**
     * 全局设置 tz
     */
    const SET_GLOBAL = 'system.config.global.key';
    /**
     * 全局设置 tz
     */
    const SET_GLOBAL_FORMAT = 'system.config.global.format';
    /**
     * 模块设置 tz
     */
    const SET_GLOBAL_MODULE = 'system.config.global.key.';

    /**
     * 第三方客服
     */
    const SET_3TH_SERVICE = 'system.config.third.service';

    const module = [
        'register' => '登录注册',
        'market' => '推广',
        'rakeBack' => '人人代理默认返佣（%）',
        'activity' => '活动设置',
        'login' => '登录后台',
        //    'audit' => '有效投注出款稽核（%） 说明：若彩票稽核设置80%，玩家投注100，该玩家实际打码量为100×80%=80',
        'recharge' => '充值',
        'withdraw' => '提现',
        // 'lottery' => '彩票设置',
        'system'    => '系统设置'
    ];
    const currency_en = [
        '1' => '菲律宾比索(PHP)',
    ];
    /**
     * @return array|bool
     */
    public function getService() {
        return ['code' => '',];
    }

    public function memberControls() {
        return [
            // true 显示真实姓名， false 显示姓+*号
            'true_name' => false,
            // true 显示完整银行卡号，false 显示部份卡号
            'bank_card' => false,
            // true 显示完整的QQ、微信、skype等信息，false 显示部份
            'address_book' => false,
            // 只允许以用户名查询用户信息，不直接显示列表
            'user_search' => false,
        ];
    }

    public function lotteryConfig() {
        return [
            'alias' => [
                'XYRB' => 'pc28',   //幸运28
                'KS'   => 'k3',     //快3
                'SSC'  => 'ssc',    //时时彩
                'SYXW' => '11x5',   //11选5
                //'KL8'=>'pc28',
                'SC' => 'sc',       //赛车
                'LHC' => 'lhc',     //六合彩
                'BJC' => 'bjc',     //百家彩
            ],
            'games' => [
                'GAME' => '电子',   //幸运28
                'LIVE'   => '真人',     //快3
                'SPORT'  => '体育',    //时时彩
                'SYXW' => '11x5',   //11选5
                'QP' => '棋牌',       //赛车
                'BY' => '捕鱼',     //六合彩
                'CP' => '彩票',     //百家彩
            ],
        ];
    }

    public static function getSystemConfigData($module,$key){
        $value = \Model\Admin\SystemConfig::where('state','enabled')
            ->where('module',$module)
            ->where('key',$key)
            ->value('value');
        return $value;
    }

    /**
     * @todo 作废
     * @return mixed
     */
    public static function globalSystemData() {
        global $app;
        $redis = $app->getContainer()->redis;
        $config = $redis->get(self::SET_GLOBAL);
        if($config){
            return json_decode($config,true);
        }
        $config = \Model\Admin\SystemConfig::where('state','enabled')->get()->toArray();

        $redis->set(self::SET_GLOBAL,json_encode($config));
        return $config;
    }

    /**
     * 根据module获取系统配置
     * @param null $module system_config表module
     * @return array|mixed
     */
    public static function getModuleSystemConfig($module = null) {
        global $app;
        $redis = $app->getContainer()->redis;

        if(is_null($module)){
            return [];
        }

        $res = $redis->get(self::SET_GLOBAL_MODULE . $module);
        if(!is_null($res) || !empty($res)){
            $res = json_decode($res, true);
            return $res;
        }

        $system_config = \Model\Admin\SystemConfig::where('state','enabled')->where('module', $module)->get()->toArray();

        //无配置，防止击穿
        if(empty($system_config)){
            $redis->setex(self::SET_GLOBAL_MODULE . $module, 600, '{}');
            return [];
        }

        $res = [];
        $new_market = [];
        foreach ($system_config as $val) {
            if($val['module'] == 'recharge_type'){
                $res[$val['module']][] = [
                    'name' => $val['name'],
                    'key'  => $val['key'],
                    'value' => $val['value'],
                ];
            }else{
                switch ($val['type']) {
                    case 'int':
                        $res[$val['module']][$val['key']] = intval($val['value']);
                        break;
                    case 'bool':
                        $res[$val['module']][$val['key']] = boolval($val['value']);
                        break;
                    case 'json':
                        $res[$val['module']][$val['key']] = json_decode($val['value'],true);
                        break;
                    default:
                        $res[$val['module']][$val['key']] = $val['value'];
                }
            }

            if ($val['module'] == 'market' && strpos($val['key'], 'h5_url') === 0) {
                $new_market[] = [
                    'id' => $val['id'],
                    'key' => $val['key'],
                    'name' => $val['name'],
                    'value' => $val['value'],
                    'state' => $val['state'],
                    'is_new' => 0    //1表示新增数据，0表示已有的数据
                ];
            }
        }
        if($module == 'market'){
            $res['new_market'] = $new_market;
        }

        if(!empty($res['recharge']['recharge_money_value'])){
            ksort($res['recharge']['recharge_money_value']);
            $res['recharge']['recharge_money_value'] = array_values($res['recharge']['recharge_money_value']);
        }

        $redis->setex(self::SET_GLOBAL_MODULE . $module, 86400, json_encode($res[$module], JSON_UNESCAPED_UNICODE));
        return $res[$module];
    }

    /**
     * @todo 作废
     * @param null $module
     * @return array
     */
    public static function getGlobalSystemConfig($module = null) {
        $system_config = self::globalSystemData();

        $res = [];
        $new_market = [];
        foreach ($system_config as $val) {
            if($val['module'] == 'recharge_type'){
                $res[$val['module']][] = [
                    'name' => $val['name'],
                    'key'  => $val['key'],
                    'value' => $val['value'],
                ];
            }else{
                switch ($val['type']) {
                    case 'int':
                        $res[$val['module']][$val['key']] = intval($val['value']);
                        break;
                    case 'bool':
                        $res[$val['module']][$val['key']] = boolval($val['value']);
                        break;
                    case 'json':
                        $res[$val['module']][$val['key']] = json_decode($val['value'],true);
                        break;
                    default:
                        $res[$val['module']][$val['key']] = $val['value'];
                }
            }

            if ($val['module'] == 'market' && strpos($val['key'], 'h5_url') === 0) {
                $new_market[] = [
                    'id' => $val['id'],
                    'key' => $val['key'],
                    'name' => $val['name'],
                    'value' => $val['value'],
                    'state' => $val['state'],
                    'is_new' => 0    //1表示新增数据，0表示已有的数据
                ];
            }
        }
        $res['new_market'] = $new_market;

        if(!empty($res['recharge']['recharge_money_value'])){
            ksort($res['recharge']['recharge_money_value']);
            $res['recharge']['recharge_money_value'] = array_values($res['recharge']['recharge_money_value']);
        }
        $res = $module && isset($res[$module]) && is_array($res[$module]) ? $res[$module] : $res;
        return $res;
    }
    /*
     * $array1  修改的值
     * $array2  修改前的值
     */
    public function updateSystemConfig(array $array1,array $array2) {
        $charge = array_diff_assoc2_deep($array1, $array2);
        $log = new LogicModel();
        foreach ($charge as $module=>$value) {  //模块
            foreach ($value as $k => $v) {  //模块内的值
//                print_r($array2[$module]);exit;
                if(is_array($v) && count($v) <= 0 || is_array($v) && !isset($array2[$module][$k])) {
                    continue;
                }
                if (is_array($v)) {
                    $v = $v + $array2[$module][$k];
                    $v = json_encode($v,JSON_UNESCAPED_UNICODE);
                }
                $desc = \Model\Admin\SystemConfig::where('module', $module)
                    ->where('key', $k)->first();
                if(is_null($desc)){
                    continue;
                }
                $log->logs_type = '更新/修改';
                switch ($desc->type) {
                    case 'int':case 'string':
                    $log->opt_desc = $desc->name.'('. $array2[$module][$k].' 改 '.$v.')';break;
                    case 'bool':
                        $log->opt_desc = $v ? $desc->name.'(开)' : $desc->name.'(关)';break;
                    default:
                        $tmp = array_values(json_decode($v,true));
                        if(strpos($k,'money') !== false) {
                            foreach ($tmp as &$t) {
                                $t = $t / 100;
                            }
                        }
                        $log->opt_desc = $desc->name.'('.json_encode($tmp,JSON_UNESCAPED_UNICODE ).')';
                }
                $log->log();
                \Model\Admin\SystemConfig::where('module', $module)->where('key', $k)->update(['value' => $v]);

                $this->redis->del(SystemConfig::SET_GLOBAL_MODULE.$module);
            }
        }
        if($charge) {
            $this->redis->del(\Logic\Set\SystemConfig::SET_GLOBAL);
        }
        return true;
    }

    /**
     *  唐朝启动后配置参数
     */
    public function getStartGlobal() {
        $_SERVER['REQUEST_SCHEME'] = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : '';
        $_SERVER['HTTP_HOST'] = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
        $pusherio = $this->ci->get('settings')['pusherio'];
        $config['system']        = SystemConfig::getModuleSystemConfig('system');
        $config['register']      = SystemConfig::getModuleSystemConfig('register');
        $config['withdraw']      = SystemConfig::getModuleSystemConfig('withdraw');
        $config['activity']      = SystemConfig::getModuleSystemConfig('activity');
        $config['agent']         = SystemConfig::getModuleSystemConfig('agent');
        $config['market']        = SystemConfig::getModuleSystemConfig('market');
        $config['recharge']      = SystemConfig::getModuleSystemConfig('recharge');
        $config['recharge_type'] = SystemConfig::getModuleSystemConfig('recharge_type');
        $config['website']       = SystemConfig::getModuleSystemConfig('website');
        $config['market2']       = SystemConfig::getModuleSystemConfig('market2');
        $config['direct']        = SystemConfig::getModuleSystemConfig('direct');
        $config['kagame']        = SystemConfig::getModuleSystemConfig('kagame');
        $data = [
            'pusherio_server' => is_array($pusherio['server_url']) ? current($pusherio['server_url']) : $pusherio['server_url'],  //pusherio socketio服务器地址
            'code' =>  '',//第三方客服URL
            'WeChat_login' => $config['register']['WeChat_login'] ?? true,
            'maintaining' => $config['system']['maintaining'],
            'site_pc_logo' => $config['system']['site_pc_logo'] ? showImageUrl($config['system']['site_pc_logo']) : '',
            'site_h5_logo' => $config['system']['site_h5_logo'] ? showImageUrl($config['system']['site_h5_logo']) : '',
            'first_WeChat_binding' => $config['register']['first_WeChat_binding'] ?? true,
            'register_type' =>  $config['register']['register_type'] ?? 2,
            'no_login_trial_service' => $config['register']['no_login_trial_service'] ?? true,
            'xima' => $config['system']['xima'] ?? true,
            'kefu_code' => $config['system']['kefu_code'] ?? '',
            'active_progress'=>$config['system']['active_progress'] ?? false,
            'withdraw_min' => $config['withdraw']['withdraw_money']['withdraw_min'] ?? 0,
            'withdraw_max' => $config['withdraw']['withdraw_money']['withdraw_max'] ?? 0,
            'agent_apply_desc' => $config['agent']['agent_apply_desc'],
            'addition_switch'=>$config['activity']['addition_switch'] ?? false,
            'today_task_switch'=>$config['activity']['today_task_switch'] ?? false,
            'withdraw_bkge_min' => $config['withdraw']['withdraw_bkge_money']['withdraw_min'] ?? 0,              //盈亏返佣提现限额
            'withdraw_bkge_max' => $config['withdraw']['withdraw_bkge_money']['withdraw_max'] ?? 999999900,
            'register_verify_switch'=>$config['register']['register_verify_switch'] ?? true
        ];
        //判断客服是否返回
        $uid = $this->auth->getUserId();
        $user_vip = 0;
        if($uid > 0){
            $user = (new \Logic\User\User($this->ci))->getUserInfo($uid);
            $user_vip = $user['ranting'];
        }

        if(!empty($config['market']['service'])){
            if(isset($config['system']['kefu_url_vip']) && !empty($config['system']['kefu_url_vip'])){
                $kefu_url_arr = explode(',', $config['system']['kefu_url_vip']);
                if(in_array($user_vip,$kefu_url_arr)){
                    $data['code'] = $config['market']['service'];
                }
            }
        }
        if(!empty($config['system']['kefu3_code']) && empty($data['code'])){
            if(isset($config['system']['kefu3_code_vip']) && !empty($config['system']['kefu3_code_vip'])){
                $kefu_code_arr = explode(',', $config['system']['kefu3_code_vip']);
                if(in_array($user_vip,$kefu_code_arr)){
                    $data['code'] = $config['system']['kefu3_code'];
                }
            }
        }

        if(!empty($config['system']['kefu_code'])){
            if(isset($config['system']['kefu_code_vip']) && !empty($config['system']['kefu_code_vip'])){
                $kefu_code_arr = explode(',', $config['system']['kefu_code_vip']);
                if(!in_array($user_vip,$kefu_code_arr)){
                    $data['kefu_code'] = '';
                }
            }
        }


        $recharge = $config['recharge']['recharge_money_value'] ?? [10000, 20000, 50000, 100000,];
        //特殊处理recharge_type
        foreach($config['recharge_type'] as $key => $val){
            $config['recharge_type'][$val['key']] = boolval($val['value']);
        }
        if(!empty($config['system']['currency_id'])){
            $currency_name=\DB::table('currency_exchange_rate')->where('id',$config['system']['currency_id'])->value('alias');
        }

        $coin = [
            'withdraw_need_idcard' => $config['withdraw']['withdraw_need_idcard'] ?? true,
            'withdraw_need_mobile' => $config['withdraw']['withdraw_need_mobile'] ?? true,
            'min_money' => $config['recharge']['recharge_money']['recharge_min'] ?? 0,// 充值金额
            'max_money' => $config['recharge']['recharge_money']['recharge_max'] ?? 0,//充值最高金额
            'recharge_money_set' => $config['recharge']['recharge_money_set'] ?? true,
            'recharge_money_value' => array_values($recharge),
            'recharge_money_value_list' => array_values($recharge),
            'stop_withdraw' => $config['lottery']['stop_withdraw'] ?? false, // 暂停提现开关
            'stop_deposit' => $config['lottery']['stop_deposit'] ?? false, // 暂停充值
            'recharge_autotopup' => $config['recharge']['autotopup'] ?? false,
            'recharge_qrcode' => $config['recharge']['qrcode'] ?? false,
            'recharge_offline' => $config['recharge']['offline'] ?? false,
            'recharge_711' => $config['recharge']['direct'] ?? false,
            'recharge_grab' => $config['recharge']['grabpay'] ?? false,
            'recharge_qr' => $config['recharge_type']['qr'] ?? false,
            'recharge_bpia' => $config['recharge_type']['bpia'] ?? false,
            'recharge_ubpb' => $config['recharge_type']['ubpb'] ?? false,
        ];
        $spread = [
            'down_url' => $config['market']['down_url'],
            'app_name' => $config['market']['app_name'],
            'app_desc' => $config['market']['app_desc'],
            'h5_url' => $config['market']['h5_url'],
            'pc_url' => $config['market']['pc_url'],
            'spread_url' => $config['market']['spread_url'],
            'certificate_url' =>  $config['market2']['certificate_url'],
            'certificate_switch' =>  $config['market2']['certificate_switch'],
            'app_spead_url' =>  $config['market2']['app_spead_url'],
        ];
//        $lottery = [
//            'stop_bet' => $config['lottery']['stop_bet'] ?? false, // 暂停下单开关
//            'stop_chasing' => $config['lottery']['stop_chasing'] ?? false, // 暂停追号下单开关
//            'unusual_period_auto' => $config['lottery']['unusual_period_auto'] ?? true,  //彩期异常销售自动开关
//        ];
        $lottery = [];
        $direct = [
            'direct_switch' => $config['direct']['direct_switch']
        ];

        $website = [
            'landingpage_img' => showImageUrl($config['website']['landing_page_config']['img']),   //落地页图片
            'landingpage_jump_url' => $config['website']['landing_page_config']['jump_url'] ?? "",   //落地页跳转地址
            'landingpage_video' => showImageUrl($config['website']['landing_page_config']['video']),   //落地页视频
            'landingpage_button' => isset($config['website']['landing_page_config']['button']) ? showImageUrl($config['website']['landing_page_config']['button']) : "",   //落地页按钮跳转地址
            'agent_desc_img' => showImageUrl($config['website']['agent_desc_config']['img']),   //代理说明图片
            'app_boot_type' => $config['website']['app_boot']['type'] ?? '',                 //app落地页风格
            'app_boot_top_img' => showImageUrl($config['website']['app_boot']['top_img']),                 //顶部展示图
            'app_boot_live_url' => $config['website']['app_boot']['live_url'] ?? '',                 //视频内容
            'app_boot_download_img' => showImageUrl($config['website']['app_boot']['download_img']),         //下载引导页
        ];
        $kagame = [
            'kagame' => [
                'user_balance_port' => $config['kagame']['user_balance_port'] ?? 1,       //用户余额弹窗端口 1全部，2app，3h5，4pc
                'switch' => $config['kagame']['switch'] ?? true,                          //用户余额弹窗开关 1开，0关
                'text' => $config['kagame']['text'],                                      //用户余额弹窗内容
                'user_balance_condition' => $config['kagame']['user_balance_condition']
            ]
        ];
        $currency=[
            'currency_name'=>$currency_name ?? ''
        ];

        return array_merge($data,$coin,$spread,$lottery,$website,$direct,$kagame,$currency);
    }

    /*
     * 获取隐藏的最高赔率和最高派奖
     */
    public function getMaxOdds() {
        global $logger;
//        $data = $this->redis->hGetALL(SystemConfig::SET_GLOBAL_MODULE.'lottery');
        $data = self::getModuleSystemConfig('lottery');
//        if(!$data) {
//            $data = self::getModuleSystemConfig('lottery');
//            $this->redis->hMset(SystemConfig::SET_GLOBAL_MODULE.'lottery', $data);
//        }
        if(!is_array($data)){
            $logger->info($data);
        }
        // 最高派奖
        $maxSendPrize = isset($data['max_award']) ? $data['max_award'] : 50 * 10000 * 100;
        // 最高隐藏赔率
        $maxOdds = isset($data['max_odds']) ? $data['max_odds'] : 10000;
        return [
            $maxSendPrize,
            $maxOdds,
        ];
    }

}
