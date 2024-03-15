<?php

namespace Logic\Activity;

use DB;
use Model\Active;
use Model\ActiveApply;
use Model\ActiveSignUp;
use Model\FundsDeposit;
use \Logic\Wallet\Wallet;

/**
 * 活动模块
 */
class Activity extends \Logic\Logic
{

    protected $paramsStr = [
        'begin_time'           => '',
        'end_time'             => '',
        'title'                => '',
        'cover'                => '',
        'status'               => ['enabled' => '启用', 'disabled' => '停用'],
        'sort'                 => '',
        'description'          => '',
        'content'              => '',
        'content_type'         => ['1' => '文字', '2' => '圖片'],
        'rule'                 => '赠送金额',
        'bind_info'            => ['1' => '手机', '2' => '邮箱', '3' => '银行卡'],
        'issue_mode'           => ['manual' => '手动', 'auto' => '自动'],
        'withdraw_require_val' => '',
        'state' => [
            'manual'  => '手动',
            'auto'    => '自动参与',
            'contact' => '联系客服',
            'apply'   => '用户申请',
        ],
        'send_time' => [
            '1'  => '一个用户一次',
            '2'  => '不限制',
        ],
        'condition_recharge' => [
            0 => "没有充值记录",
            1 => "有充值记录",
            2 => "当天有充值记录",
            3 => "本周有充值记录",
            4 => "当月有充值记录",
        ],
        'send_max'             => '',
        'send_type'            => ['1' => '固定', '2' => '百分比'],
        'vender_type'          => ['1' => '全部', '2' => '线上充值', '3' => '线下充值'],
    ];

    public function bindInfo($userId, $type)
    {
        //判断是否有绑定资料活动
        $date = date("Y-m-d H:i:s");
        $sql  = "select id ,name from  active where begin_time < '$date' and end_time > '$date' and status = 'enabled' and type_id = 5";
        $res  = \DB::selectOne($sql);
        if (!$res) {
            return false;
        }
        $user = \Model\User::where('id', $userId)->first();
        //先判断是否已经参与过了。

        if (!empty($res)) {
            $res        = (array)$res;
            $activeId   = $res['id'];
            $activeName = $res['name'];
            $sql        = "select id from  active_apply where user_id = $userId and active_id = $activeId";
            if (\DB::selectOne($sql)) {
                return;//已经参与
            }
        }

        $sql  = "select * from active_rule where active_id = $activeId";
        $data = \DB::select($sql);
        if (!$data) {
            return;
        }
        $bindInfo = $data[0]->bind_info;
        if ($bindInfo) {
            $bind_info_array = explode(',', $bindInfo);
            asort($bind_info_array);
            $bindInfo = implode(',', $bind_info_array);
        }
        switch ($bindInfo) {
            case '1':
                //如果没有绑定手机就返回
                $mobile = \Model\User::where('id', $userId)->value('mobile');
                if (!$mobile) {
                    return;
                }
                break;
            case '2':
                //如果没有绑定邮箱就返回
                $email = \Model\User::where('id', $userId)->value('email');
                if (!$email) {
                    return;
                }
                break;

            case '3':
                //如果没有绑定银行卡就返回
                $bank_id = \Model\BankUser::where('user_id', $userId)->get(['id'])->toArray();
                if (!$bank_id) {
                    return;
                }
                //如果绑定了一次也返回
                if (count($bank_id) > 1) {
                    return;
                }
                break;

            case '1,2':
                //如果没有绑定邮箱+手机就返回
                $user_info = \Model\User::where('id', $userId)->first(['email', 'mobile']);
                if (!$user_info->mobile || !$user_info->email) {
                    return;
                }
                break;
            case '1,3':
                //如果没有绑定手机+银行卡就返回
                $mobile  = \Model\User::where('id', $userId)->value('mobile');
                $bank_id = \Model\BankUser::where('user_id', $userId)->value('id');
                if (!$mobile || !$bank_id) {
                    return;
                }
                break;

            case '2,3':
                //如果没有绑定邮箱+银行卡就返回
                $email   = \Model\User::where('id', $userId)->value('email');
                $bank_id = \Model\BankUser::where('user_id', $userId)->value('id');
                if (!$email || !$bank_id) {
                    return;
                }
                break;

            case '1,2,3':
                //如果没有绑定手机+邮箱+银行卡就返回
                $user_info = \Model\User::where('id', $userId)->first(['email', 'mobile']);
                $bank_id   = \Model\BankUser::where('user_id', $userId)->value('id');
                if (!$user_info->email || !$bank_id || !$user_info->mobile) {
                    return;
                }
                break;

            default:
                break;

        }
        if ($res) {
            if ((!empty($data) && in_array($type, explode(',', $bindInfo)) || !$bindInfo)) {
                $data               = $data[0];
                $activeId           = $data->active_id;
                $rule               = $data->rule;
                $withdrawRequire    = $data->withdraw_require;
                $withdrawRequireVal = $data->withdraw_require_val;
                $money              = $rule;
                if ($withdrawRequire == 'bet') {
                    $condition = $withdrawRequireVal;
                } else if ($withdrawRequire == 'times') {
                    $condition = $withdrawRequireVal * $money / 10000;
                } else {
                    $condition = 0;
                }
                // 判断是否自动发放
                if ($data->issue_mode == 'auto') {
                    $recharge = new \Logic\Recharge\Recharge($this->ci);
                    $recharge->handoutActivity($user['id'], $money, $condition, $activeId, $this->lang->text("Registration activities"), '1');

                    if (!empty($data->game_type)) {
                        $gameType = explode(',', $data->game_type);
                        foreach ($gameType as $value) {
                            $registerData = array(
                                'user_id'   => $user['id'],
                                'active_id' => $activeId,
                                'amount'    => $money,
                                'game_type' => $value
                            );
                            DB::table("active_register")->insert($registerData);
                        }
                    }

                } else {
                    // 增加入款单据(后面可能需要加锁)
                    \Model\ActiveApply::create([
                        'user_id'          => $user['id'],
                        'user_name'        => $user['name'],
                        'coupon_money'     => $data->rule,
                        'withdraw_require' => $condition,
                        'active_id'        => $activeId,
                        'active_name'      => $activeName,
                        'memo'             => $this->lang->text("Registration activities"),
                        'status'           => 'pending',
                        'state'            => 'manual'
                    ]);
                }
            }

        }
    }


    /**
     * 实名绑定活动 (没有防护？？？)
     * @param  [type] $userId [description]
     * @return [type]         [description]
     */
    public function bindIdCard($userId)
    {
        $user    = \Model\User::where('id', $userId)->first();
        $date    = date("Y-m-d H:i:s");
        $actives = \Model\Active::where('begin_time', '<', $date)
            ->where('end_time', '>=', $date)
            ->where('status', 'enabled')
            ->where('type_id', 1)
            ->get()->toArray();

        if (empty($actives) || empty($user)) {
            return false;
        }
        foreach ($actives ?? [] as $active) {
            $rule = \Model\ActiveRule::where('active_id', $active['id'])->first()->toArray();
            if ($rule['withdraw_require'] == 'bet') {
                $condition = $rule['withdraw_require_val'];
            } else if ($rule['withdraw_require'] == 'times') {
                $condition = $rule['withdraw_require_val'] * $rule['rule'] / 10000;
            } else {
                $condition = 0;
            }

            // 判断是否自动发放
            if ($rule['issue_mode'] == 'auto') {
                $recharge = new \Logic\Recharge\Recharge($this->ci);
                $recharge->handoutActivity($user['id'], $rule['rule'], $condition, $active['id'], $this->lang->text('Real name certification'), '1');
            } else {
                // 增加入款单据(后面可能需要加锁)
                \Model\ActiveApply::create([
                    'user_id'          => $user['id'],
                    'user_name'        => $user['name'],
                    'coupon_money'     => $rule['rule'],
                    'withdraw_require' => $condition,
                    'active_id'        => $active['id'],
                    'memo'             => $this->lang->text('Real name certification'),
                    'status'           => 'pending',
                    'state'            => 'manual'
                ]);
            }
        }
    }

