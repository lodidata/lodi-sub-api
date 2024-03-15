<?php

namespace Logic\Recharge\Traits;

use DB;
use Logic\Admin\Message;
use Logic\GameApi\Common;
use Logic\Lottery\Rebet;
use Logic\Wallet\Wallet;
use Model\FundsDealLog;
use Model\FundsDealManual;
use Model\FundsDeposit;
use Model\User;
use Logic\Admin\Log;

trait RechargeAdminOptMoney {
    /**
     * 手动存款（厅主后台）
     *
     * @param int $userId 存入用户id
     * @param int $amount 存入金额
     * @param int $receiveBankAccountId 存入银行
     * @param string $memo 备注
     * @param bool $pass 是否直接成功
     * @param string $ip ip地址
     * @param int $currentUserId 当前用户id
     *
     * @return bool 成功与否
     */
    public function handPassDeposit(
        int $userId,
        int $amount,
        $receiveBankAccountId,
        string $memo,
        bool $pass,
        string $ip,
        int $currentUserId,
        int $play_code = 0,  //打码量
        int $send = 0,  //优惠金额,
        string $applyAdmin = ''
    ) {
        // 获取收款银行信息
        if ($receiveBankAccountId) {

            // 获取收款银行信息
            $receiveBankAccount = \DB::table('bank_account')
                                     ->join('bank', 'bank_account.bank_id', '=', 'bank.id')
                                     ->where('bank_account.id', '=', $receiveBankAccountId)
                                     ->first([
                                         'bank_account.bank_id', 'bank_account.name', 'bank_account.card', 'bank_account.address',
                                         \DB::raw('bank.code AS bank_code'),
                                     ]);
            $receiveBankAccount['bank_name'] = $this->lang->text($receiveBankAccount['bank_code']);
            $receiveBankAccount && $receiveBankInfo = json_encode([
                'bank'        => $receiveBankAccount->address,
                'accountname' => $receiveBankAccount->name,
                'card'        => RSAEncrypt($receiveBankAccount->card),
                'bank_code'   => $receiveBankAccount->bank_code
            ], JSON_UNESCAPED_UNICODE);
        }

        if (!isset($receiveBankInfo)) {
            $receiveBankInfo = json_encode([
                'bank'        => '',
                'accountname' => '',
                'card'        => '',
                'bank_code'   => ''
            ], JSON_UNESCAPED_UNICODE);
        }

        $user = (new Common($this->ci))->getUserInfo($userId);
        $state = 'tz';
        // 是否首存
        $isNew = !FundsDeposit::whereRaw("FIND_IN_SET('paid',status)")->where('money','>',0)->where('user_id', '=', $userId)->first();
        if($isNew and $amount > 0){
            $state = 'new,tz';
            //修改用户  首充时间
            \Model\User::where('id',$userId)->whereRaw('first_recharge_time is NULL')
                ->update(['first_recharge_time'=>date('Y-m-d H:i:s')]);
        }

        $tradeNo = date('YmdHis') . rand(pow(10, 3), pow(10, 4) - 1);
        $time = time();
        $model = [
            'trade_no'                => $tradeNo,
            'user_id'                 => $userId,
            'money'                   => $amount,
            'coupon_money'            => $send,
            'withdraw_bet'            => $play_code,
            'name'                    => $user['name'],
            'recharge_time'           => date('Y-m-d H:i:s'),
            'deposit_type'            => 1,
            'receive_bank_account_id' => $receiveBankAccountId,
            'ip'                      => $ip,
            'memo'                    => $memo,
            'status'                  => $pass ? 'paid' : 'pending',
            'state'                   => $state,
            'receive_bank_info'       => $receiveBankInfo,
            'created'                 => date('Y-m-d H:i:s', $time),
        ];

        // fixme 增加入款校验码
        $model['marks'] = 0;

        // 代申请还是直接成功
        if ($pass) {
            $model['updated_uid'] = $currentUserId;
            $model['process_uid'] = $currentUserId;
            $model['process_time'] = date('Y-m-d H:i:s');
            $model['recharge_time'] = date('Y-m-d H:i:s');
            $valid_bet = 0;
            $model['valid_bet'] = $valid_bet;

            // $frontMoney = \Model\Funds::find($user['wallet_id'])->value('balance');
            // 存款
            try {
                $this->db->getConnection()
                         ->beginTransaction();

                //添加打码量可提余额等信息
                $dml = new \Logic\Wallet\Dml($this->ci);
                $dmlData = $dml->getUserDmlData($userId, (int)$play_code, 2);

                // 锁定钱包
                $oldFunds = \Model\Funds::where('id', $user['wallet_id'])
                                        ->lockForUpdate()
                                        ->first();
                (new Wallet($this->ci))->crease($user['wallet_id'], $amount + $send);
                $balance = \DB::table('funds')
                             ->where('id', '=', $user['wallet_id'])
                             ->value('balance');

                //添加存款总笔数，总金额
                \Model\UserData::where('user_id',$userId)->increment('deposit_amount',$amount,['deposit_num'=>\DB::raw('deposit_num + 1')]);

                if (isset($GLOBALS['playLoad'])) {
                    $admin_id = $GLOBALS['playLoad']['uid'];
                    $admin_name = $GLOBALS['playLoad']['nick'];
                } else {
                    $admin_id = 0;
                    $admin_name = '';
                }

                //存款
                FundsDeposit::create($model);

                $dealData = ([
                    'user_id' => $userId,
                    'user_type' => 1,
                    'username' => $user['name'],
                    'order_number' => $model['trade_no'],
                    'deal_type' => FundsDealLog::TYPE_INCOME_MANUAL,
                    'deal_category' => FundsDealLog::CATEGORY_INCOME,
                    'deal_money' => $amount,
                    'coupon_money' => 0,
                    'balance' => $balance,
                    'memo' => $memo ? $memo : $this->lang->text("The amount of manual deposit in the main and back office"),
                    'wallet_type' => 1,
                    'total_bet' => $dmlData->total_bet,
                    'withdraw_bet' => $play_code,
                    'total_require_bet' => $dmlData->total_require_bet,
                    'free_money' => $dmlData->free_money,
                    'admin_id' => $admin_id,
                    'admin_user' => $admin_name,
                ]);
                FundsDealLog::create($dealData);
                if ($send) {
                    $dealData = ([
                        'user_id'           => $userId,
                        'user_type'         => 1,
                        'username'          => $user['name'],
                        'order_number'      => $model['trade_no'],
                        'deal_type'         => 105,
                        'deal_category'     => FundsDealLog::CATEGORY_INCOME,
                        'deal_money'        => $send,
                        'coupon_money'      => 0,
                        'balance'           => $balance,
                        'memo'              => $memo ? $memo : $this->lang->text("The amount of manual deposit in the main and back office"),
                        'wallet_type'       => 1,
                        'total_bet'         => $dmlData->total_bet,
                        'withdraw_bet'      => 0,
                        'total_require_bet' => $dmlData->total_require_bet,
                        'free_money'        => $dmlData->free_money,
                    ]);
                    FundsDealLog::create($dealData);
                }
                if($amount || $play_code) {
                    // 增加手动入款记录
                    $dealManual = ([
                        'user_id' => $userId,
                        'username' => $user['name'],
                        'user_type' => 1,
                        'type' => 1,
                        'trade_no' => $model['trade_no'],
                        'operator_type' => 1,
                        'front_money' => $oldFunds['balance'],
                        'money' => $amount,
                        'balance' => $oldFunds['balance'] + $amount,
                        'withdraw_bet' => $play_code,
                        'admin_uid' => $currentUserId,
                        'ip' => $ip,
                        'wallet_type' => 1,
                        'sub_type' => $pass ? 1 : 2,
                        'memo' => $memo ? $memo : $this->lang->text("Main and back office manual deposit"),
                        'created' => time(),
                        'updated' => time(),
                    ]);
                    $applyAdmin && $dealManual['memo'] .= '--'.$applyAdmin;
                    FundsDealManual::create($dealManual);
                }
                // 增加赠送活动手动入款记录
                if($send > 0) {
                    $dealManual = ([
                        'user_id' => $userId,
                        'username' => $user['name'],
                        'user_type' => 1,
                        'type' => 3,
                        'pay_type' => 0,
                        'trade_no' => $model['trade_no'],
                        'operator_type' => 1,
                        'front_money' => $oldFunds['balance'] + $amount,
                        'money' => $send,
                        'balance' => $balance,
                        'withdraw_bet' => 0,
                        'admin_uid' => $currentUserId,
                        'ip' => $ip,
                        'wallet_type' => 1,
                        'sub_type' => $pass ? 1 : 2,
                        'memo' => $memo ? $memo : $this->lang->text("The amount of manual deposit in the main and back office"),
                        'created' => time(),
                        'updated' => time(),
                    ]);
                    FundsDealManual::create($dealManual);
                }
                $message = new Message($this->ci);
                $content  = ["Dear %s, Hello! You have successfully charged %s yuan", $user['name'], ($amount / 100)];
                $insertId = $this->messageAddByMan("Recharge to account", $user['name'], $content);
                $message->messagePublish($insertId);
                if ($send > 0) {
                    $content  = ["Dear %s, Hello! %s yuan has arrived", $user['name'], ($send / 100)];
                    $insertId = $this->messageAddByMan("Recharge gift", $user['name'], $content);
                    $message->messagePublish($insertId);
                }
                $this->db->getConnection()
                         ->commit();

                return true;
            } catch (\Exception $e) {
                $this->db->getConnection()
                         ->rollback();
                var_dump($e->getCode(),$e->getMessage());die;
                return false;
            }
        } else {
            return $this->module->funds->addDeposit($model);
        }
    }


