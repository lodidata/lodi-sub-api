<?php
use Utils\Www\Action;
return new class extends Action
{
    const TOKEN = true;
    const TITLE = "安全中心-开启安全项";
    const TAGS = "安全中心";
    const SCHEMAS = [
        "id_card" => [
            "status" => "string(required) #1为开启，0为未开启",
            "value" => 'string(required) #身份验证',
        ],
        "bank_card" => [
            "status" => "string(required) #1为已绑定，0为未绑定",
            "value" => 'string(required) #绑定银行卡',
        ],
        "password" => [
            "status" => "string(required) #1为开启，0为未开启",
            "value" => 'string(required) #登录密码',
            "password_reset_need_old" => "string(required) 是否需要校验旧密码 1要 0不要",
        ],
        "withdraw_pwd" => [
            "status" => "string(required) #1为开启，0为未开启",
            "value" => 'string(required) #提款密码',
        ],
        "mobile" => [
            "status" => "string(required) #1为开启，0为未开启",
            "value" => 'string(required) #验证手机号 加密码',
            "real_value" => "string(required) #验证手机号 原值",
        ],
        "email" => [
            "status" => "string(required) #1为开启，0为未开启",
            "value" => 'string(required) #验证邮箱 加密码',
            "real_value" => "string(required) #验证邮箱 原值",
        ],
        "freeze_password" => [
            "status" => "string(required) #1为开启，0为未开启",
            "value" => 'string(required) #保险箱密码',
        ]
   ];

    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $type    = 1;
        $user = \Model\User::where('id', $this->auth->getUserId())->first();
        $telphoneCode = "+86";
        if (isset($user['telphone_code'])) {
            $telphoneCode = $user['telphone_code'];
        }

        $safety = new \Logic\User\Safety($this->ci);

        $mobile = \Model\Profile::getMobile($user['mobile']);
        list($data, $userInfo) = $safety->getList($this->auth->getUserId(), $type);
        $email =  $userInfo['email'];
        

        if (!$data) {
            return $this->lang->set(109);
        }
        /*$var                      = file_get_contents(__DIR__ . '/get.json');
        $var                      = json_decode($var, true);*/
        $var = [
            "id_card"=> [
                "status"=> 0,
                "value"=> $this->lang->text("safety_id_card")
            ],
            "password"=> [
                "status"=> 1,
                "value"=> $this->lang->text("safety_password"),
                "reg"=> "^[~!@#$%^&*()-_=+|[],.?=>;a-zA-Z0-9][6,16]$"
            ],
            "withdraw_pwd"=> [
                "status"=> 0,
                "value"=> $this->lang->text("safety_withdraw_pwd"),
                "reg"=> "^[0-9][4]$"
            ],
            "mobile"=> [
                "status"=> 0,
                "value"=> $this->lang->text("safety_mobile")
            ],
            "bank_card"=> [
                "status"=> 0,
                "value"=> $this->lang->text("bank_card")
            ],
            "email"=> [
                "status"=> 0,
                "value"=> $this->lang->text("safety_email")
            ],
            "freeze_password"=> [
                "status"=> 0,
                "value"=> $this->lang->text("safety_freeze_password")
            ]
        ];

        $var['id_card']['status'] = $data['id_card'];

        if (isset($data['id_card']) && $data['id_card'] == 1) {
            $var['id_card']['value'] = $data['id_card_value'];
        }

        if (!empty($mobile) && $data['mobile'] == 1) {
            $var['mobile']['value'] = $data['mobile_value'];
            $var['mobile']['real_value'] = $mobile;
            $var['mobile']['status']       = 1;
        }

        if (!empty($email) && $data['email'] == 1) {
            $var['email']['value'] = $data['email_value'];
            $var['email']['real_value'] = $email;
            $var['email']['status'] = 1;
        }


        $var['withdraw_pwd']['status'] = $data['withdraw_password'];
        
        
        $tpPasswordInitial = $user['tp_password_initial'];
        $var['password']['password_reset_need_old'] = $tpPasswordInitial ? 0 : 1;// 是否需要验证旧密码,1需要

        $var['telphone_code'] = $telphoneCode;

        $freeze_password = \Model\Admin\Funds::where('id',$user['wallet_id'])->value('freeze_password');
        if($freeze_password) {
            $var['freeze_password']['status'] = 1;
        }
        $bankList = \Model\BankUser::getRecords($user['id']);
        if($bankList){
            $var['bank_card']['status'] = 1;
        }
        return $var;
    }


};
