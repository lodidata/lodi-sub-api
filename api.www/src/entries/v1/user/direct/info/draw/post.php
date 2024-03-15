<?php
use Utils\Www\Action;
use Model\FundsDealLog;
use Model\User;
use Logic\Wallet\Dml;
use Logic\Wallet\Wallet;
use Model\Funds;

return new class extends Action {
    const TOKEN = true;
    const TITLE = '领取奖励';
    const TAGS = "直推活动";
    const QUERY = [
        'ids' => 'string(required) #活动ID',
        'dml' => 'int(required) #打码量',
        'balance' => 'int(required) #钱包余额'
    ];
    const SCHEMAS     = [];

    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $userId = $this->auth->getUserId();

        $amount = $this->request->getParam('balance');          //钱包余额

        /*
        #发奖励会增加应有打码量，转出到主钱包不需要增加应有打码量
        $dml = $this->request->getParam('dml');                 //打码量
        */

        if($amount <= 0.00) {
            return $this->lang->set(0);
        }

        $amount = $amount * 100;
        $user = User::where('id', $userId)->first();

        try{
            DB::beginTransaction();

            $oldFunds = DB::table('funds')->where('id', $user['wallet_id'])->lockForUpdate()->first();
            $oldFunds = (array)$oldFunds;

            (new Wallet($this->ci))->crease($user['wallet_id'], $amount);

            //判断直推钱包余额≤0 或 (直推钱包余额-转出金额)≤0 不能转出
            if($oldFunds['direct_balance'] <= 0 || ($oldFunds['direct_balance']-$amount) < 0) {
                $this->db->getConnection()
                    ->rollback();

                return $this->lang->set(901);
            }

            //减少直推钱包余额
            Funds::where('id', $user['wallet_id'])->update(['direct_balance' => \DB::raw('direct_balance-'.$amount)]);

            FundsDealLog::create([
                'user_id' => $userId,
                'user_type' => 1,
                'username' => $user['name'],
                'deal_type' => FundsDealLog::TYPE_DIRECT_REWARD_COST,
                'deal_category' => FundsDealLog::CATEGORY_COST,
                'deal_money' => $amount,
                'balance' => $oldFunds['balance'] + $amount,
                'memo' => $this->lang->text('Direct award cost'),
                'wallet_type' => 1
            ]);

            // 更新直推活动记录状态
            $sql = "SELECT `id` FROM `direct_record` WHERE `user_id`='{$userId}' AND (`type`=1 OR `type`=2 OR `type`=3) AND `is_transfer`=0";
            $res = DB::connection()->select($sql);
            $idArr = array_column($res, 'id');
            if(!empty($idArr)) {
                $ids = implode(',', $idArr);
                DB::update("UPDATE `direct_record` SET `is_transfer` = 1 WHERE `id` IN ({$ids})");
            }

            DB::commit();
            return $this->lang->set(0);
        }catch (\Exception $e){
            DB::rollback();
            var_dump($e->getMessage());
            return $this->lang->set(-2);
        }
    }
};