    /**
     * 判定绑定活动条件
     * @return [type] [description]
     */
    protected function runBindInfoCondition($user, $userId, $bindInfoIds)
    {

        $configs = [
            //如果没有绑定手机就返回
            1 => function ($userId) {
                return !empty($user['mobile']) ? true : false;
            },

            //如果没有绑定邮箱就返回
            2 => function ($userId) {
                return !empty($user['email']) ? true : false;
            },

            //如果没有绑定银行卡就返回
            3 => function ($userId) {
                return \Model\BankUser::where('user_id', $userId)->count() == 1 ? true : false;
            }
        ];

        foreach ($bindInfoIds ?? [] as $bindInfoId) {
            if (isset($configs[$bindInfoId])) {
                if (!$configs[$bindInfoId]($userId)) {
                    $this->logger->info('绑定活动 用户没有通过type:' . $bindInfoId, ['user_id' => $userId]);
                }
            } else {
                $this->logger->error('绑定活动出错 发现没有定义的活动type:' . $bindInfoId, ['user_id' => $userId]);
                return false;
            }
        }

        $this->logger->info('绑定活动 用户条件通过', ['user_id' => $userId, 'bindInfoIds' => $bindInfoIds]);
        return true;
    }

    public function rechargeActive($userId, $user, $money, $needPre, $type = 'online', $tradeId = '')
    {
        //直推-充值奖励发放
//        if ($type == "online") {
//            $obj = new \Logic\Recharge\Recharge($this->ci);
//            $obj->directRechargeAward($userId,$money);
//        }

        $global = \Logic\Set\SystemConfig::getModuleSystemConfig('activity');

        $canGetBothActive = true;
        $rechargeActive   = true;

        //1：有电子优惠 0：没有电子优惠
        $hasSlotActiveCoupon = self::canGetSlotActiveCoupon($userId);
        //判断是否允许同时参与多个充值活动
        if (isset($global['canGetBothActivity']) && $global['canGetBothActivity'] == false) {
            $canGetBothActive = false;
        }

        // 是否首存
        $isNew = FundsDeposit::whereRaw("FIND_IN_SET('paid',status)")->where('money', '>', 0)->where('user_id', '=', $userId)->first();
        if (!$isNew) {
            $isNew = true;
            //首充优惠
            $isNewCoupon = true;
        } else {
            $isNew       = false;
            $isNewCoupon = false;
        }

        //判断是否当日首次存款
        $today_start = date('Y-m-d') . " 00:00:00";
        $today_end   = date('Y-m-d') . " 23:59:59";
        $deposit     = FundsDeposit::where('user_id', '=', $userId)->where('money', '>', 0)->where('status', 'paid')
            ->where('created', '>=', $today_start)
            ->where('created', '<=', $today_end)->first();
        if ($deposit) {
            $todayNew = false;
        } else {
            $todayNew = true;
        }

        /***
         * 是否用maya渠道首次充值
         ***/
        //获取paymaya渠道信息
        $isMayaNew = false;
        $channelId = \DB::table('pay_channel')->where("type", 'qr')->value('id');
        $paymentId = [];
        if(!empty($channelId)) {
            $paymentId = \DB::table('payment_channel')->where("pay_channel_id", $channelId)->pluck('id')->toArray();
            if(!empty($paymentId)) {
                $mayaCnt = FundsDeposit::whereRaw("FIND_IN_SET('paid',status)")
                    ->where('money', '>', 0)
                    ->where('user_id', '=', $userId)
                    ->whereIn('payment_id', $paymentId)
                    ->count();
            }else{
                $paymentId = [];
            }
            if ($mayaCnt <= 0) {
                $isMayaNew       = true;
            }
        }

        /***
         * 优先级：渠道首充>新人首充>每日首充
         ***/
        //是首充  且不能同时参与优惠  那就不能参与每日首充
        //检查是否有maya充值活动
        $maya_active = \DB::table('active')->where('status', '=', 'enabled')
            ->where('type_id', 17)
            ->where('begin_time', '<', date('Y-m-d H:i:s'))
            ->where('end_time', '>', date('Y-m-d H:i:s'))
            ->first();
        if(empty($maya_active)){
            $isMayaNew = false;
        }
        $fund_info = FundsDeposit::where('id',$tradeId)->first();
        if(!in_array($fund_info->payment_id,$paymentId)){
            $isMayaNew = false;
        }
        if ($isMayaNew && !$canGetBothActive) {
            $todayNew = false;
            $isNewCoupon = false;
        }

        //是首充  且不能同时参与优惠  那就不能参与每日首充
        if ($isNew && !$canGetBothActive) {
            $todayNew = false;
        }

        //有电子活动优惠 且不能同时参与优惠 那其它充值活动都不参与
        if ($hasSlotActiveCoupon && !$canGetBothActive) {
            $todayNew = false;
            //首充优惠
            $isNewCoupon = false;
        }

        //计算打码量
//        $level_config = \DB::table('user_level')->where('level', '=', $user['ranting'])->first();
//
//        if ($type == 'online') {
//            $dml_percent = $level_config ? $level_config->online_dml_percent : 1;
//        } else {
//            $dml_percent = $level_config ? $level_config->offline_dml_percent : 1;
//        }
//        $withdraw_bet           = $money * $dml_percent / 10000;
        $coupon_withdraw_bet = 0;
        $sendPrize           = 0;
        $coupon_money        = 0;
        $sumCouponMoney      = 0;
        $tempMoney           = 0;
        $sumMoney            = 0;
        $rechargeMoney       = 0;
        $activeArr           = explode(',', $needPre);
        $activeApply         = [];

        foreach ($activeArr ?? [] as $needPre) {

            $ruleData = (array)\DB::table('active_rule')
                ->join('active', 'active_rule.active_id', '=', 'active.id')
                ->where('active_rule.active_id', '=', $needPre)
                ->where('active.status', '=', 'enabled')
                ->where('begin_time', '<', date('Y-m-d H:i:s'))
                ->where('end_time', '>', date('Y-m-d H:i:s'))
                ->first(['active_rule.template_id', 'active_rule.rule', 'active_rule.send_type', 'active_rule.send_max',
                    'active_rule.withdraw_require_val', 'active_rule.issue_mode', 'active.name', 'active.status', 'active.vender_type',
                    'active_rule.give_condition', 'active_rule.give_date', 'active.vender_type']);

            if ($ruleData) {
                $template_id = $ruleData['template_id'];
                $vender_type = $ruleData['vender_type'];

                if ($type == 'online') {
                    if ($vender_type == 3) {
                        continue;
                    }
                } else {
                    if ($vender_type == 2) {
                        continue;
                    }
                }

                //充值活动
                if ($template_id == 11) {
                    if (!in_array($ruleData['give_condition'], [1, 5])) {
                        continue;
                    }
                    switch ($ruleData['give_condition']) {
                        case 2:
                            //单日累计
                            $startTime = date('Y-m-d 00:00:00', time());
                            $endTime   = date('Y-m-d 23:59:59', time());
                            break;
                        case 3:
                            //周累计
                            $startTime = date('Y-m-d 00:00:00', (time() - ((date('w') == 0 ? 7 : date('w')) - 1) * 24 * 3600));
                            $endTime   = date('Y-m-d 23:59:59', (time() + (7 - (date('w') == 0 ? 7 : date('w'))) * 24 * 3600));
                            break;
                        case  4:
                            //月累计
                            $startTime = date('Y-m-d 00:00:00', strtotime(date('Y-m', time()) . '-01 00:00:00'));
                            $endTime   = date('Y-m-d 23:59:59', strtotime(date('Y-m', time()) . '-' . date('t', time()) . ' 00:00:00'));
                            break;
                        case  5:
                            //自定义
                            $startTime = date('Y-m-d 00:00:00', time());
                            $endTime   = date('Y-m-d 23:59:59', time());
                            break;
                        default:
                            $startTime = date('Y-m-d 00:00:00', time());
                            $endTime   = date('Y-m-d 23:59:59', time());
                            break;
                    }
                    if ($ruleData['give_condition'] != 1) {
//                        $query=DB::table('funds_deposit')
//                                 ->where('user_id','=',$userId)
//                                 ->where('status', '=','paid')
//                                 ->where('created', '>=', $startTime)
//                                 ->where('created', '<=', $endTime);
//
//                        $paidActive=$query->get('active_apply');
//                        //线上充值
//                        if($ruleData['vender_type'] == 2){
//                            $query->whereRaw('FIND_IN_SET("online",state)');
//                        }elseif($ruleData['vender_type'] == 3){
//                            //线下充值
//                            $query->whereRaw('!FIND_IN_SET("online",state)');
//                        }
//                        $sumMoney=$query->sum('money');
                        $tempMoney = $money;
//                        $rechargeMoney = $money + $sumMoney;
                        $rechargeMoney  = $money;   //单日单笔直接判断该笔充值金额即可
                        $paidActiveId   = [];
                        $rechargeActive = true;
//                        if($ruleData['give_condition'] == 5){
//                            if(time() < strtotime($time[1]));{
//                                $rechargeActive = false;
//                            }
//                        }
                        if (!empty($paidActive)) {
                            foreach ($paidActive as $value) {
                                if (!empty($value->active_apply)) {
                                    $str = explode(',', $value->active_apply);
                                    foreach ($str as $item) {
                                        $paidActiveId[] = $item;
                                    }
                                }
                            }
                            //已经赠送金额
                            $sumCouponMoney = DB::table('active_apply as ap')
                                ->leftJoin('active as a', 'ap.active_id', '=', 'a.id')
                                ->leftJoin('active_rule as ar', 'ap.active_id', '=', 'ar.active_id')
                                ->where('a.type_id', '=', '11')
                                ->where('ar.give_condition', '=', $ruleData['give_condition'])
                                ->whereIn('ap.id', $paidActiveId)
                                ->sum('coupon_money');
                        }
                    } else {
                        $today_start = date('Y-m-d') . " 00:00:00";
                        $today_end   = date('Y-m-d') . " 23:59:59";
                        $deposit     = FundsDeposit::where('user_id', '=', $userId)->where('status', 'paid')
                            ->where('money', '>', 0)
                            ->where('created', '>=', $today_start)
                            ->where('created', '<=', $today_end)->first();
                        if ($deposit) {
                            $rechargeActive = false;
                        }
                        $rechargeMoney = $money;
                    }

                    if ($rechargeActive) {
                        $rule                 = $ruleData['rule'];
                        $withdraw_require_val = $ruleData['withdraw_require_val'];
                        $send_type            = $ruleData['send_type'];
                        $send_max             = $ruleData['send_max'];
                        //解析rule
                        $basePrize = 0;
                        $dml       = 0;
                        $ruleArr   = explode(';', $rule);
                        //如果只有一条规则，只要充值大于最小值即赠送优惠
                        $last = array_pop($ruleArr);
                        if ($ruleArr) {
                            foreach ($ruleArr as $k => $ruleConfig) {
                                $config_arr = explode(',', $ruleConfig);
                                if ($config_arr[0] < $rechargeMoney && $rechargeMoney <= $config_arr[1]) {
                                    $basePrize = $config_arr[2];
                                    $dml       = $config_arr[3] ?? 0;
                                    break;
                                }
                            }
                        }
                        $config_arr = explode(',', $last);
                        if ($config_arr[0] < $rechargeMoney) {
                            $basePrize = $config_arr[2];
                            $dml       = $config_arr[3] ?? 0;
                        }

                        //充值活动上限计算
                        if ($ruleData['give_condition'] != 1) {
                            $state = $ruleData['issue_mode'];
                            if ($send_type == 1) {
                                //固定金额
                                $sendPrize = $basePrize;
                            } else if ($send_type == 2) {
                                //百分比
                                $sendPrize = bcmul($money, ($basePrize / 10000), 2);
                            }
                            //累计金额上限 赠送+累计 <= 上限 取赠送, 赠送+累计 > 上限,取 上限 - 累计
                            if ($sendPrize + $sumCouponMoney > $send_max) {
                                $sendPrize = $send_max - $sumCouponMoney > 0 ? $send_max - $sumCouponMoney : 0;
                            }
                            if ($ruleData['issue_mode'] == 'auto') {
                                $coupon_money += $sendPrize;
                            }
                        } else {
                            if ($ruleData['issue_mode'] == 'auto') {
                                $state = 'auto';
                                if ($send_type == 1) {
                                    //固定金额
                                    $sendPrize = $basePrize;
                                } else if ($send_type == 2) {
                                    //百分比
                                    $sendPrize = $money * ($basePrize / 10000);
                                    $sendPrize > $send_max && $sendPrize = $send_max;
                                }
                                $coupon_money += $sendPrize;
                            } else {
                                $state = 'manual';
                                //手动领取
                                if ($send_type == 1) {
                                    //固定金额
                                    $sendPrize = $basePrize;
                                } else if ($send_type == 2) {
                                    //百分比
                                    $sendPrize = $money * ($basePrize / 10000);
                                    $sendPrize > $send_max && $sendPrize = $send_max;
                                }
                            }
                        }
                        $coupon_withdraw_bet += $sendPrize * $dml;

                        // 增加入款单据
                        if ($sendPrize) {
                            $model_active = [
                                'user_id'          => $user['id'],
                                'trade_id'         => $tradeId,
                                'user_name'        => $user['name'] ?? $user['user_name'],
                                'deposit_money'    => $money,
                                'coupon_money'     => $sendPrize,
                                'withdraw_require' => $sendPrize * $dml,
                                'active_id'        => $needPre,
                                'active_name'      => $ruleData['name'],
                                'memo'             => $ruleData['name'],
                                'status'           => 'undetermined',
                                'state'            => $state,
                                'apply_time'       => date('Y-m-d H:i:s', time()),
                            ];

                            $active_id = \DB::table('active_apply')->insertGetId($model_active);
                            array_push($activeApply, $active_id);
                        }
                    }
                }

                if ((($template_id == 2 && $isNewCoupon) || ($template_id == 3 && $todayNew)) && (in_array($vender_type, [1, 2, 3]))) {
                    $rule                 = $ruleData['rule'];
                    $withdraw_require_val = $ruleData['withdraw_require_val'];
                    $send_type            = $ruleData['send_type'];
                    $send_max             = $ruleData['send_max'];
                    //解析rule
                    $basePrize = 0;
                    $ruleArr   = explode(';', $rule);
                    //如果只有一条规则，只要充值大于最小值即赠送优惠
                    $last = array_pop($ruleArr);

                    if ($ruleArr) {
                        foreach ($ruleArr as $k => $ruleConfig) {
                            $config_arr = explode(',', $ruleConfig);
                            if ($config_arr[0] < $money && $money <= $config_arr[1]) {
                                $basePrize = $config_arr[2];
                                break;
                            }
                        }
                    }

                    $config_arr = explode(',', $last);
                    if ($config_arr[0] < $money) {
                        $basePrize = $config_arr[2];
                    }

                    // 新人首充任务百分比，每日任务万分比
                    $rate = $template_id == 2 ? 100 : 10000;

                    if ($ruleData['issue_mode'] == 'auto') {
                        $state = 'auto';
                        if ($send_type == 1) {
                            //固定金额
                            $sendPrize = $basePrize;
                        } else if ($send_type == 2) {
                            //百分比
                            $sendPrize = $money * ($basePrize / 10000);
                            $sendPrize > $send_max && $sendPrize = $send_max;
                        }
                        $coupon_money += $sendPrize;
                        //加上活动打码量
                        //                            $coupon_withdraw_bet += $sendPrize * $withdraw_require_val / 100;
                        $coupon_withdraw_bet += $sendPrize * $withdraw_require_val / $rate;
                    } else {
                        $state = 'manual';
                        //手动领取
                        if ($send_type == 1) {
                            //固定金额
                            $sendPrize = $basePrize;
                        } else if ($send_type == 2) {
                            //百分比
                            $sendPrize = $money * ($basePrize / 10000);
                            $sendPrize > $send_max && $sendPrize = $send_max;
                        }
                    }

                    // 增加入款单据
                    if ($sendPrize) {
                        $model_active = [
                            'user_id'          => $user['id'],
                            'trade_id'         => $tradeId,
                            'user_name'        => $user['name'] ?? $user['user_name'],
                            'deposit_money'    => $money,
                            'coupon_money'     => $sendPrize,
                            'withdraw_require' => $sendPrize * $withdraw_require_val / $rate,
                            'active_id'        => $needPre,
                            'active_name'      => $ruleData['name'],
                            'memo'             => $ruleData['name'],
                            'status'           => 'undetermined',
                            'state'            => $state,
                            'apply_time'       => date('Y-m-d H:i:s', time()),
                        ];

                        $active_id = \DB::table('active_apply')->insertGetId($model_active);
                        array_push($activeApply, $active_id);
                    }
                }

                if ($template_id == 17) {
                    if(!$isMayaNew) {
                        continue;
                    }
                    $rule                 = $ruleData['rule'];
                    $withdraw_require_val = $ruleData['withdraw_require_val'];
                    $send_type            = $ruleData['send_type'];
                    $send_max             = $ruleData['send_max'];
                    //解析rule
                    $basePrize = 0;
                    $ruleArr   = explode(';', $rule);
                    //如果只有一条规则，只要充值大于最小值即赠送优惠
                    $last = array_pop($ruleArr);

                    if ($ruleArr) {
                        foreach ($ruleArr as $k => $ruleConfig) {
                            $config_arr = explode(',', $ruleConfig);
                            if ($config_arr[0] < $money && $money <= $config_arr[1]) {
                                $basePrize = $config_arr[2];
                                break;
                            }
                        }
                    }

                    $config_arr = explode(',', $last);
                    if ($config_arr[0] < $money) {
                        $basePrize = $config_arr[2];
                    }

                    if ($ruleData['issue_mode'] == 'auto') {
                        $state = 'auto';
                        if ($send_type == 1) {
                            //固定金额
                            $sendPrize = $basePrize;
                        } else if ($send_type == 2) {
                            //百分比
                            $sendPrize = $money * ($basePrize / 10000);
                            $sendPrize > $send_max && $sendPrize = $send_max;
                        }
                        $coupon_money += $sendPrize;
                        //加上活动打码量
                        //                            $coupon_withdraw_bet += $sendPrize * $withdraw_require_val / 100;
                        $coupon_withdraw_bet += $sendPrize * $withdraw_require_val / 100;
                    } else {
                        $state = 'manual';
                        //手动领取
                        if ($send_type == 1) {
                            //固定金额
                            $sendPrize = $basePrize;
                        } else if ($send_type == 2) {
                            //百分比
                            $sendPrize = $money * ($basePrize / 10000);
                            $sendPrize > $send_max && $sendPrize = $send_max;
                        }
                    }

                    // 增加入款单据
                    if ($sendPrize) {
                        $model_active = [
                            'user_id'          => $user['id'],
                            'trade_id'         => $tradeId,
                            'user_name'        => $user['name'] ?? $user['user_name'],
                            'deposit_money'    => $money,
                            'coupon_money'     => $sendPrize,
                            'withdraw_require' => $sendPrize * $withdraw_require_val / 100,
                            'active_id'        => $needPre,
                            'active_name'      => $ruleData['name'],
                            'memo'             => $ruleData['name'],
                            'status'           => 'undetermined',
                            'state'            => $state,
                            'apply_time'       => date('Y-m-d H:i:s', time()),
                        ];

                        $active_id = \DB::table('active_apply')->insertGetId($model_active);
                        array_push($activeApply, $active_id);
                    }
                }

                //有电子活动优惠
                if (($template_id == 7 && $hasSlotActiveCoupon) && ($vender_type == 2 || $vender_type == 3 || $vender_type == 1)) {
                    $rule             = $ruleData['rule'];
                    $send_type        = $ruleData['send_type'];
                    $active_sign_info = ActiveSignUp::getInfo($userId, $needPre);
                    //取对应的那条规则
                    $ruleArr = explode('|', $rule)[$active_sign_info['times']];
                    $ruleArr = explode(',', $ruleArr);
                    if (!$ruleArr) {
                        continue;
                    }
                    $ratio                = $ruleArr[0] / 10000;
                    $send_max             = $ruleArr[1];
                    $withdraw_require_val = $ruleArr[2] / 10000;

                    //取最低充值金额
                    $chargeAmount = explode("|", $rule)[3];
                    if (!empty($chargeAmount) && $chargeAmount > 0) {
                        if ($money < $chargeAmount) {
                            continue;
                        }

                    }
                    $state = 'auto';

                    $sendPrize = $money * $ratio;
                    $sendPrize > $send_max && $sendPrize = $send_max;
                    $coupon_money += $sendPrize;
                    //加上活动打码量
                    $coupon_withdraw_bet += $sendPrize * $withdraw_require_val;

                    // 增加入款单据
                    if ($sendPrize) {
                        $model_active = [
                            'user_id'          => $user['id'],
                            'trade_id'         => $tradeId,
                            'user_name'        => $user['name'] ?? $user['user_name'],
                            'deposit_money'    => $money,
                            'coupon_money'     => $sendPrize,
                            'withdraw_require' => $sendPrize * $withdraw_require_val,
                            'active_id'        => $needPre,
                            'active_name'      => $ruleData['name'],
                            'memo'             => $ruleData['name'],
                            'status'           => 'undetermined',
                            'state'            => $state,
                            'apply_time'       => date('Y-m-d H:i:s', time()),
                        ];

                        $active_id = \DB::table('active_apply')->insertGetId($model_active);
                        array_push($activeApply, $active_id);
                    }
                }

                // APP充值赠送活动
                if ($template_id == 18) {
                    $rule                 = $ruleData['rule'];
                    $send_type            = $ruleData['send_type'];

                    //解析rule
                    $basePrize = 0;
                    $ruleArr   = explode(';', $rule);

                    // 查询用户完成进度
                    $progress = \DB::table('active_apply')->where('user_id', $user['id'])->where('status', 'pass')->where('active_id', $needPre)->count();
                    if ($progress >= count($ruleArr) || !isset($ruleArr[$progress])) {
                        continue;
                    }
                    $last = $ruleArr[$progress];
                    
                    $config_arr = explode(',', $last);
                    if ($config_arr[0] < $money) {
                        // 如果充值金额大于设置上限则取上限值
                        $basePrize = $config_arr[2];
                        $money = $money > $config_arr[1] ? $config_arr[1] : $money;
                    }
                    // 打码量
                    $withdraw_require_val = $config_arr[3] ?? 0;
                    // 此活动自由自动领取
                    if ($ruleData['issue_mode'] == 'auto') {
                        $state = 'auto';
                        if ($send_type == 1) {
                            //固定金额
                            $sendPrize = $basePrize;
                            $coupon_withdraw_bet += $withdraw_require_val;
                        } else if ($send_type == 2) {
                            //百分比
                            $sendPrize = $money * ($basePrize / 10000);
                            //打码量
                            $coupon_withdraw_bet += $sendPrize * $withdraw_require_val / 10000;
                        }
                        // 赠送金额
                        $coupon_money += $sendPrize;
                        
                    }

                    // 增加入款单据
                    if ($sendPrize) {
                        $model_active = [
                            'user_id'          => $user['id'],
                            'trade_id'         => $tradeId,
                            'user_name'        => $user['name'] ?? $user['user_name'],
                            'deposit_money'    => $money,
                            'coupon_money'     => $sendPrize,
                            'withdraw_require' => $sendPrize * $withdraw_require_val / 10000,
                            'active_id'        => $needPre,
                            'active_name'      => $ruleData['name'],
                            'memo'             => $ruleData['name'],
                            'status'           => 'undetermined',
                            'state'            => $state,
                            'apply_time'       => date('Y-m-d H:i:s', time()),
                        ];

                        $active_id = \DB::table('active_apply')->insertGetId($model_active);
                        array_push($activeApply, $active_id);
                    }
                }

            }
        }
        $activeApply = count($activeApply) ? implode(',', $activeApply) : '';
        return [
            'state'               => $isNew ? 'new' : '',
            'today_state'         => $todayNew ? 'new' : '',
            'slot_coupon'         => $hasSlotActiveCoupon ? 'slot_coupon' : '',
            'maya_state'          => $isMayaNew ? 'new' : '',
            'coupon_money'        => $coupon_money ?? 0,
            'withdraw_bet'        => $withdraw_bet ?? 0,
            'activeArr'           => $activeArr ?? [],
            'coupon_withdraw_bet' => $coupon_withdraw_bet,
            'activeApply'         => $activeApply,
            'rechargeActive'      => $rechargeActive ?? false
        ];
    }

