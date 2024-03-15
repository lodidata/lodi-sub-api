<?php
namespace Logic\Recharge\Traits;


use Logic\GameApi\Common;
use Model\FundsDealLog;
use Model\FundsDealManual;

trait RechargeDirectManual{

    /**
     * 手动增加直推余额
     * @param int    $userId 用户id
     * @param int    $amount 金额
     * @param int    $play_code 打码量
     * @param string $memo 备注
     * @param int    $currentUserId 操作人id
     * @return bool
     */
    public function increaseDirect(int $userId,int $amount, int $play_code, string $memo,int $currentUserId,string $applyAdmin=''){
        //$user = \Model\User::where('id', $userId)->first();
        $user = (new Common($this->ci))->getUserInfo($userId);
        $play_code = (int)$play_code;
        $default_memo = "Manually increase the direct balance";
        if (!$memo) {
            $memo = $this->lang->text($default_memo);
        }
        try {
            //添加打码量可提余额等信息
            $dml = new \Logic\Wallet\Dml($this->ci);
            $dmlData = $dml->getUserDmlData($userId, $play_code, 2);

            $this->db->getConnection()
                     ->beginTransaction();
            $wallet = new \Logic\Wallet\Wallet($this->ci);

            // 锁定钱包
            $oldFunds = \Model\Funds::where('id', $user['wallet_id'])
                                    ->lockForUpdate()
                                    ->first();
            $result = $wallet->crease($user['wallet_id'],$amount,4);

            if(empty($result)){
                $this->db->getConnection()
                    ->rollback();

                return false;
            }

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
                'deal_type'         => FundsDealLog::TYPE_INCREASE_DIRECT_MANUAL,
                'deal_category'     => FundsDealLog::CATEGORY_INCOME,
                'deal_money'        => $amount,
                'balance'           => intval($funds['direct_balance']),
                'memo'              => $this->lang->text($default_memo),
                'wallet_type'       => FundsDealLog::WALLET_TYPE_PRIMARY,
                'total_bet'         => $dmlData->total_bet,
                'withdraw_bet'      => $play_code,
                'total_require_bet' => $dmlData->total_require_bet,
                'free_money'        => $dmlData->free_money,

                'admin_id'   => $admin_id,
                'admin_user' => $admin_name,
            ]);

            // 增加手动入款记录
            FundsDealManual::create([
                'user_id'       => $userId,
                'username'      => $user['name'],
                'user_type'     => 1,
                'type'          => FundsDealManual::MANUAl_INCREASE_DIRECT,
                'trade_no'      => $tradeNo,
                'operator_type' => 1,
                'front_money'   => intval($oldFunds['direct_balance']),
                'money'         => $amount,
                'balance'       => intval($funds['direct_balance']),
                'admin_uid'     => $currentUserId,
                'wallet_type'   => 1,
                'withdraw_bet'  => $play_code,
                'memo'          => $applyAdmin ? $memo."--{$applyAdmin}" : $memo,
            ]);

            $this->db->getConnection()
                     ->commit();

            if ($play_code > 0) {
                //添加打码量到dml表
                $dml = new  \Model\Dml();
                $dml->addDml($userId, $play_code, $amount, '手动增加直推余额添加打码量');
            }
            return true;
        }catch(\Exception $e){
            $this->logger->error('手动增加直推余额错误:'.$e->getMessage());
            $this->db->getConnection()
                     ->rollback();
            return false;
        }
    }

    /**
     * 手动减少直推余额
     * @param int    $userId 用户id
     * @param int    $amount 金额
     * @param string $memo 备注
     * @param int    $currentUserId 操作人id
     * @return bool
     */
    public function decreaseDirect(int $userId,int $amount,string $memo,int $currentUserId){
        $user = (new Common($this->ci))->getUserInfo($userId);
        $default_memo = "Manually decrease the direct balance";

        if (!$memo) {
            $memo = $this->lang->text($default_memo);
        }
        try {
            $this->db->getConnection()
                     ->beginTransaction();
            $wallet = new \Logic\Wallet\Wallet($this->ci);

            // 锁定钱包
            $oldFunds = \Model\Funds::where('id', $user['wallet_id'])
                                    ->lockForUpdate()
                                    ->first();
            if($amount <= 0){
                $this->db->getConnection()
                         ->rollback();

                return false;
            }
            $result = $wallet->crease($user['wallet_id'],-$amount,4);

            if(empty($result)){
                $this->db->getConnection()
                    ->rollback();

                return false;
            }
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
                'deal_type'         => FundsDealLog::TYPE_DECREASE_DIRECT_MANUAL,
                'deal_category'     => FundsDealLog::CATEGORY_COST,
                'deal_money'        => $amount,
                'balance'           => intval($funds['direct_balance']),
                'memo'              => $this->lang->text($default_memo),
                'wallet_type'       => FundsDealLog::WALLET_TYPE_PRIMARY,
                'total_bet'         => 0,
                'withdraw_bet'      => 0,
                'total_require_bet' => 0,
                    'free_money'        => intval($funds['direct_balance']),

                'admin_id'   => $admin_id,
                'admin_user' => $admin_name,
            ]);

            // 手动减少直推余额
            FundsDealManual::create([
                'user_id'       => $userId,
                'username'      => $user['name'],
                'user_type'     => 1,
                'type'          => FundsDealManual::MANUAl_DECREASE_DIRECT,
                'trade_no'      => $tradeNo,
                'operator_type' => 1,
                'front_money'   => intval($oldFunds['direct_balance']),
                'money'         => $amount,
                'balance'       => intval($funds['direct_balance']),
                'admin_uid'     => $currentUserId,
                'wallet_type'   => 1,
                'memo'          => $memo,
                'withdraw_bet'  => 0,
            ]);


            $this->db->getConnection()
                     ->commit();
            return true;
        }catch(\Exception $e){
            $this->logger->error('手动减少直推余额错误:'.$e->getMessage());
            $this->db->getConnection()
                     ->rollback();
            return false;
        }
    }
}