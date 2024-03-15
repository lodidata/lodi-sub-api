<?php

use Utils\Www\Action;
use Respect\Validation\Validator as V;
use Model\UserLog;

return new class extends Action {
    const TOKEN = true;
    const TITLE = "会员提现-会员线上提款申请";
    const TAGS = "充值提现";
    const PARAMS = [
        "withdraw_money" => "int(required) #取款金额",
        "withdraw_card"  => "int(required) #银行卡id",
        "memo"           => "string() #备注",
    ];
    const SCHEMAS = [
    ];

    public function run() {
        $verify = $this->auth->verfiyToken();

        if (!$verify->allowNext()) {
            return $verify;
        }

        $validator = $this->validator->validate($this->request, [
            'withdraw_money' => V::Positive()
                                 ->noWhitespace()
                                 ->setName($this->lang->text("withdraw_money")),
            'withdraw_card'  => V::noWhitespace()
                                 ->setName($this->lang->text("withdraw_card")),
            'memo'           => V::noWhitespace()
                                 ->setName($this->lang->text("memo")),
        ]);

        if (!$validator->isValid()) {
            return $validator;
        }
        $userId     = $this->auth->getUserId();


        $userModel=new Logic\User\User($this->ci);
        $isVerify=$userModel->withdrawVerify($userId);
        if($isVerify){
            return $this->lang->set(2224);
        }

        $withdrawMoney = $this->request->getParam('withdraw_money');
        $withdrawCard = $this->request->getParam('withdraw_card');
        $memo = $this->request->getParam('memo');


        $website    = $this->ci->get('settings')['website'];

        $config     = \Logic\Set\SystemConfig::getModuleSystemConfig('withdraw');
        $user       = \Model\User::where('id', $userId)
                           ->whereNotIn('tags', $website['notInTags'])
                           ->first();

        if (empty($user)) {
            return $this->lang->set(156);
        }

        $global = \Logic\Set\SystemConfig::getModuleSystemConfig('lottery');

        // 校验提现总开关
        if ($global['stop_withdraw']) {
            return $this->lang->set(9);
        }
        $withdraw_money = $config['withdraw_money'];
        $withdraw_time = $config['withdraw_time'];
        $passwordWithdrawFault = $config['password_withdraw_fault'];
//        $withdrawMin = $withdraw_money['withdraw_min'];
//        $withdrawMax = $withdraw_money['withdraw_max'];
        $withdrawTimeStart = $withdraw_time['withdraw_time_start'];
        $withdrawTimeEnd = $withdraw_time['withdraw_time_end'];
//        $withdrawDayTimes = $config['withdraw_day_times'];

        $ranting = $user['ranting'];

        $level = \Model\UserLevel::where('level',$ranting)->select('welfare','fee')->first();
        if ($level){
            $welfare = $level->welfare;
            $fee     = $level->fee;
        }
        if (!empty($welfare)) $welfare = json_decode($welfare,true);

        $withdrawMin        = $welfare['withdraw_min']  ?? 0;
        $withdrawMax        = $welfare['withdraw_max']  ?? 0;
        $withdrawDayTimes   = $welfare['withdraw_day_times'] ?? 0;
        $daily_withdraw_max = $welfare['daily_withdraw_max'] ?? 0;

        //银行卡单独限额
        $bankUser = \Model\BankUser::where('state', 'enabled')
            ->where('role', 1)
            ->where('user_id', $userId)
            ->where('id', $withdrawCard)
            ->first();
        if (empty($bankUser)) {
            return $this->lang->set(148);
        }
        $bank = \Model\Bank::where('id', $bankUser->bank_id)
            ->first();
        if(!in_array($bank->code, ['Paymaya Philippines, Inc.', 'Gcash'])){
            $withdrawMin = $welfare['bank_withdraw_min'];
            $withdrawMax = $welfare['bank_withdraw_max'];
            if ($withdrawMoney > $withdrawMax && $withdrawMax > 0) {
                return $this->lang->set(194, [$withdrawMax / 100]);
            }

            if ($withdrawMoney < $withdrawMin && $withdrawMin > 0) {
                return $this->lang->set(195, [$withdrawMin / 100]);
            }
        }else{
            if ($withdrawMoney > $withdrawMax && $withdrawMax > 0) {
                return $this->lang->set(158, [$withdrawMax / 100]);
            }

            if ($withdrawMoney < $withdrawMin && $withdrawMin > 0) {
                return $this->lang->set(159, [$withdrawMin / 100]);
            }
        }

        $today = date('Y-m-d');
        $now = time();
        if (strtotime($withdrawTimeEnd) != strtotime($withdrawTimeStart)) {
            if (strtotime($withdrawTimeEnd) >= strtotime($withdrawTimeStart)) {
                if ($now > strtotime($today . " " . $withdrawTimeEnd) || $now < strtotime($today . " " . $withdrawTimeStart)) {
                    return $this->lang->set(157, [$withdrawTimeStart, $withdrawTimeEnd]);
                }
            } else {
                if ($now > strtotime($today . " " . $withdrawTimeEnd) && $now < strtotime($today . " " . $withdrawTimeStart)) {
                    return $this->lang->set(157, [$withdrawTimeStart, $withdrawTimeEnd]);
                }
            }
        }


        $count = \Model\FundsWithdraw::where('user_type', 1)
                                     ->selectRaw('count(*) as count,sum(money) as money')
                                     ->where('created', '>', "$today 00:00:00")
                                     ->whereIn('status',['paid','pending','obligation'])
                                     ->where('user_id', $userId)
                                     ->first()->toArray();
        $sum_money = bcadd($withdrawMoney,$count['money']);
        if ($sum_money > $daily_withdraw_max && $daily_withdraw_max != 0) {
            return $this->lang->set(209, [$daily_withdraw_max], [], []);
        }

        if ($count['count'] >= $withdrawDayTimes && $withdrawDayTimes != 0) {
            return $this->lang->set(160, [$withdrawDayTimes], [], []);
        }

        $dml = new \Logic\Wallet\Dml($this->ci);
        $tmp = $dml->getUserDmlData($userId);
        $dmlData = [
            'factCode' => $tmp->total_bet,
            'codes'    => $tmp->total_require_bet,
            'canMoney' => $tmp->free_money,
            'balance'  => \Model\User::getUserTotalMoney($userId)['lottery'] ?? 0,
        ];

        if ($withdrawMoney > $dmlData['canMoney']) {
            return $this->lang->set(161, [], [], ['w' => $withdrawMoney, 'c' => $dmlData]);
        }

        $authStatus = explode(',', $user['auth_status']);
        if (in_array('refuse_withdraw', $authStatus)) {
            return $this->lang->set(163);
        }

        $funds = \Model\Funds::where('id', $user['wallet_id'])
                             ->first();


        //  回收所有钱包以供提款
        $wid = \Model\User::where('id',$userId)->value('wallet_id');
        $tmp_game = \Model\FundsChild::where('pid',$wid)->where('balance','>',0)->pluck('game_type')->toArray();
        foreach ($tmp_game as $val) {
            $gameClass = \Logic\GameApi\GameApi::getApi($val, $userId);
            $gameClass->rollOutThird();
        }

        $recharge = new \Logic\Recharge\Recharge($this->ci);
        return $recharge->handApply($userId, $withdrawMoney, $withdrawCard, $memo,1,0,'',$fee);
    }
};