    public function updUndetermined($userId, $activeId = '')
    {
        $id        = ActiveApply::where('user_id', '=', $userId)->where('status', '=', 'undetermined')->value('id');
        $issueMode = \DB::table('active_rule')->where("active_id", $activeId)->value('issue_mode');
        if ($issueMode == 'auto') {
            $status = "pass";
        } else {
            $status = "pending";
        }
        if ($id) {
            return ActiveApply::where('id', '=', $id)->update(['status' => $status]);
        }
    }


    /**
     * 活动编辑的管理员操作日志
     * @param $id
     * @param $params
     * @param $template_id
     * @return array
     */
    public function activeAdminLog($id, $params, $template_id)
    {

        if($template_id == 4) {
            // 专门处理template_id = 4的分类
            return $this->nonTemplate($id, $params, $template_id);
        }
        $fieldMeaning = [
            'begin_time'           => '有效时间（开始时间）',
            'end_time'             => '有效时间（结束时间）',
            'title'                => '优惠活动名称',
            'cover'                => '图片',
            'status'               => '状态',
            'sort'                 => '排序',
            'content'              => '优惠规则（图片）',
            'rule'                 => '赠送金额条件',
            'bind_info'            => '绑定资料',
            'issue_mode'           => '领取方式',
            'withdraw_require_val' => '提现打码量百分比',
            'send_max'             => '赠送最大金额',
            'send_type'            => '赠送金额计算方式',
            'vender_type'          => '活动充值方式',
            'content_type'         => '优惠规则展示模式',
            'condition_user_level' => '会员等级',
        ];

        $activeInfo = DB::table('active as a')
            ->leftJoin('active_rule as r', 'a.id', '=', 'r.active_id')
            ->leftJoin('active_template as template', 'a.type_id', '=', 'template.id')
            ->selectRaw('a.condition_user_level,a.id,a.title,a.status,a.vender_type,a.sort,a.content_type,a.description,a.link,r.rule,r.withdraw_require_val,r.issue_mode,r.send_type,r.send_max,r.send_bet_max,r.bind_info as bind_info,template.name,a.begin_time,a.end_time,a.cover')
            ->where('a.id', $id)
            ->first();

        $params['cover']                = replaceImageUrl($params['cover']);
        $params['description']          = mergeImageUrl($params['description']);
        $activeInfo                     = (array)$activeInfo;
        if(!$activeInfo['send_type']) {
            unset($activeInfo['send_type']);
            unset($params['send_type']);
        }
        $diffs                          = array_diff_assoc($activeInfo, $params);
        $string                         = '';
        $diffStrOld                     = '';
        $diffStrNew                     = '';

        foreach ($diffs as $diffKey => $diff) {
            if (!isset($params[$diffKey])) {
                unset($diffs[$diffKey]);
                continue;
            }
            if ($diffKey == 'send_max') {
                if($template_id == 4) {
                    continue;
                }
                $diff               = $diff / 100;
                $params['send_max'] = $params['send_max'] / 100;
                if ($diff - $params['send_max'] == 0) {
                    continue;
                }
                if ($template_id == 13) {
                    $diff               = $diff * 100;
                    $params['send_max'] = $params['send_max'] * 100;
                }
            }

            if ($diffKey == 'send_bet_max' && $diff - $params['send_bet_max'] == 0) {
                continue;
            }

            if ($diffKey == 'withdraw_require_val') {
                if($template_id == 4) {
                    continue;
                }
                $diff                           = $diff / 100 . '%';
                $params['withdraw_require_val'] = $params['withdraw_require_val'] / 100 . '%';
                if (bccomp($diff, $params['withdraw_require_val']) == 0)
                    continue;
            }

            if ($diffKey == 'bind_info' && !empty($diff)) {

                $bindsOld = explode(',', $diff);
                $bindsNew = explode(',', $params[$diffKey]);
                $bindStr  = '绑定';
                foreach ($bindsOld as $bind) {
                    $bindStr .= $this->paramsStr['bind_info'][$bind];
                }
                $diffStrOld = $bindStr;
                $bindStr    = '绑定';
                foreach ($bindsNew as $bind) {
                    $bindStr .= $this->paramsStr['bind_info'][$bind];
                }
                $diffStrNew = $bindStr;

            } elseif ($diffKey == 'rule') {
                if (in_array($template_id, [1, 5])) {
                    $diffStrOld = $diff / 100;
                    $diffStrNew = $params[$diffKey] / 100;
                }
                //幸运轮盘
                if (in_array($template_id, [6])) {
                    $oldRulesArr = json_decode($diff, true);
                    $newRulesArr = json_decode($params[$diffKey], true);
                    $oldRules    = [];
                    $newRules    = [];
                    foreach ($oldRulesArr as $v) {
                        $oldRules[$v['award_id']] = json_encode($v);
                    }

                    foreach ($newRulesArr as $val) {
                        $newRules[$val['award_id']] = json_encode($val);
                    }
                    //里面项太多了  不分了
                    $diffRules  = array_diff_assoc($oldRules, $newRules);
                    $diffStrNew = json_encode($diffRules);
                    $diffStrOld = '';
                }

                if (in_array($template_id, [4])) {
                    $diffStrOld = '';
                    $diffStrNew = '';
                }
                //首次充值300%
                if (in_array($template_id, [7, 11])) {
                    $diffStrOld = $diff;
                    $diffStrNew = $params[$diffKey];
                }

                if (in_array($template_id, [2, 3])) {
                    $oldRules      = explode(';', $diff);
                    $newRules      = explode(';', $params[$diffKey]);
                    $diffRules     = array_diff_assoc($oldRules, $newRules);
                    $oldRulesCount = count($oldRules);
                    $newRulesCount = count($newRules);

                    if ($oldRulesCount == $newRulesCount && $diffRules) {
                        foreach ($diffRules as $key => $rule) {
                            $oldRuleArr = explode(',', $rule);
                            $newRuleArr = explode(',', $newRules[$key]);
                            $diffStrOld .= $oldRuleArr[0] / 100 . '< 充值范围 <=' . $oldRuleArr[1] / 100 . '赠送' . $oldRuleArr[2] / 100 . "\n";
                            $diffStrNew .= $newRuleArr[0] / 100 . '< 充值范围 <=' . $newRuleArr[1] / 100 . '赠送' . $newRuleArr[2] / 100 . "\n";
                        }
                    }

                    if ($oldRulesCount > $newRulesCount && $diffRules) {
                        $delCount = $oldRulesCount - $newRulesCount;
                        for ($i = 1; $i <= $delCount; $i++) {
                            $delRule    = array_pop($diffRules);
                            $delRuleArr = explode(',', $delRule);
                            $string     .= $delRuleArr[0] / 100 . '< 充值范围 <=' . $delRuleArr[1] / 100 . '赠送' . $delRuleArr[2] / 100 . "已删除 \n";
                        }

                        foreach ($diffRules ?? [] as $key => $rule) {
                            $oldRuleArr = explode(',', $rule);
                            $newRuleArr = explode(',', $newRules[$key]);
                            $diffStrOld .= $oldRuleArr[0] / 100 . '< 充值范围 <=' . $oldRuleArr[1] / 100 . '赠送' . $oldRuleArr[2] / 100 . "\n";
                            $diffStrNew .= $newRuleArr[0] / 100 . '< 充值范围 <=' . $newRuleArr[1] / 100 . '赠送' . $newRuleArr[2] / 100 . "\n";
                        }
                    }

                    if ($oldRulesCount < $newRulesCount && $diffRules) {
                        $addCount     = $newRulesCount - $oldRulesCount;
                        $diffnewRules = array_diff_assoc($newRules, $oldRules);
                        for ($i = 1; $i <= $addCount; $i++) {
                            $addRule    = array_pop($diffnewRules);
                            $addRuleArr = explode(',', $addRule);
                            $string     .= "增加赠送范围" . $addRuleArr[0] / 100 . '< 充值范围 <=' . $addRuleArr[1] / 100 . '赠送' . $addRuleArr[2] / 100 . "\n";
                        }
                        foreach ($diffRules ?? [] as $key => $rule) {
                            $oldRuleArr = explode(',', $rule);
                            $newRuleArr = explode(',', $newRules[$key]);
                            $diffStrOld .= $oldRuleArr[0] / 100 . '< 充值范围 <=' . $oldRuleArr[1] / 100 . '赠送' . $oldRuleArr[2] / 100 . "\n";
                            $diffStrNew .= $newRuleArr[0] / 100 . '< 充值范围 <=' . $newRuleArr[1] / 100 . '赠送' . $newRuleArr[2] / 100 . "\n";
                        }
                    }
                }

                if (in_array($template_id, [8, 9])) {
                    $oldRulesArr = json_decode($diff, true);
                    $newRulesArr = json_decode($params[$diffKey], true);
                    if (!empty($oldRulesArr)) {
                        foreach ($oldRulesArr as $key => $value) {
                            if ($value['game_menu_id'] != $newRulesArr[$key]['game_menu_id']) {
                                $diffStrOld .= '指定分类:' . $value['game_menu_id'];
                                $diffStrNew .= '指定分类:' . $newRulesArr[$key]['game_menu_id'];
                            }
                            if ($value['type'] != $newRulesArr[$key]['type']) {
                                $diffStrOld .= ' 回水方式1:' . $value['type'];
                                $diffStrNew .= ' 回水方式1:' . $newRulesArr[$key]['type'];
                            }
                            if ($value['data']['status'] != $newRulesArr[$key]['data']['status']) {
                                $diffStrOld .= ' 回水方式2:' . $value['data']['status'];
                                $diffStrNew .= ' 回水方式2:' . $newRulesArr[$key]['data']['status'];
                            }
                            if ($value['data']['value'] != $newRulesArr[$key]['data']['value']) {
                                $diffStrOld .= ' 规则值:' . json_encode($value['data']['value']);
                                $diffStrNew .= ' 规则值:' . json_encode($newRulesArr[$key]['data']['value']);
                            }
                        }
                    }
                }
                if (in_array($template_id, [10])) {
                    $oldRulesArr = json_decode($diff, true);
                    $newRulesArr = json_decode($params[$diffKey], true);
                    if ($oldRulesArr['game_menu_name'] != $newRulesArr['game_menu_name']) {
                        $diffStrOld .= '指定分类:' . $oldRulesArr['game_menu_name'];
                        $diffStrNew .= '指定分类:' . $newRulesArr['game_menu_name'];
                    }
                    if ($oldRulesArr['rule'] != $newRulesArr['rule']) {
                        $diffStrOld .= ' 规则值:' . $oldRulesArr['rule'];
                        $diffStrNew .= ' 规则值:' . $newRulesArr['rule'];
                    }
                }
                if (in_array($template_id, [13])) {
                    $oldRulesArr = json_decode($diff, true);
                    $newRulesArr = json_decode($params[$diffKey], true);
                    if ($oldRulesArr['game_menu_name'] != $newRulesArr['game_menu_name']) {
                        $diffStrOld .= '指定分类:' . $oldRulesArr['game_menu_name'];
                        $diffStrNew .= '指定分类:' . $newRulesArr['game_menu_name'];
                    }
                    if ($oldRulesArr['send_prize'] != $newRulesArr['send_prize']) {
                        $diffStrOld .= '赠送彩金:' . $oldRulesArr['send_prize'];
                        $diffStrNew .= '赠送彩金:' . $newRulesArr['send_prize'];
                    }
                    if ($oldRulesArr['send_bet'] != $newRulesArr['send_bet']) {
                        $diffStrOld .= '赠送打码量:' . $oldRulesArr['send_bet'];
                        $diffStrNew .= '赠送打码量:' . $newRulesArr['send_bet'];
                    }
                    if ($oldRulesArr['rule']['recharge'] != $newRulesArr['rule']['recharge']) {
                        $diffStrOld .= '充值范围:' . $oldRulesArr['rule']['recharge'];
                        $diffStrNew .= '充值范围:' . $newRulesArr['rule']['recharge'];
                    }
                    if ($oldRulesArr['rule']['bet_amount'] != $newRulesArr['rule']['bet_amount']) {
                        $diffStrOld .= '流水范围:' . $oldRulesArr['rule']['bet_amount'];
                        $diffStrNew .= '流水范围:' . $newRulesArr['rule']['bet_amount'];
                    }
                }
            } else {
                if (isset($this->paramsStr[$diffKey]) && $this->paramsStr[$diffKey]) {
                    if (isset($this->paramsStr[$diffKey][$diff])) {
                        $diffStrOld = $this->paramsStr[$diffKey][$diff];
                        $diffStrNew = $this->paramsStr[$diffKey][$params[$diffKey]];
                    }
                } else {
                    $diffStrOld = $diff;
                    $diffStrNew = $params[$diffKey];
                }
            }

            switch ($diffKey) {
                case 'description' :
                    $string .= '更改了-优惠规则（文字描述）'. "\n";
                    $diffStrNew = '';
                    $diffStrOld = '';
                    break;
                case 'condition_user_level' :
                    if($diffStrOld) {

                        $user_level = DB::table('user_level')->whereIn('level',explode(',',$diffStrOld))->select(['name'])->get()->toArray();

                        $diffStrOld = implode(',',array_column($user_level,'name'));

                    }

                    if($diffStrNew) {

                        $user_level = DB::table('user_level')->whereIn('level',explode(',',$diffStrNew))->select(['name'])->get()->toArray();

                        $diffStrNew = implode(',',array_column($user_level,'name'));

                    }
                    $string .= $fieldMeaning[$diffKey] . ': ' . $diffStrOld . '改为: ' . $diffStrNew . "\n";
                    break;
                default:
                    $string .= $fieldMeaning[$diffKey] . ': ' . $diffStrOld . '改为: ' . $diffStrNew . "\n";
            }

        }
        $str = [
            'str'  => $string,
            'name' => $activeInfo['name']
        ];
        return $str;
    }

