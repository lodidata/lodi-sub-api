<?php
use Utils\Www\Action;
use Respect\Validation\Validator as V;
return new class extends Action
{
    const TOKEN = true;
    const TITLE = "撤销提现";
    const TAGS = "充值提现";
    const QUERY = [
        "id" => "int(required) #记录ID",
   ];
    const SCHEMAS = [
   ];


    public function run($id) {

        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $userId = $this->auth->getUserId();
        try {
            $this->db->getConnection()->beginTransaction();
            $user = \Model\User::where('id', $userId)->first()->toArray();
            $withdraw = \Model\FundsWithdraw::where('id', $id)->first()->toArray();
            $wallet = new \Logic\Wallet\Wallet($this->ci);
            $oldFunds = \Model\Funds::where('id', $user['wallet_id'])->lockForUpdate()->first();
            // 加钱
            $wallet->crease($user['wallet_id'], $withdraw['money']);
            // 提款解冻余额
            \Model\Funds::where('id', $user['wallet_id'])->update([
                'freeze_withdraw' => \DB::raw("freeze_withdraw - {$withdraw['money']}")
            ]);

            $dml = new \Logic\Wallet\Dml($this->ci);
            $dmlData = $dml->getUserDmlData($user['id'], -$withdraw['money'], 3);
            $fundsWithdraw['status'] = 'canceled';
            $fundsWithdraw['process_uid'] = $userId;
            $fundsWithdraw['memo'] = $this->lang->text("Withdrawal withdrawn by user");
            \Model\FundsWithdraw::where('id', $id)->where('user_id', $userId)->where('status', 'pending')->update($fundsWithdraw);
            //流水里面添加打码量可提余额等信息
            $dealData = [
                "user_id" => $userId,
                "user_type" => 1,
                "username" => $user['name'],
                "order_number" => $withdraw['trade_no'],
                "deal_type" => \Model\FundsDealLog::TYPE_WITHDRAW_FREEZE,
                "deal_category" => \Model\FundsDealLog::CATEGORY_INCOME,
                "deal_money" => $withdraw['money'],
                "balance" => $dmlData->balance,
                "memo" => $this->lang->text("The withdrawal amount will be unfrozen if the member cancels the withdrawal"),
                "wallet_type" => \Model\FundsDealLog::WALLET_TYPE_PRIMARY,
                'total_bet' => $dmlData->total_bet,
                'withdraw_bet' => 0,
                'total_require_bet' => $dmlData->total_require_bet,
                'free_money' => $dmlData->free_money,
            ];
            \DB::table('funds_deal_log')->insert($dealData);
            $this->db->getConnection()->commit();
            return $this->lang->set(0);
        }catch (\Exception $e) {
            $this->db->getConnection()->rollback();
            return $this->lang->set(-2);
        }
    }
};