    /**
     * 手动扣款方法
     *
     * @param int $userId 用户ID
     * @param int $amount 金额
     * @param string $memo 备注
     * @param string $ip 操作者IP
     * @param int $currentUserId 当前操作者ID
     * @param int $userType
     * @param bool $freeMoney 是否更新可提余额
     * @param int $dealType 流水交易类型
     *
     * @return bool
     */
    public function tzHandDecrease(
        int $userId,
        int $amount,
        string $memo,
        string $ip,
        int $currentUserId,
        int $userType = 1,
        bool $freeMoney = false,
        $dealType = FundsDealLog::TYPE_REDUCE_MANUAL
    ) {
        $user = (new Common($this->ci))->getUserInfo($userId);
        if (!$user) {
            return false;
        }

        try {
            $this->db->getConnection()
                     ->beginTransaction();

            // 锁定钱包
            $oldFunds = \Model\Funds::where('id', $user['wallet_id'])
                                    ->lockForUpdate()
                                    ->first()
                                    ->toArray();

            // 判断钱包余额是否足够
            /*if ($amount > $oldFunds['balance']) {
                $this->db->getConnection()
                         ->rollback();

                return false;
            }*/
            if ($amount > $oldFunds['balance']) {
                $amount = $oldFunds['balance'];
           }

            if (!$memo) {
                $memo = $dealType == FundsDealLog::TYPE_REDUCE_MANUAL ? $this->lang->text('Manually reduce the balance in the main and back office') : $this->lang->text('Manual deduction in main and back office');
            }

            (new Wallet($this->ci))->crease($user['wallet_id'], -$amount);
            //写入user_data数据中心
            \Model\UserData::where('user_id',$userId)->increment('withdraw_amount',$amount,['withdraw_num'=>\DB::raw('withdraw_num + 1')]);

            $after_balance = \DB::table('funds')
                                ->where('id', '=', $user['wallet_id'])
                                ->value('balance');

            if ($freeMoney) {
                $user_free_money = \DB::table('user_data')
                                      ->where('user_id', '=', $userId)
                                      ->value('free_money');

                $last_free_money = $user_free_money - $amount > 0 ? $user_free_money - $amount : 0;

                User::updateBetData($userId, ['free_money'=>$last_free_money]);
            }

            //流水里面添加打码量可提余额等信息
            $dml = new \Logic\Wallet\Dml($this->ci);
            $dmlData = $dml->getUserDmlData($userId);
            $freeMoney = $dmlData->free_money;

            //手动出款需要更新可提余额
            if ($dealType == FundsDealLog::TYPE_WITHDRAW_MANUAL) {
                $freeMoney = $freeMoney - $amount;
            }

            //可提余额不为负数
            if ($freeMoney < 0) {
                $freeMoney = 0;
            }

            //可提余额不能超过钱包余额
            if ($freeMoney > $after_balance) {
                $freeMoney = $after_balance;
            }

            if (isset($GLOBALS['playLoad'])) {
                $admin_id = $GLOBALS['playLoad']['uid'];
                $admin_name = $GLOBALS['playLoad']['nick'];
            } else {
                $admin_id = 0;
                $admin_name = '';
            }

            /**
             * 该地方主要注意
             * 出入款报表不统计 deal_type == FundsDealLog::TYPE_REDUCE_MANUAL 的现金流水记录
             */

            $dealLogMemo = $dealType == FundsDealLog::TYPE_REDUCE_MANUAL ? $this->lang->text('Manually reduce the balance in the main and back office') : $this->lang->text('Manual deduction in main and back office');
            $dealData = ([
                'user_id'           => $userId,
                'user_type'         => 1,
                'username'          => $user['name'],
                'order_number'      => date('YmdHis') . rand(pow(10, 3), pow(10, 4) - 1),
                'deal_type'         => $dealType,
                'deal_category'     => FundsDealLog::CATEGORY_COST,
                'deal_money'        => $amount,
                'balance'           => $after_balance,
                'memo'              => $dealLogMemo,
                'wallet_type'       => 1,
                'total_bet'         => $dmlData->total_bet,
                'withdraw_bet'      => 0,
                'total_require_bet' => $dmlData->total_require_bet,
                'free_money'        => $freeMoney,
                'admin_id'          => $admin_id,
                'admin_user'        => $admin_name,
            ]);
            FundsDealLog::create($dealData);  //增加现金流水记录

            $dealManual = ([
                'user_id'       => $userId,
                'username'      => $user['name'],
                'user_type'     => $userType,
                'type'          => $dealType == FundsDealLog::TYPE_REDUCE_MANUAL ? 4 : 2,
                'operator_type' => 1,
                'front_money'   => $oldFunds['balance'],
                'money'         => $amount,
                'balance'       => $after_balance,
                'admin_uid'     => $currentUserId,
                'ip'            => $ip,
                'wallet_type'   => 1,
                'memo'          => $memo,
                'created'       => time(),
                'updated'       => time(),
            ]);
            FundsDealManual::create($dealManual);  //增加资金流水

            $this->db->getConnection()
                     ->commit();

            return true;
        } catch (\Exception $e) {
            var_dump($e);die;
            $this->db->getConnection()
                     ->rollback();

            return false;
        }
    }

