<?php

use Model\User;
use Utils\Www\Action;

return new class extends Action {
    const TOKEN = true;
    const TITLE = '获取AutoTopUp收款账号并注册用户';
    const TAGS = "充值提现";
    const SCHEMAS = [
            'bank_code'        => 'string #银行卡简码 KBANK',
            'bank_number'   => 'string #银行卡号',
            'bank_name'      => 'string #银行卡账号名称',
            'bank_logo'      => 'string #银行图片地址',

    ];

    public function run() {
        $verify = $this->auth->verfiyToken();

        if (!$verify->allowNext()) {
            return $verify;
        }

        //测试环境模拟
        if(RUNMODE == 'dev'){
            return [
                ["bank_code" => "SCB",
                    "bank_number" => "0062930584",
                    "bank_name" => "ธนพล อินทร์เเจ้ง",
                    "bank_logo" =>"https://update.a1jul.com/kgb/bank/SCB.png"
                ]
            ];
        }

        $user_id = $this->auth->getUserId();
        $userInfo = \DB::table('bank_user')
            ->leftjoin('bank', 'bank_user.bank_id', '=', 'bank.id')
            ->leftjoin('user', 'bank_user.user_id', '=', 'user.id')
            ->where('bank_user.role', 1)
            ->where('bank_user.user_id',$user_id)
            ->selectRaw('bank_user.card, user.name as username, bank.code AS bank_code, user.mobile')
            ->get()
            ->toArray();
        if (empty($userInfo)) {
            $this->lang->set(184);
            return false;
        }
        if (!empty($userInfo)) {
            $userInfo = \Utils\Utils::RSAPatch($userInfo);
        }

        $userInfo = $userInfo[0];

        $pay = new Logic\Recharge\Recharge($this->ci);
        $obj = $pay->getThirdClass('autotopup');
        //注册第三方
        $reg_res = $obj->register($userInfo);
        if (!$reg_res) {
            return $this->lang->set(886, [$this->lang->text('Failed to register autotopup account')]);
        }

        //获取收款方
        $res_bank = $obj->bankAuto($userInfo);
        if(!$res_bank){
            return $this->lang->set(10001);
        }
        //查银行图片
        foreach($res_bank as $key => $val){
            $res_bank[$key]['bank_logo'] = \Model\Bank::where('code', $val['bank_code'])->value('h5_logo');
        }
        return $res_bank;
    }
};
