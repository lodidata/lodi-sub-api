<?php

use Logic\Admin\BaseController;
use \Logic\Recharge\Recharge;
use Model\FundsDealLog;
use Utils\Www\Action;
use Respect\Validation\Validator as V;

return new class extends Action {
    const TOKEN = true;
    const TITLE = '钱包-增加减少余额';
    const TAGS = "钱包";
    const PARAMS = [
        'type'    => 'enum[INCREASE,DECREASE](,DECREASE) #类型：INCREASE 增加  ，DECREASE减少',
        'amount' => 'int(required) #变动金额',
        'memo'   => 'string() #备注',
    ];
    const SCHEMAS = [
    ];

    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $validator = $this->validator->validate($this->request, [
            'amount' => V::intVal()->noWhitespace()->setName($this->lang->text("Adjustment amount")),
            'type' => V::noWhitespace()->length(4)->setName($this->lang->text("Adjustment type")),
        ]);


        if (!$validator->isValid()) {
            return $validator;
        }
        $userId = $this->auth->getUserId();

        $param = $this->request->getParams();

        if(!in_array($param['type'],['DECREASE'])) {
            return $this->lang->set(899);
        }
        $decrease = $param['type'] == 'DECREASE' ? true : false;
        //  回收所有钱包以供提款
        $wid = \Model\User::where('id',$userId)->value('wallet_id');
        $tmp_game = \Model\FundsChild::where('pid',$wid)->where('balance','>',0)->pluck('game_type')->toArray();
        foreach ($tmp_game as $val) {
            $gameClass = \Logic\GameApi\GameApi::getApi($val, $userId);
            $gameClass->rollOutThird();
        }
        $result = $this->handDecrease($userId,$param['amount'],$param['memo'],\Utils\Client::getIp(),false,$decrease);
        return $this->lang->set($result);
    }

    /**
     * 扣款方法
     *
     * @param int $userId 用户ID
     * @param int $amount 金额
     * @param string $memo 备注
     * @param string $ip 操作者IP
     * @param bool $freeMoney 是否更新可提余额
     *
     * @return bool
     */
    public function handDecrease(
        int $userId,
        int $amount,
        string $memo,
        string $ip,
        bool $freeMoney = false,
        bool $decrease = true
    ) {
        $user = \Model\User::find($userId)
            ->toArray();
        if (!$user) {
            return 900;
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
            if ($amount > $oldFunds['balance']) {
                $this->db->getConnection()
                    ->rollback();

                return 901;
            }
            if($decrease) {
                (new \Logic\Wallet\Wallet($this->ci))->crease($user['wallet_id'], -$amount);
                //写入user_data数据中心
                \Model\UserData::where('user_id',$userId)->increment('withdraw_amount',$amount,['withdraw_num'=>\DB::raw('withdraw_num + 1')]);
            }else {
                (new \Logic\Wallet\Wallet($this->ci))->crease($user['wallet_id'], $amount);
                //写入user_data数据中心
                \Model\UserData::where('user_id',$userId)->increment('deposit_amount',$amount,['deposit_num'=>\DB::raw('deposit_num + 1')]);
            }

            $after_balance = \DB::table('funds')
                ->where('id', '=', $user['wallet_id'])
                ->value('balance');

            if ($freeMoney) {
                $user_free_money = \DB::table('user_data')
                    ->where('user_id', '=', $userId)
                    ->value('free_money');

                $last_free_money = $user_free_money - $amount > 0 ? $user_free_money - $amount : 0;

                \Model\User::updateBetData($userId, ['free_money'=>$last_free_money]);
            }

            //流水里面添加打码量可提余额等信息
            $dml = new \Logic\Wallet\Dml($this->ci);
            $dmlData = $dml->getUserDmlData($userId);
            $freeMoney = $dmlData->free_money;

            //手动出款需要更新可提余额
//            $freeMoney = $freeMoney - $amount;

            //可提余额不为负数
            if ($freeMoney < 0) {
                $freeMoney = 0;
            }

            //可提余额不能超过钱包余额
            if ($freeMoney > $after_balance) {
                $freeMoney = $after_balance;
            }

            /**
             * 该地方主要注意
             * 出入款报表不统计 deal_type == FundsDealLog::TYPE_REDUCE_MANUAL 的现金流水记录
             */

            $dealData = ([
                'user_id'           => $userId,
                'user_type'         => 1,
                'username'          => $user['name'],
                'order_number'      => date('YmdHis') . random_int(pow(10, 3), pow(10, 4) - 1),
                'deal_type'         => $decrease ? FundsDealLog::TYPE_REDUCE_MANUAL_OTHER : FundsDealLog::TYPE_ADDMONEY_MANUAL_OTHER,
                'deal_category'     => $decrease ? FundsDealLog::CATEGORY_COST : FundsDealLog::CATEGORY_INCOME,
                'deal_money'        => $amount,
                'balance'           => $after_balance,
                'memo'              => $memo,
                'wallet_type'       => 1,
                'total_bet'         => $dmlData->total_bet,
                'withdraw_bet'      => 0,
                'total_require_bet' => $dmlData->total_require_bet,
                'free_money'        => $freeMoney,
                'admin_id'          => 0,
                'admin_user'        => '',
            ]);
            FundsDealLog::create($dealData);  //增加现金流水记录

            $dealManual = ([
                'user_id'       => $userId,
                'username'      => $user['name'],
                'user_type'     => 1,
                'type'          => $decrease ? 4 : 5,
                'operator_type' => 1,
                'front_money'   => $oldFunds['balance'],
                'money'         => $amount,
                'balance'       => $after_balance,
                'admin_uid'     => 0,
                'ip'            => $ip,
                'wallet_type'   => 1,
                'memo'          => $memo,
                'created'       => time(),
                'updated'       => time(),
            ]);
            \Model\FundsDealManual::create($dealManual);  //增加资金流水

            $this->db->getConnection()
                ->commit();
            return 0;
        } catch (\Exception $e) {
            $this->db->getConnection()
                ->rollback();
            return 902;
        }
    }
};