    /**
     * 手动发放优惠
     *
     * @param int $userId 存入用户id
     * @param string $ip ip地址
     * @return bool 成功与否
     */
    public function handSendCoupon(
        int $userId,
        int $play_code,  //打码量
        int $send,  //优惠金额
        string $memo,
        string $ip,
        int $currentUserId=0,
        int $admin_id=0,
         $admin_user=''
    ) {
        $user = (new Common($this->ci))->getUserInfo($userId);
        if(!$user) return;
        $tradeNo = date('YmdHis') . rand(pow(10, 3), pow(10, 4) - 1);
        try {
            //添加打码量可提余额等信息
            $dml = new \Logic\Wallet\Dml($this->ci);
            $dmlData = $dml->getUserDmlData($userId, (int)$play_code, 2);

            (new Wallet($this->ci))->crease($user['wallet_id'], $send);
            $balance = \DB::table('funds')
                ->where('id', '=', $user['wallet_id'])
                ->value('balance');

            if ($send) {
                $dealData = ([
                    'user_id'           => $userId,
                    'user_type'         => 1,
                    'username'          => $user['name'],
                    'order_number'      => $tradeNo,
                    'deal_type'         => 105,
                    'deal_category'     => FundsDealLog::CATEGORY_INCOME,
                    'deal_money'        => $send,
                    'coupon_money'      => 0,
                    'balance'           => $balance,
                    'memo'              => $memo ?? $this->lang->text("Activity gift"),
                    'wallet_type'       => 1,
                    'total_bet'         => $dmlData->total_bet,
                    'withdraw_bet'      => 0,
                    'total_require_bet' => $dmlData->total_require_bet,
                    'free_money'        => $dmlData->free_money,
                    'admin_id'          => $admin_id,
                    'admin_user'        => $admin_user
                ]);
                FundsDealLog::create($dealData);
            }

            // 增加赠送活动手动入款记录
            /*if($send > 0) {
                $dealManual = ([
                    'user_id' => $userId,
                    'username' => $user['name'],
                    'user_type' => 1,
                    'type' => 3,
                    'pay_type' => 0,
                    'trade_no' => $tradeNo,
                    'operator_type' => 1,
                    'front_money' => $oldFunds['balance'],
                    'money' => $send,
                    'balance' => $balance,
                    'withdraw_bet' => 0,
                    'admin_uid' => $currentUserId,
                    'ip' => $ip,
                    'wallet_type' => 1,
                    'sub_type' => 1,
                    'memo' => $memo ? $memo : $this->lang->text("Activity gift"),
                    'created' => time(),
                    'updated' => time(),
                ]);
                FundsDealManual::create($dealManual);
            }*/


            /*if ($send > 0) {
               $message = new Message($this->ci);
                $content  = ["Dear %s, Hello! %s yuan has arrived", $user['name'], ($send / 100)];
                $insertId = $this->messageAddByMan("Activity gift", $user['name'], $content);
                $message->messagePublish($insertId);
            }*/

            if ($play_code > 0) {
                //添加打码量到dml表
                $dml = new  \Model\Dml();
                $dml->addDml($userId, $play_code, $send, '活动赠送优惠添加打码量');
            }

            return true;
        } catch (\Exception $e) {

            $this->error->log('领取彩金错误:'.$e->getMessage());
            return false;
        }
    }