    public function nonTemplate($id, $params, $template_id)
    {

        $fieldMeaning = [
            'id'                   => '自增id',
            'begin_time'           => '有效时间（开始时间）',
            'end_time'             => '有效时间（结束时间）',
            'title'                => '优惠活动名称',
            'condition_recharge'   => '申请条件-是否有充值记录',
            'send_times'           => '活动赠送次数',
            'apply_times'          => '可发起申请次数',
            'blacklist_url'        => '禁止参与的用户的文件地址',
            'state'                => '赠送方式',
            'content'              => '活动简介',
            'cover'                => '图片',
            'status'               => '状态',
            'sort'                 => '排序',
            'type_id'              => '活动类型',
            'link'                 => '跳转链接',
            'vender_type'          => '活动充值方式',
            'content_type'         => '优惠规则展示模式',
            'condition_user_level' => '会员等级',
        ];

        $activeInfo = DB::table('active as a')
            ->leftJoin('active_template as template', 'a.type_id', '=', 'template.id')
            ->selectRaw('a.active_type_id as type_id,a.content,a.condition_recharge,a.send_times,a.apply_times,a.blacklist_url,a.state,a.condition_user_level,a.id,a.title,a.status,a.vender_type,a.sort,a.content_type,a.description,a.link,template.name,a.begin_time,a.end_time,a.cover')
            ->where('a.id', $id)
            ->first();

        $params['cover']                = replaceImageUrl($params['cover']);
        $params['description']          = mergeImageUrl($params['description']);
        $activeInfo                     = (array)$activeInfo;
        $diffs                          = array_diff_assoc($activeInfo, $params);
        $string                         = '';
        $diffStrOld                     = '';
        $diffStrNew                     = '';

        foreach ($diffs as $diffKey => $diff) {
            if (!isset($params[$diffKey])) {
                unset($diffs[$diffKey]);
                continue;
            }

            if (isset($this->paramsStr[$diffKey]) && $this->paramsStr[$diffKey]) {
                if (isset($this->paramsStr[$diffKey][$diff])) {
                    $diffStrOld = $this->paramsStr[$diffKey][$diff];
                    $diffStrNew = $this->paramsStr[$diffKey][$params[$diffKey]];
                }
            } else {
                $diffStrOld = $diff;
                $diffStrNew = $params[$diffKey];
            }

            //格式化提示
            switch ($diffKey) {
                case 'description' :
                    $string .= '更改了-优惠规则（文字描述）'. "\n";
                    $diffStrNew = '';
                    $diffStrOld = '';
                    break;
                case 'condition_user_level' :
                    if($diffStrOld) {
                        $user_level = DB::table('user_level')->whereIn('level',explode(',',$diffStrOld))->select(['name'])->get()->toArray();
                        $diffStrOld = implode(',',array_column($user_level,'name'));
                    }

                    if($diffStrNew) {
                        $user_level = DB::table('user_level')->whereIn('level',explode(',',$diffStrNew))->select(['name'])->get()->toArray();
                        $diffStrNew = implode(',',array_column($user_level,'name'));
                    }
                    $string .= $fieldMeaning[$diffKey] . ': ' . $diffStrOld . ' 改为: ' . $diffStrNew . " \n";
                    break;
                case 'type_id' :
                    if($diffStrOld) {
                        $active_type = DB::table('active_type')->where('id',$diffStrOld)->select(['name'])->get()->toArray();
                        $diffStrOld = implode(',',array_column($active_type,'name'));
                    }

                    if($diffStrNew) {
                        $active_type = DB::table('active_type')->where('id',$diffStrNew)->select(['name'])->get()->toArray();
                        $diffStrNew = implode(',',array_column($active_type,'name'));
                    }
                    $string .= $fieldMeaning[$diffKey] . ': ' . $diffStrOld . ' 改为: ' . $diffStrNew . " \n";
                    break;

                default:
                    $string .= $fieldMeaning[$diffKey] . ': ' . $diffStrOld . ' 改为: ' . $diffStrNew . " \n";
            }

        }
        $str = [
            'str'  => $string,
            'name' => $activeInfo['name']
        ];
        return $str;
    }
    /**
     * 后台活动管理的幸运转盘的参数校验
     * @param $limit_times
     * @param $prize_value
     * @return mixed
     */
    public function lucky($limit_times, $prize_value)
    {

        //非正整数验证
        if ($limit_times < 0 || !is_numeric($limit_times) || !is_int($limit_times)) {
            return $this->lang->set(10813, [$this->lang->text('Number of free lottery')]);
        }

        //奖项金额非正整数验证
        $rule_value = json_decode($prize_value, true) ?? [];

        $code = 0;
        foreach ($rule_value as $key => $item) {
            $money = (int)$item['award_money'];

            $code += (int)$item['award_code'];

            if ($money < 0 && !is_int($money)) {
                return $this->lang->set(10813, [$item['award_name'] . $this->lang->text('Award amount')]);
            }

            foreach ($item['user_list'] as $item2) {
                $this->redis->hset(\Logic\Define\CacheKey::$perfix['lockyCountUserList'] . $item['award_id'], $item2['id'], (int)$item2['times']);
            }
        }

        if ($code <= 0) {
            return $this->lang->set(10814);
        }
    }


