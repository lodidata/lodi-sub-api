<?php
use Utils\Www\Action;
return new class extends Action {
    const TOKEN = true;
    const TITLE = "竖版会员提现-获取线上提款信息";
    const TAGS = "充值提现";
    const SCHEMAS = [
        "balance" => "int(required) #主钱包余额",
        "total_bet"=> "int(required) #总打码量",
        "require_bet"=> "int(required) #需要的打码量",
        "withdraw_money" => [
            "min" => "int(required) #最小取款金额",
            "max" => "int(required) #最大取款金额"
        ],
        "withdraw_card" => [
            "id" => "int(required) #银行卡id",
            "bank_name" => "string(required) #银行名称",
            "card_number" => "string(required) #银行卡号"
        ],
        "info"   => [
            "counter_fee" => "int(required) #打码量",
            "government_fee" => "int(required) #行政费",
            "Discount" => "int(required) #优惠"
        ]
    ];

    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $userId = $this->auth->getUserId();

        $data   = \Model\BankUser::getRecords($userId);
        $config = \Logic\Set\SystemConfig::getModuleSystemConfig('withdraw');
        $wallet = new \Logic\Wallet\Wallet($this->ci);
        $walletInfo = $wallet->getWalletInfo($userId);
        $userModel=new Logic\User\User($this->ci);
        $output = [
            'balance' => $walletInfo['take_balance'],
            'total_bet'=>$walletInfo['total_bet'],
            'require_bet'=>$walletInfo['require_bet'],
            'withdraw_verify'=>$userModel->withdrawVerify($userId),
            'phone'=>\Utils\Utils::RSADecrypt(DB::table('user')->where('id',$userId)->value('mobile')),
            // 'name' => $user['true_name'],
            'withdraw_money' => [
                'min' => $config['withdraw_money']['withdraw_min'],
                'max' => $config['withdraw_money']['withdraw_max'],
                //'times' => $config['withdraw_day_times'],
                //'start' => $config['withdraw_time']['withdraw_time_start'],
                //'end' => $config['withdraw_time']['withdraw_time_end']
            ],
            'withdraw_card' =>[

            ],
            "info" => [
                "counter_fee" => "12",
                "government_fee" => "22",
                "Discount" => "0"
            ]
        ];
        $req = 1;
        if($req){

            $output['info'] = [
                "counter_fee" => 0,
                "government_fee" => 0,
                "Discount" => 0,
            ];
            $output['withdraw_money']=[
                'min' => $config['withdraw_money']['withdraw_min'],
                'max' => $config['withdraw_money']['withdraw_max'],
                'times' => $config['withdraw_day_times'],
                'start' => $config['withdraw_time']['withdraw_time_start'],
                'end' => $config['withdraw_time']['withdraw_time_end']
            ];
        }
        if (!empty($data)) {
            foreach ($data as $v) {
                if($v['state'] == 'enabled'){
                    $output['withdraw_card'] = [
                        'id'            => $v['id'],
                        'code'          => $v['short_name'],
                        'h5_logo'       => showImageUrl($v['h5_logo']),
                        'logo'          => showImageUrl($v['logo']),
                        'shortname'     => $v['shortname'],
                        'bank_name'     => $this->lang->text($v['short_name']),
                        'name'          => $v['name'],
                        'card'          => $v['account'],
                    ];
                }
            }
            return $output;
        } else {
            $output['withdraw_card'] = [];
            return $output;
        }


    }
};