    /**
     * 内充奖励优惠券
     *
     * @param int $userId 存入用户id
     * @param string $ip ip地址
     * @return bool 成功与否
     */
    public function innerPaySendCoupon(
        int $userId,
        int $play_code,  //打码量
        int $send,  //优惠金额
        string $memo,
        string $ip,
        int $currentUserId=0,
        int $admin_id=0,
        $admin_user=''
    ) {
        $user = (new Common($this->ci))->getUserInfo($userId);
        if(!$user) return;
        $tradeNo = date('YmdHis') . rand(pow(10, 3), pow(10, 4) - 1);
        try {
            //添加打码量可提余额等信息
            $dml = new \Logic\Wallet\Dml($this->ci);
            $dmlData = $dml->getUserDmlData($userId, (int)$play_code, 2);

            (new Wallet($this->ci))->crease($user['wallet_id'], $send);
            $balance = \DB::table('funds')
                ->where('id', '=', $user['wallet_id'])
                ->value('balance');

            if ($send) {
                $dealData = ([
                    'user_id'           => $userId,
                    'user_type'         => 1,
                    'username'          => $user['name'],
                    'order_number'      => $tradeNo,
                    'deal_type'         => 105,
                    'deal_category'     => FundsDealLog::CATEGORY_INCOME,
                    'deal_money'        => $send,
                    'coupon_money'      => 0,
                    'balance'           => $balance,
                    'memo'              => $memo ?? $this->lang->text("Activity gift"),
                    'wallet_type'       => 1,
                    'total_bet'         => $dmlData->total_bet,
                    'withdraw_bet'      => 0,
                    'total_require_bet' => $dmlData->total_require_bet,
                    'free_money'        => $dmlData->free_money,
                    'admin_id'          => $admin_id,
                    'admin_user'        => $admin_user
                ]);
                FundsDealLog::create($dealData);

                $message = new Message($this->ci);
                $content  = ["Dear %s, hello! Your reward %s has arrived", $user['name'], ($send / 100)];
                $insertId = $this->messageAddByMan("Activity gift", $user['name'], $content);
                $message->messagePublish($insertId);
            }

            if ($play_code > 0) {
                //添加打码量到dml表
                $dml = new  \Model\Dml();
                $dml->addDml($userId, $play_code, $send, '活动赠送优惠添加打码量');
            }

            return true;
        } catch (\Exception $e) {

            $this->error->log('领取彩金错误:'.$e->getMessage());
            return false;
        }
    }