    public function slot($rule)
    {
        $rule_value = explode('|', $rule);
        if (empty($rule_value) || count($rule_value) < 3) {
            throw new \Exception('规则不能为空，或者规则不能小于3条');
        }

        foreach ($rule_value as $k => $v) {
            $value = explode(',', $v);
            $i     = $k + 1;
            if (empty($value) || count($value) < 3) {
                if ($i < 4) {
                    throw new \Exception("第{$i}条规则不合法");
                }
            }
            foreach ($value as $key => $item) {
                $new_key = $key + 1;
                if (!is_numeric($item) || strpos($item, '.') !== false || $item < 0) {
                    throw new \Exception("第{$i}条规则,第{$new_key}个参数必须为整数，且不能小于0");
                }
            }
        }
    }

    /**
     * 电子活动参与状态
     * @param $uid
     * @return int
     */
    public static function canGetSlotActiveCoupon($uid)
    {
        $active_id = self::getSlotActiveId();
        //活动不存在
        if (!$active_id) {
            return 0;
        }
        $info = ActiveSignUp::getInfo($uid, $active_id);
        //没有参与 | 或者参加了  但是失效了
        if (empty($info) || $info['apply_status'] == 0) {
            return 0;
        }

        $id    = $info['id'];
        $times = $info['times'];
        $now   = time();
        $end   = date('Y-m-d H:i:s', $now);

        if ($times == 0) {
            //超过24小时 设为不参与
            if ($now - strtotime($info['apply_time']) > 86400) {
                ActiveSignUp::updateInfo($id, ['apply_status' => 0]);
                return 0;
            }
            $start = $info['apply_time'];

            $deposit = self::getDepositId($uid, $start, $end);
            //以防万一 怕times出错  谨慎判断
            if ($deposit) {
                return 0;
            }
            return 1;

        } elseif ($times == 1) {
            $first_join_time = strtotime($info['first_deposit_time']);
            $start           = date('Y-m-d H:i:s', $first_join_time + 86400);

            //小于24小时 不发优惠
            if ($now - $first_join_time < 86400) {
                return 0;
            }
            //超过48小时 不发优惠 设为不参与
            if ($now - $first_join_time > 172800) {
                ActiveSignUp::updateInfo($id, ['apply_status' => 0]);
                return 0;
            }

            $deposit = self::getDepositId($uid, $start, $end);
            //已经存在就不发放优惠  其实如果存在 那就不该进这里 而是进$times = 2
            if ($deposit) {
                return 0;
            }
            return 1;

        } elseif ($times == 2) {
            $second_join_time = strtotime($info['second_deposit_time']);
            $start            = date('Y-m-d H:i:s', $second_join_time + 86400);

            //小于24小时 不发优惠
            if ($now - $second_join_time < 86400) {
                return 0;
            }
            //超过48小时 不发优惠 设为不参与
            if ($now - $second_join_time > 172800) {
                ActiveSignUp::updateInfo($id, ['apply_status' => 0]);
                return 0;
            }

            $deposit = self::getDepositId($uid, $start, $end);
            //已经存在就不发放优惠  其实如果存在 那就不该进这里 而是进$times = 2
            if ($deposit) {
                return 0;
            }
            return 1;
        }

        return 0;
    }

