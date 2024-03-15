<?php
namespace Logic\Recharge\Traits;


use Logic\GameApi\Common;
use Model\FundsDealLog;
use Model\FundsDealManual;

trait RechargeShareManual{

    /**
     * 手动增加股东分红可提余额
     * @param int    $userId 用户id
     * @param int    $amount 金额
     * @param string $memo 备注
     * @param int    $currentUserId 操作人id
     * @return bool
     */
    public function increase(int $userId,int $amount,string $memo,int $currentUserId,string $applyAdmin=''){
        //$user = \Model\User::where('id', $userId)->first();
        $user = (new Common($this->ci))->getUserInfo($userId);
        if (!$memo) {
            $memo = $this->lang->text("Manually increase the balance in the main and back office");
        }
        try {
            $this->db->getConnection()
                     ->beginTransaction();
            $wallet = new \Logic\Wallet\Wallet($this->ci);

            // 锁定钱包
            $oldFunds = \Model\Funds::where('id', $user['wallet_id'])
                                    ->lockForUpdate()
                                    ->first();
            $wallet->crease($user['wallet_id'],$amount,2);

            $tradeNo=FundsDealLog::generateDealNumber();

            if (isset($GLOBALS['playLoad'])) {
                $admin_id = $GLOBALS['playLoad']['uid'];
                $admin_name = $GLOBALS['playLoad']['nick'];
            } else {
                $admin_id = 0;
                $admin_name = '';
            }

            $funds = \Model\Funds::where('id', $user['wallet_id'])->first();

            // 增加资金流水
            FundsDealLog::create([
                'user_id'           => $userId,
                'user_type'         => 1,
                'username'          => $user['name'],
                'order_number'      => $tradeNo,
                'deal_type'         => FundsDealLog::TYPE_INCREASE_SHARE_MANUAL,
                'deal_category'     => FundsDealLog::CATEGORY_INCOME,
                'deal_money'        => $amount,
                'balance'           => intval($funds['share_balance']),
                'memo'              => $this->lang->text("Manually increase the balance in the main and back office"),
                'wallet_type'       => FundsDealLog::WALLET_TYPE_PRIMARY,
                'total_bet'         => 0,
                'withdraw_bet'      => 0,
                'total_require_bet' => 0,
                'free_money'        => intval($funds['share_balance']),

                'admin_id'   => $admin_id,
                'admin_user' => $admin_name,
            ]);

            // 增加手动入款记录
            FundsDealManual::create([
                'user_id'       => $userId,
                'username'      => $user['name'],
                'user_type'     => 1,
                'type'          => 13,
                'trade_no'      => $tradeNo,
                'operator_type' => 1,
                'front_money'   => intval($oldFunds['share_balance']),
                'money'         => $amount,
                'balance'       => intval($funds['share_balance']),
                'admin_uid'     => $currentUserId,
                'wallet_type'   => 1,
                'memo'          => $applyAdmin ? $memo."--{$applyAdmin}" : $memo,
                'withdraw_bet'  => 0,
            ]);



            $this->db->getConnection()
                     ->commit();
            return true;
        }catch(\Exception $e){
            $this->logger->error('手动增加股东分红可提余额错误',$e->getMessage());
            $this->db->getConnection()
                     ->rollback();
            return false;
        }
    }

    /**
     * 手动减少股东分红可提余额
     * @param int    $userId 用户id
     * @param int    $amount 金额
     * @param string $memo 备注
     * @param int    $currentUserId 操作人id
     * @return bool
     */
    public function decrease(int $userId,int $amount,string $memo,int $currentUserId){
        $user = (new Common($this->ci))->getUserInfo($userId);

        if (!$memo) {
            $memo = $this->lang->text("Manually increase the balance in the main and back office");
        }
        try {
            $this->db->getConnection()
                     ->beginTransaction();
            $wallet = new \Logic\Wallet\Wallet($this->ci);

            // 锁定钱包
            $oldFunds = \Model\Funds::where('id', $user['wallet_id'])
                                    ->lockForUpdate()
                                    ->first();
            if($amount > $oldFunds['share_balance']){
                $this->db->getConnection()
                         ->rollback();

                return false;
            }
            $wallet->crease($user['wallet_id'],-$amount,2);

            $tradeNo=FundsDealLog::generateDealNumber();

            if (isset($GLOBALS['playLoad'])) {
                $admin_id = $GLOBALS['playLoad']['uid'];
                $admin_name = $GLOBALS['playLoad']['nick'];
            } else {
                $admin_id = 0;
                $admin_name = '';
            }

            $funds = \Model\Funds::where('id', $user['wallet_id'])->first();

            // 增加资金流水
            FundsDealLog::create([
                'user_id'           => $userId,
                'user_type'         => 1,
                'username'          => $user['name'],
                'order_number'      => $tradeNo,
                'deal_type'         => FundsDealLog::TYPE_DECREASE_SHARE_MANUAL,
                'deal_category'     => FundsDealLog::CATEGORY_COST,
                'deal_money'        => $amount,
                'balance'           => intval($funds['share_balance']),
                'memo'              => $this->lang->text("Manually increase the balance in the main and back office"),
                'wallet_type'       => FundsDealLog::WALLET_TYPE_PRIMARY,
                'total_bet'         => 0,
                'withdraw_bet'      => 0,
                'total_require_bet' => 0,
                    'free_money'        => intval($funds['share_balance']),

                'admin_id'   => $admin_id,
                'admin_user' => $admin_name,
            ]);

            // 增加手动入款记录
            FundsDealManual::create([
                'user_id'       => $userId,
                'username'      => $user['name'],
                'user_type'     => 1,
                'type'          => 14,
                'trade_no'      => $tradeNo,
                'operator_type' => 1,
                'front_money'   => intval($oldFunds['share_balance']),
                'money'         => $amount,
                'balance'       => intval($funds['share_balance']),
                'admin_uid'     => $currentUserId,
                'wallet_type'   => 1,
                'memo'          => $memo,
                'withdraw_bet'  => 0,
            ]);



            $this->db->getConnection()
                     ->commit();
            return true;
        }catch(\Exception $e){
            $this->logger->error('手动增加股东分红可提余额错误',$e->getMessage());
            $this->db->getConnection()
                     ->rollback();
            return false;
        }
    }
}