    /**
     * 直推注册账号后发放奖励
     * @param $invitationCode
     * @param $userId
     * @param $username
     * @param $walletId
     * @return array|mixed|void
     */
    function directRegAward($invitationCode, $userId, $username, $walletId)
    {
        $sysConfig = \Logic\Set\SystemConfig::getModuleSystemConfig('direct');
        if (!isset($sysConfig['cash_promotion_register']['send_amount']) || !isset($sysConfig['cash_be_pushed_register']['send_amount'])) {
            return ["code"=>-2, 'msg'=>'获取直推配置数据为空'];
        }
        //检测直推开关
        $direct_switch = $sysConfig['direct_switch'];
        if ($direct_switch != 1) {
            return ['code'=>-2,'msg'=>'直推开关已关闭'];
        }
        $sup_award = $sysConfig['cash_promotion_register']['send_amount'] * 100;    //上级发放的奖励
        $limit_sup = $sysConfig['cash_promotion_register']['get_limit'] * 100;   //上级历史累计最大获取奖励限制
        $self_award = $sysConfig['cash_be_pushed_register']['send_amount'] * 100;   //自己发放的奖励
        $limit_sub = $sysConfig['cash_be_pushed_register']['get_limit'] * 100;   //下级历史累计最大获取奖励限制
        $direct_dml = $sysConfig['send_dml'];   //打码量

        //获取上级代理信息
        $sup_agent = \Model\UserAgent::where('code', $invitationCode)->first();
        if(!$sup_agent){
            $agent_id = \DB::table('agent_code')->where('code',$invitationCode)->value('agent_id');
            $sup_agent = \Model\UserAgent::where('user_id', $agent_id)->first();
        }
        $sup_info = DB::table('user')->selectRaw('id,name,wallet_id,agent_switch')->whereRaw('id=?',[$sup_agent->user_id])->first();
        if (!isset($sup_info->id)) {
            return ["code"=>-2, 'msg'=>'上级代理信息错误:'.$sup_agent->user_id];
        }
        if ($sup_info->agent_switch == 0){
            //邀请成功开启代理
            \DB::table('user')->where('id', $sup_info->id)->update(['agent_switch'=>1,'agent_time'=>date('Y-m-d H:i:s',time())]);
            $agent  = new \Logic\User\Agent($this->ci);
            $profit = $agent->getProfit($sup_info->id);
            if($profit){
                DB::table('user_agent')->where('user_id',$sup_info->id)->update(['profit_loss_value'=>$profit]);
            }
            (new Log($this->ci))->create($sup_info->id, $sup_info->name, Log::MODULE_CASH, '直推绑定', '直推/直推绑定', '绑定上级代理', 1, '推广下级user_name：'.$username.'user_id：'.$userId.'，触发开启代理');
        }
        //发放上级直推奖励
        $ck_sup_money = DB::table('user_data')->whereRaw('user_id=?',[$sup_info->id])->value('direct_reg_award');
//        $this->logger->error('直推注册奖励：' . "uid=".$userId. " 上级uid=".$sup_info->id."上级已有奖励=$ck_sup_money"."限制最大奖励：".$limit_sup);
        if ($limit_sup <= 0 || $ck_sup_money < $limit_sup){
            (new Wallet($this->ci))->crease($sup_info->wallet_id, $sup_award);
//            DB::table('funds')->whereRaw('id=?',[$sup_info->wallet_id])->update(['balance'=>DB::raw('balance + '.$sup_award)]);
            $sup_direct_dml = $sup_award * ($direct_dml / 100);
            $supData = [
                'user_id' => $sup_info->id,
                'username' => $sup_info->name,
                'sup_uid' => $sup_info->id,
                'sup_name' => $sup_info->name,
                'type' => 1,   //1-注册，2-充值，3-绑定上级
                'price' => $sup_award,
                'dml' => $sup_direct_dml,
                'is_transfer' => 1,
                'date' => date("Y-m-d"),
                'created' => date("Y-m-d H:i:s"),
                'updated' => date("Y-m-d H:i:s")
            ];
            DB::table('direct_record')->insertGetId($supData);
            //更新上级代理的直推注册人数、已获奖励数
            DB::table('user_data')->whereRaw('user_id=?',[$sup_info->id])->update(['direct_register'=>DB::raw('direct_register + 1'),
                'direct_award'=>DB::raw('direct_award + '.$sup_award),'direct_reg_award'=>DB::raw('direct_reg_award +'.$sup_award)]);
            //记录交易流水
            $this->addFundsDealLog($sup_info->id,$sup_info->name,$sup_info->wallet_id,$sup_direct_dml,$sup_award,"直推-注册奖励");
        } else {
            DB::table('user_data')->whereRaw('user_id=?',[$sup_info->id])->update(['direct_register'=>DB::raw('direct_register + 1')]);
        }
        $rebetObj = new \Logic\Lottery\Rebet($this->ci);
        $rebetObj->updateUserDirectBkgeIncr($sup_info->id); // 直推返水比例

        //发放自己直推奖励
        $is_first_reg = (array)DB::table('direct_record')->whereRaw('type=? and user_id=?', [1,$userId])->first();    //检测一下该用户是否参加过直推注册活动
        if (empty($is_first_reg)) {
            //发放奖励了到直推钱包
            (new Wallet($this->ci))->crease($walletId, $self_award);
//            DB::table('funds')->whereRaw('id=?',[$walletId])->update(['balance'=>DB::raw('balance + '.$self_award)]);
            $sub_direct_dml = $self_award * ($direct_dml / 100);
            $selfData = [
                'user_id' => $userId,
                'username' => $username,
                'sup_uid' => $sup_info->id,
                'sup_name' => $sup_info->name,
                'type' => 1,   //1-注册，2-充值，3-绑定上级
                'price' => $self_award,
                'dml' => $sub_direct_dml,
                'is_transfer' => 1,
                'date' => date("Y-m-d"),
                'created' => date("Y-m-d H:i:s"),
                'updated' => date("Y-m-d H:i:s")
            ];
            DB::table('direct_record')->insertGetId($selfData);
            //更新自己直推已获奖励数
            DB::table('user_data')->whereRaw('user_id=?',[$userId])->update(['direct_award'=>DB::raw('direct_award + '.$self_award),'direct_reg_award'=>DB::raw('direct_reg_award +'.$self_award)]);
            //记录交易流水
            $this->addFundsDealLog($userId,$username,$walletId,$sub_direct_dml,$self_award,"直推-注册奖励");
        }
        return ['code'=>0, 'msg'=>'successful'];
    }