    /**
     * 获取充值id
     * @param $uid
     * @param $startTime
     * @param $endTime
     * @return mixed
     */
    public static function getDepositId($uid, $startTime, $endTime)
    {
        $deposit = FundsDeposit::where('user_id', '=', $uid)->where('status', 'paid')->where('money', '>', 0)
            ->whereBetween('created', [$startTime, $endTime])
            ->orderBy('id', 'asc')
            ->get(['id'])
            ->toArray();

        return $deposit;
    }

    /**
     * 判断是否能参与电子活动
     */
    public static function canJoinSlotActive($uid, $activeId)
    {
        //已充值过的用户不能参与
        $deposit = FundsDeposit::whereRaw("FIND_IN_SET('paid',status)")->where('money', '>', 0)->where('user_id', '=', $uid)->first();
        if ($deposit) throw new \Exception("You've been deposited");

        //已经参与的用户
        $info = ActiveSignUp::getInfo($uid, $activeId);
        if ($info) {
            throw new \Exception("You've been involved");
        }
        return true;
    }

    /**
     * 参与电子活动
     * @param $uid
     * @throws \Exception
     */
    public static function joinSlotActive($uid)
    {
        $active_id = self::getSlotActiveId();
        if (!$active_id) throw new \Exception('Activity does not exist');
        self::canJoinSlotActive($uid, $active_id);

        $res = ActiveSignUp::joinActive($uid, $active_id);
        if (!$res) throw new \Exception('error');
    }

