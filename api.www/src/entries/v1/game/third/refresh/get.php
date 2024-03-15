<?php

use Utils\Www\Action;

return new class extends Action {
    const TOKEN = true;
    const TITLE = "回收第三方所有钱包金额（一键回收）";
    const TAGS = '游戏';
    const SCHEMAS = [
        'sum_balance' => "float(required) #总金额 1000",
        'available_balance' => "float(required) #可用金额 800",
        'freeze_money' => "float(required) #冻结金额 200",
        'take_balance' => "float(required) #可提余额 100",
        'today_profit' => "float(required) #今天收益 10.01",
        'child' => [
            [
                'uuid'      => 'string() #uuid 游戏唯一标识',
                "game_type" => "string() #游戏类型 KAIYUAN",
                "name"      => "string() #游戏名称",
                'freeMoney' => "float(required) #可下分金额 10.01",
                'balance'   => "float(required) #总金额 100.10"
            ]
        ]
    ];

    public function run() {
        $verify = $this->auth->verfiyToken();

        if (!$verify->allowNext()) {
            return $verify;
        }

        $uid = $this->auth->getUserId();
//         $uid = 4;
        try {
            // 回收所有第三方
            \Logic\GameApi\GameApi::rollOutAllThird($uid);
            // 查询钱包
            $wid    = \Model\User::where('id',$uid)->value('wallet_id');
            $funds  = \Model\Funds::where('id',$wid)->first();
            $list   = \Model\FundsChild::where('pid',$wid)->where('status','enabled')->get()->toArray();
            $child  = [];
            $total_third_money = 0; //第三方总金额

            foreach ($list as $val) {
                $balance          = $val['balance'];
                $tmp['uuid']      = $val['uuid'];
                $tmp['game_type'] = $val['game_type'];
                $tmp['name']      = $val['name'];
                $tmp['balance']   = $balance;
                $child[]          = $tmp;
                // 总金额累计
                $total_third_money += $balance;
            }

            $res = [
                'sum_balance'       => $funds['balance'] + $total_third_money,
                'available_balance' => $funds['balance'],
                'freeze_money'      => $funds['freeze_money'],
                'take_balance'      => \DB::table('user_data')->where('user_id',$uid)->value('free_money'),
            ];
            $res['child'] = $child;
            return $res;
        }catch (\Exception $e) {
            return $this->lang->set(3011);
        }
    }
};