    //直推-充值奖励
    function directRechargeAward($userId,$money)
    {
//        $this->logger->error('直推充值奖励：' . "uid=".$userId." 金额=".$money);
        //直推充值奖励：检测用户是否绑定过上级代理，如果有上级且充值数量在直推充值配置范围内，则给上级和自己发放直推充值奖励
        $sup_agent = (array)DB::connection('slave')
                              ->table('user_agent')
                              ->selectRaw('uid_agent')
                              ->whereRaw('user_id=?', [$userId])
                              ->where('direct_reg', 1)     //直推开启后注册的用户才有充值赠送
                              ->first();
//        $this->logger->error('直推充值奖励：' . "uid=".$userId. "上级代理信息=".json_encode($sup_agent));
        if (isset($sup_agent['uid_agent']) && $sup_agent['uid_agent']>0) {
            $direct_conf = \Logic\Set\SystemConfig::getModuleSystemConfig('direct');
            if (!isset($direct_conf['cash_promotion_recharge']['send_amount']) || !isset($direct_conf['cash_be_pushed_recharge']['send_amount'])) {
                return ['code'=>-2,'msg'=>'系统配置数据为空'];
            }
            //检测直推开关
            $direct_switch = $direct_conf['direct_switch'];
            if ($direct_switch != 1) {
                return ['code'=>-2,'msg'=>'直推开关已关闭'];
            }
//            $this->logger->error('直推充值奖励：' . "uid=".$userId. "直推开关=".$direct_switch);
            $min_amount = $direct_conf['cash_be_pushed_recharge']['recharge_amount'] * 100;   //充值最小金额
            $sup_amount = $direct_conf['cash_promotion_recharge']['send_amount'] * 100;   //上级奖励
            $limit_sup = $direct_conf['cash_promotion_recharge']['get_limit'] * 100;   //上级最大获奖限制
            $self_amount = $direct_conf['cash_be_pushed_recharge']['send_amount'] * 100;  //自己奖励
            $limit_sub = $direct_conf['cash_be_pushed_recharge']['get_limit'] * 100;   //自身最大获奖限制
            $direct_dml = $direct_conf['send_dml'];   //打码量
//            $this->logger->error('直推充值金额：' . $money. "最小金额限制：".$min_amount."uid=".$userId);
            if ($money >= $min_amount) {
//                $this->logger->error('直推充值奖励：' . "uid=".$userId. "金额=".$money." 限制数量：".$min_amount);
                $supInfo = (array)DB::table('user')->selectRaw('id,wallet_id,name')->whereRaw('id=?',[$sup_agent['uid_agent']])->first();
                $selfInfo = (array)DB::table('user')->selectRaw('id,wallet_id,name')->whereRaw('id=?',[$userId])->first();
                //发奖励前要检测用户历史累计直推获奖总数是否超过最大限制
                $ck_sup_money = DB::table('user_data')->whereRaw('user_id=?',[$supInfo['id']])->value('direct_recharge_award');

                //发奖励前判断用户是否为第一次参加直推充值活动
                $is_first_rec = (array)DB::table('direct_record')->whereRaw('type=? and user_id=? and user_id != sup_uid', [2,$selfInfo['id']])->first();
                if (empty($is_first_rec)) {
                    //发上级奖励
                    if ($sup_amount > 0 && ($limit_sup <= 0 || $ck_sup_money < $limit_sup)){
                        (new Wallet($this->ci))->crease($supInfo['wallet_id'], $sup_amount);
//                        DB::table('funds')->whereRaw('id=?',[$supInfo['wallet_id']])->update(['balance'=>DB::raw('balance + '.$sup_amount)]);
                        $sup_direct_dml = $sup_amount * ($direct_dml / 100);   //计算打码量: 奖励 * (配置 / 100)
                        $supData = [
                            'user_id' => $supInfo['id'],
                            'username' => $supInfo['name'],
                            'sup_uid' => $supInfo['id'],
                            'sup_name' => $supInfo['name'],
                            'type' => 2,   //1-注册，2-充值，3-绑定上级
                            'price' => $sup_amount,
                            'dml' => $sup_direct_dml,
                            'is_transfer' => 1,
                            'date' => date("Y-m-d"),
                            'created' => date("Y-m-d H:i:s"),
                            'updated' => date("Y-m-d H:i:s")
                        ];
                        DB::table('direct_record')->insertGetId($supData);
                        //更新上级代理的直推充值人数、已获奖励数
                        DB::table('user_data')->whereRaw('user_id=?',[$supInfo['id']])->update(['direct_deposit'=>DB::raw('direct_deposit + 1'),
                            'direct_award'=>DB::raw('direct_award + '.$sup_amount), 'direct_recharge_award'=>DB::raw('direct_recharge_award + '.$sup_amount)]);
                        //添加交易流水记录
                        $this->addFundsDealLog($supInfo['id'],$supInfo['name'],$supInfo['wallet_id'],$sup_direct_dml,$sup_amount,"直推-充值奖励");
                    } else {
                        DB::table('user_data')->whereRaw('user_id=?',[$supInfo['id']])->update(['direct_deposit'=>DB::raw('direct_deposit + 1')]);
                    }
                    //发自己奖励
                    $ck_self_award = DB::table('user_data')->whereRaw('user_id=?',[$selfInfo['id']])->value('direct_recharge_award');
                    if ($limit_sub <= 0 || $ck_self_award < $limit_sub) {
                        (new Wallet($this->ci))->crease($selfInfo['wallet_id'], $self_amount);
                        DB::table('funds')->whereRaw('id=?',[$selfInfo['wallet_id']])->update(['balance'=>DB::raw('balance + '.$self_amount)]);
                        $sub_direct_dml = $self_amount * ($direct_dml / 100);
                        $selfData = [
                            'user_id' => $selfInfo['id'],
                            'username' => $selfInfo['name'],
                            'sup_uid' => $supInfo['id'],
                            'sup_name' => $supInfo['name'],
                            'type' => 2,   //1-注册，2-充值，3-绑定上级
                            'price' => $self_amount,
                            'dml' => $sub_direct_dml,
                            'is_transfer' => 1,
                            'date' => date("Y-m-d"),
                            'created' => date("Y-m-d H:i:s"),
                            'updated' => date("Y-m-d H:i:s")
                        ];
                        DB::table('direct_record')->insertGetId($selfData);
                        //更新自己直推已获奖励数
                        DB::table('user_data')->whereRaw('user_id=?',[$selfInfo['id']])->update(['direct_award'=>DB::raw('direct_award + '.$self_amount),
                            'direct_recharge_award'=>DB::raw('direct_recharge_award + '.$self_amount)]);
                        //添加交易流水记录
                        $this->addFundsDealLog($selfInfo['id'],$selfInfo['name'],$selfInfo['wallet_id'],$sub_direct_dml,$self_amount,"直推-充值奖励");
                    }
                    $rebetObj = new Rebet($this->ci);
                    $rebetObj->updateUserDirectBkgeIncr($supInfo['id']); // 直推返水比例
                }
            }
        }
    }