    /**
     * 获取当前开启的电子活动id
     * @return mixed
     */
    public static function getSlotActiveId()
    {
        $date  = date('Y-m-d H:i:s');
        $where = [
            ['status', '=', 'enabled'],
            ['type_id', '=', 7],
            ['begin_time', '<=', $date],
            ['end_time', '>=', $date]
        ];
        return Active::where($where)->value('id');
    }

    /**
     * 更新电子优惠参与记录
     * @param $userId
     * @param $activeId
     * @param $depositTime
     * @return bool
     */
    public static function updateActiveSignUp($userId, $activeId, $depositTime)
    {
        $info = ActiveSignUp::getInfo($userId, $activeId);
        if (!$info) return false;
        return ActiveSignUp::updateTimesAndDepositTime($info['id'], $info['times'], $depositTime);
    }

    /**
     * 判断用户能否玩所有游戏
     * @param $userId
     * @return int
     */
    public static function canPlayAllGame($userId, $update = false)
    {
        $active_id = self::getSlotActiveId();
        //活动不存在
        if (!$active_id) {
            return 1;
        }
        $info = ActiveSignUp::getInfo($userId, $active_id);
        //没有参与 | 或者参加了  但是失效了 | 或者可以玩所有游戏
        if (empty($info) || $info['apply_status'] == 0 || $info['can_play_all_game'] == 1) {
            return 1;
        }

        if ($update) {
            //改为可以玩所有游戏
            $data['can_play_all_game'] = 1;
            //已经领过3次优惠了
            if ($info['times'] >= 3) {
                $data['apply_status'] = 0;
            }
            ActiveSignUp::updateInfo($info['id'], $data);
        }
        return 0;
    }

