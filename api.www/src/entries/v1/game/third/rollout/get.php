<?php

use Utils\Www\Action;

return new class extends Action {
    const HIDDEN = true;
    const TOKEN = true;
    const TITLE = "回收第三方游戏金额";
    const TAGS = '游戏';
    const QUERY = [
        "game_id" => "int(required) #第三方对应的游戏的id",
    ];
    const SCHEMAS = [
        'sum_balance' => "float(required) #总金额 1000",
        'available_balance' => "float(required) #可用金额 800",
    ];

    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $uid = $this->auth->getUserId();
//        $uid = 94;
        $game_id = $this->request->getParam('game_id');
        if(!$game_id) {
            return $this->lang->set(3010);
        }
        try {
            $games = (array)\DB::table('game_menu')->where('switch','enabled')->where('id', $game_id)->first();
            if(!$games) {
                return $this->lang->set(3010);
            }
            $gameClass = \Logic\GameApi\GameApi::getApi($games['type'], $uid);
            // 回收第三方
            $b = $gameClass->rollOutThird();
            // 钱包余额
            $wid = \Model\User::where('id',$uid)->value('wallet_id');
            $funds = \Model\Funds::where('id',$wid)->first(['balance','freeze_money']);
            $b['sum_balance'] = $funds['balance'];
            $b['available_balance'] = $funds['balance'] - $funds['freeze_money'];
            return $this->lang->set(0,[],$b);
        }catch (\Exception $e) {
            return $this->lang->set(3011);
        }
    }
};