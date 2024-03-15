<?php

use Logic\Recharge\Recharge;
use Model\User;
use Utils\Www\Action;

return new class extends Action {
    const TOKEN   = true;
    const TITLE   = '获取yespay收款账号并注册用户';
    const TAGS    = "充值提现";
    const SCHEMAS = [
        'bank_code'   => 'string #银行卡简码',
        'bank_number' => 'string #银行卡号',
        'bank_name'   => 'string #银行卡账号名称',
        'bank_logo'   => 'string #银行图片地址',

    ];

    public function run()
    {
        $verify = $this->auth->verfiyToken();

        if (!$verify->allowNext()) {
            return $verify;
        }

        $userId = $this->auth->getUserId();
        $payType = 'yespay';

        $pay = new Logic\Recharge\Recharge($this->ci);
        $obj = $pay->getThirdClass($payType);

        // 获取用户信息
        $userInfo = (array)\DB::table('bank_user')
            ->leftjoin('bank', 'bank_user.bank_id', '=', 'bank.id')
            ->where('bank_user.role', 1)
            ->where('bank_user.user_id', $userId)
            ->selectRaw('bank_user.card, bank.code AS bank_code, bank_user.user_id')
            ->orderBy('bank_user.id', 'desc')
            ->first();
        if (empty($userInfo)) {
            $this->lang->set(184);
            return false;
        }
        if (!empty($userInfo)) {
            $userInfo = \Utils\Utils::RSAPatch($userInfo);
        }
        //注册第三方
        $reg_res = $obj->register($userInfo);
        if (!$reg_res) {
            return $this->lang->set(886, [$this->lang->text('Failed to register yespay account')]);
        }

        // 请求支付获取银行卡信息
        $obj->orderID = date("YmdHis") . rand(pow(10, 3), pow(10, 4) - 1);
        $obj->rechargeType = 'ThaiAutoBilling';
        $obj->money = 10000;
        $obj->config();
        $obj->start();

        if ($obj->return['code'] !== 0) {
            return $this->lang->set(10001);
        }

        return [$obj->return['bank']];
    }
};
