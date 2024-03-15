<?php

use Utils\Www\Action;
use Respect\Validation\Validator as V;
use Model\UserLog;

return new class extends Action {
    const TOKEN = true;
    const TITLE = "竖版会员提现-会员线上提款申请";
    const TAGS = "充值提现";
    const PARAMS = [
        "withdraw_money" => "int(required) #取款金额",
        "withdraw_pwd"   => "string(required) #取款密码",
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
            'withdraw_pwd'   => V::intVal()
                ->length(4)
                ->noWhitespace()
                ->setName($this->lang->text("withdraw_pwd"))
        ]);

        if (!$validator->isValid()) {
            return $validator;
        }

        $withdrawMoney  = $this->request->getParam('withdraw_money');
        $withdrawPwd    = $this->request->getParam('withdraw_pwd');

        $userId     = $this->auth->getUserId();
        $website    = $this->ci->get('settings')['website'];
        $config     = \Logic\Set\SystemConfig::getModuleSystemConfig('withdraw');
        $user       = \Model\User::where('id', $userId)
            ->whereNotIn('tags', $website['notInTags'])
            ->first();

        if (empty($user)) {
            return $this->lang->set(156);
        }

        $global       = \Logic\Set\SystemConfig::getModuleSystemConfig('lottery');
        $bankcard_id  = \Model\BankUser::getBancardId($userId);

        // 校验提现总开关
        if ($global['stop_withdraw']) {
            return $this->lang->set(9);
        }
        $withdraw_money         = $config['withdraw_money'];
        $withdraw_time          = $config['withdraw_time'];
        $passwordWithdrawFault  = $config['password_withdraw_fault'];
        $withdrawMin            = $withdraw_money['withdraw_min'];
        $withdrawMax            = $withdraw_money['withdraw_max'];
        $withdrawTimeStart      = $withdraw_time['withdraw_time_start'];
        $withdrawTimeEnd        = $withdraw_time['withdraw_time_end'];
        $withdrawDayTimes       = $config['withdraw_day_times'];

        if ($withdrawMoney > $withdrawMax) {
            return $this->lang->set(158, [$withdrawMax / 100]);
        }

        if ($withdrawMoney < $withdrawMin) {
            return $this->lang->set(159, [$withdrawMin / 100]);
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
            ->where('created', '>', "$today 00:00:00")
            ->whereIn('status',['paid','pending','obligation'])
            ->where('user_id', $userId)
            ->count();

        if ($count >= $withdrawDayTimes && $withdrawDayTimes != 0) {
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

        if (\Model\User::getPasword($withdrawPwd, $funds['salt']) != $funds['password']) {
            $fromWheres = ['ios' => 2, 'android' => 1, 'h5' => 3, 'pc' => 4];
            //写入日志
            UserLog::create([
                'user_id'       => $userId,
                'name'          => $user['name'],
                'log_value'     => $this->lang->text("password error"),
                'log_type'      => 2,
                'status'        => 0,
                'platform'      => $fromWheres[$this->auth->getCurrentPlatform()]
            ]);

            if ($passwordWithdrawFault > 0) {
                $errorTimes = $user['withdraw_error_times'] + 1;
                if ($errorTimes >= $passwordWithdrawFault) {
                    //  冻结+离线
                    //  \Model\User::where('id', $userId)->update(['online' => 0, 'state' => 0, 'withdraw_error_times' => DB::raw('ifnull(withdraw_error_times,0) + 1')]);
                    \Model\User::where('id', $userId)
                        ->update(['withdraw_error_times' => DB::raw('ifnull(withdraw_error_times,0) + 1'), 'auth_status' => DB::raw("auth_status|1")]);
                    //  $this->auth->logout($this->auth->getUserId());
                    //  return $this->lang->set(164, [$errorTimes]);
                    return $this->lang->set(2117, [$errorTimes]);
                } else {
                    \Model\User::where('id', $userId)
                        ->update(['withdraw_error_times' => DB::raw('ifnull(withdraw_error_times,0) + 1')]);
                    //  return $this->lang->set(165, [$errorTimes, $passwordWithdrawFault]);
                    return $this->lang->set(2118, [$errorTimes, $passwordWithdrawFault]);
                }
            }

            return $this->lang->set(154);
        }

        //  回收所有钱包以供提款
        $wid = \Model\User::where('id',$userId)->value('wallet_id');
        $tmp_game = \Model\FundsChild::where('pid',$wid)->where('balance','>',0)->pluck('game_type')->toArray();
        foreach ($tmp_game as $val) {
            $gameClass = \Logic\GameApi\GameApi::getApi($val, $userId);
            $gameClass->rollOutThird();
        }

        $recharge = new \Logic\Recharge\Recharge($this->ci);

        return $recharge->handApply($userId, $withdrawMoney, $bankcard_id, '');
    }
};