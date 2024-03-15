<?php

namespace Logic\Recharge\Traits;

use Logic\User\User;

trait RechargeHandApply
{
    /**
     * 申请出款单
     *
     * @param int $userId 收款用户id
     * @param int $amount 金额
     * @param int $receiveBankId 收款银行id
     * @param string $memo 备注
     * @param int $type 提现类型,1:主钱包,2:股东分红
     * @param int $currentUserId 当前用户id
     * @param string $state
     *
     * @return mixed
     */
    public function handApply(
        $userId,
        $amount,
        $receiveBankId,
        $memo,
        $type=1,
        $currentUserId = 0,
        $state = '',
        $fee = 0
    )
    {
        if($type == 2){
            $field='share_balance';
            $freeze='share_freeze_withdraw';
            $dealType=\Model\FundsDealLog::TYPE_WITHDRAW_SHARE;
        }else{
            $field="balance";
            $freeze='freeze_withdraw';
            $dealType=\Model\FundsDealLog::TYPE_WITHDRAW_ONFREEZE;
        }
        $userType = 1;
        $user = \Model\User::where('id', $userId)
            ->first()
            ->toArray();
        if($amount <= 0){
            return $this->lang->set(193);
        }
        //获取来源
        $origins = ['pc' => 1, 'h5' => 2, 'ios' => 3, 'android' => 4];
        $origin = isset($this->request->getHeaders()['HTTP_PL']) && is_array($this->request->getHeaders()['HTTP_PL']) ? current($this->request->getHeaders()['HTTP_PL']) : '';
        $platformId = $origins[$origin] ?? 1;

        try {
            $this->db->getConnection()
                ->beginTransaction();

            // 判断是否禁止会员提现
            if (in_array('refuse_withdraw', explode(',', $user['auth_status']))) {
                $this->db->getConnection()
                    ->rollback();
                return $this->lang->set(200);
            }

            $wallet = new \Logic\Wallet\Wallet($this->ci);

            $oldFunds = \Model\Funds::where('id', $user['wallet_id'])
                ->lockForUpdate()
                ->first();

//            $count = \Model\FundsWithdraw::where('user_id', $userId)->where('user_type', $userType)->where('status', 'pending')->count();
//            // 判断已经申请订单
//            if ($count > 0) {
//                $this->db->getConnection()->rollback();
//                return $this->lang->set(167);
//            }

            if ($oldFunds["{$field}"] < $amount) {
                $this->db->getConnection()
                    ->rollback();
                return $this->lang->set(161, [], [], ['old' => $oldFunds["{$field}"], 'amount' => $amount]);
            }

            $bankUser = \Model\BankUser::where('state', 'enabled')
                ->where('role', 1)
                ->where('user_id', $userId)
                ->where('id', $receiveBankId)
                ->first();

            if (empty($bankUser)) {
                $this->db->getConnection()
                    ->rollback();
                return $this->lang->set(148);
            }

            $lastData = \Model\FundsWithdraw::where('user_id', $userId)
                ->where('user_type', $userType)
                ->orderby('created', 'desc')
                ->first();
            $tmpstate = empty($lastData) ? (empty($state) ? 'new' : $state . ',new') : $state;

            if ($receiveBankId) {
                $bank = \Model\Bank::where('id', $bankUser->bank_id)
                    ->first();
                $receiveBankInfo = json_encode([
                    'bank' => $this->lang->text($bank->code),
                    'name' => $bankUser->name,
                    'card' => $bankUser->card,
                    'address' => $bankUser->address,
                    'bank_code' => $bank->code,
                ], JSON_UNESCAPED_UNICODE);
            } else {
                $receiveBankInfo = json_encode([
                    'bank' => '',
                    'name' => '',
                    'card' => '',
                    'address' => '',
                    'bank_code' => ''
                ], JSON_UNESCAPED_UNICODE);
            }

            $date = date('Y-m-d');
            $time = time();
            $timesData = \Model\FundsWithdraw::where('user_type', 1)
                ->where('user_id', $userId)
                ->where('status', 'paid')
                ->where('created', '>=', $date . ' 00:00:00')
                ->where('created', '<=', $date . ' 23:59:59')
                ->select([
                    \DB::raw('COUNT(1) count'),
                    \DB::raw('SUM(money) money'),
                ])
                ->first();

            $tradeNo = date("YmdHis") . rand(pow(10, 3), pow(10, 4) - 1);

            // 判断是否是首提
            $lastWithdraw = \Model\FundsWithdraw::where('user_id', $userId)
                ->where('user_type', $userType)
                ->first();

            if (!$lastWithdraw) {
                if ($state == '') {
                    $state = 'new';
                } else {
                    $state = $state . ',new';
                }
            }

//            if ($fee) $amount =  bcsub($amount,bcmul($amount, $fee / 100,2),2);
            $model = [
                'trade_no' => $tradeNo,
                'user_id' => $userId,
                'user_type' => $userType,
                'type'  =>$type,
                'money' => $amount,
                'valid_bet' => 0,
                'fee' => $fee,
                'admin_fee' => 0,
                'receive_bank_account_id' => $receiveBankId,
                'bank_id' => $bankUser->bank_id,
                'receive_bank_info' => $receiveBankInfo,
                'ip' => \Utils\Client::getIp(),
                'created_uid' => $currentUserId,
                'status' => 'pending',
                'memo' => $memo,
                'state' => $state,
                'origin' => isset($origins[$origin]) ? $origins[$origin] : 0,
                'today_times' => $timesData['count'] + 1,
                'today_money' => $timesData['money'],
                'created' => date('Y-m-d H:i:s', $time),
            ];

            // 记录上次出款时间
            if (!empty($model)) {
                $model['previous_time'] = $lastData['created'];
            }

            // 提款冻结余额
            \Model\Funds::where('id', $user['wallet_id'])
                ->update([
                    "{$freeze}" => \DB::raw("{$freeze} + $amount"),
                ]);



            // 扣钱
            $wallet->crease($user['wallet_id'], -$amount,$type);

            $model = \Utils\Utils::RSAPatch($model);
            $funds = \Model\Funds::where('id', $user['wallet_id'])
                ->first();
            if($type == 1) {
                $dml       = new \Logic\Wallet\Dml($this->ci);
                $dmlData   = $dml->getUserDmlData($user['id'], $amount, 3);
                $freeMoney = $dmlData->free_money;
                $balance=$funds['balance'];
            }else{
                $balance=$funds['share_balance'];
            }

            //增加资金流水
            //流水里面添加打码量可提余额等信息
            \Model\FundsDealLog::create([
                "user_id" => $user['id'],
                "user_type" => 1,
                "username" => $user['name'],
                "order_number" => $model['trade_no'],
                "deal_type" => $dealType,
                "deal_category" => \Model\FundsDealLog::CATEGORY_COST,
                "deal_money" => $amount,
                "balance" => $balance,
                "memo" => $this->lang->text("Member withdrawal freeze"),
                "wallet_type" => \Model\FundsDealLog::WALLET_TYPE_PRIMARY,
                'total_bet' => $dmlData->total_bet ?? 0,
                'withdraw_bet' => 0,
                'total_require_bet' => $dmlData->total_require_bet ?? 0,
                'free_money' => $freeMoney ?? $balance,
            ]);
            \Model\FundsWithdraw::create($model);

            if ($state == 'tz') {
                // 增加手动入款记录
                \Model\FundsDealManual::create([
                    'user_id' => $user['id'],
                    'username' => $user['name'],
                    'user_type' => 1,
                    'type' => 5,
                    'trade_no' => $model['trade_no'],
                    'operator_type' => 1,
                    'front_money' => intval($oldFunds['balance']),
                    'money' => $amount,
                    'balance' => intval($funds['balance']),
                    'admin_uid' => $currentUserId,
                    'wallet_type' => 1,
                    'memo' => $this->lang->text("Main and back office manual payment application"),
                ]);
            }

            \Model\UserLog::create([
                'user_id' => $user['id'],
                'name' => $user['name'],
                'log_value' => $this->lang->text("Application for payment"),
                'status' => 1,
                'log_type' => 2,
                'platform' => $platformId,
            ]);

            $this->db->getConnection()
                ->commit();
        } catch (\Exception $e) {
            $this->db->getConnection()
                ->rollback();
            \Model\UserLog::create([
                'user_id' => $user['id'],
                'name' => $user['name'],
                'log_value' => $this->lang->text("Application for payment"),
                'status' => 0,
                'log_type' => 2,
                'platform' => $platformId,
            ]);
            $this->logger->error('handApply error:' . $e->getMessage());
            return $this->lang->set(170);
        }
        return $this->lang->set(4206);
    }
}