    //直推-添加交易流水
    public function addFundsDealLog($userId,$username,$walletId,$conf_dml,$money,$memo)
    {
        //记录交易流水
        $tradeNo = date('YmdHis') . rand(pow(10, 3), pow(10, 4) - 1);
        $balance = \DB::table('funds')->where('id', '=', $walletId)->value('balance');
        //添加打码量可提余额等信息
        $dml = new \Logic\Wallet\Dml($this->ci);
        $dmlData = $dml->getUserDmlData($userId, $conf_dml, 2);
        $dealData = ([
            'user_id'           => $userId,
            'user_type'         => 1,
            'username'          => $username,
            'order_number'      => $tradeNo,
            'deal_type'         => 105,
            'deal_category'     => FundsDealLog::CATEGORY_INCOME,
            'deal_money'        => $money,
            'coupon_money'      => 0,
            'balance'           => $balance,
            'memo'              => $memo ?? $this->lang->text("Activity gift"),
            'wallet_type'       => 1,
            'total_bet'         => $dmlData->total_bet,
            'withdraw_bet'      => $conf_dml,
            'total_require_bet' => $dmlData->total_require_bet,
            'free_money'        => $dmlData->free_money,
        ]);
        FundsDealLog::create($dealData);
        //添加打码量到dml表
        $dml = new  \Model\Dml();
        $dml->addDml($userId, $conf_dml, $balance, '活动赠送优惠添加打码量');
    }
}