    /**
     * APP首次登录活动
     * @param $uid
     * @throws \Exception
     */
    public function sendAppLogin($uid, $uuid, $origin)
    {
        //检查活动是否进行中
        $date        = date('Y-m-d H:i:s');
        $where       = [
            ['status', '=', 'enabled'],
            ['type_id', '=', 14],
            ['begin_time', '<=', $date],
            ['end_time', '>=', $date]
        ];
        $active_info = Active::where($where)->orderBy('id', 'desc')->first();
        if (empty($active_info)) {
            return false;
        }
        $rule = \Model\ActiveRule::where('active_id', $active_info->id)->first()->toArray();
        if (empty($rule)) {
            return false;
        }
        $money = $rule['rule'];
        //打码量是百分比
        $bet = bcdiv($rule['withdraw_require_val'], 10000, 2);
        $bet = bcmul($money, $bet);

        //检查是否已经赠送
        $award_count = DB::table("active_award")->where('user_id', $uid)->orWhere('uuid', $uuid)->count();
        if ($award_count > 0) {
            return false;
        }

        //符合条件 开始赠送
        $date   = date('Y-m-d H:i:s', time());
        $wallet = new Wallet($this->ci);
        $user   = \Model\User::where('id', $uid)->select(['wallet_id', 'id', 'name'])->first();
        try {
            $this->db->getConnection()->beginTransaction();

            // 锁定钱包
            \Model\Funds::where('id', $user['wallet_id'])->lockForUpdate()->first();
            $rand        = random_int(10000, 99999);
            $orderNumber = date('Ymdhis') . str_pad(mt_rand(1, $rand), 4, '0', 0);

            $memo = "APP首次登录活动,设备ID:" . $uuid;
            $wallet->addMoney($user, $orderNumber, $money, 105, $memo, $bet, true);
            \Model\UserData::where('user_id', $uid)->increment('rebet_amount', $money);

            $model_active = [
                'user_id'          => $uid,
                'user_name'        => $user['name'],
                'active_id'        => $active_info->id,
                'active_name'      => $active_info->name,
                'status'           => 'pass',
                'state'            => $rule['issue_mode'],
                'memo'             => '设备ID:' . $uuid,
                'coupon_money'     => $money,
                'withdraw_require' => $bet,
                'apply_time'       => date('Y-m-d H:i:s'),
            ];
            \DB::table('active_apply')->insert($model_active);

            $model_award = [
                'user_id'   => $uid,
                'uuid'      => $uuid,
                'origin'    => $origin,
                'active_id' => $active_info->id,
            ];
            \DB::table('active_award')->insert($model_award);

            $this->db->getConnection()->commit();
            $this->logger->info('首次登录APP赠送活动' . $active_info->id . ':用户' . $user['name'] . '派送金额:' . ($money / 100) . '发放完毕');
        } catch (\Exception $e) {
            $this->logger->error('首次登录APP赠送活动发放：异常，active_id：' . $active_info->id . "userId: {$uid}" . "  " . $date . "\r\n" . $e->getMessage());
            $this->db->getConnection()->rollBack();
        }

        return true;
    }

    // 查询app充值活动
    public function getAppTopUpGiftStauts()
    {
        return \DB::table('active')->where('type_id', 18)->where('status', 'enabled')->first(['id']);
    }
}