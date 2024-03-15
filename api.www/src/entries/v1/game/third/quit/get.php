<?php

use Utils\Www\Action;

return new class extends Action {
    const TOKEN = true;
    const TITLE = "退出第三方游戏";
    const TAGS = '游戏';
    const QUERY = [
        "play_id" => "int(required) #游戏菜单ID",
        //"game_id" => "int() #第三方对应的具体小游戏的id",
    ];
    const SCHEMAS = [
        "quit" => "boolean(required) #是否退出成功 true是 false否",
        "rollOut" => [
            "status" => "boolean(required) #是否退出成功 true是 false否",
            "thirdBalance"  => "float(required) #第三金额 5.67",
            'sum_balance' => "float(required) #总金额 1000",
            'available_balance' => "float(required) #可用金额 800",
        ]
    ];

    public function run() {
        $verify = $this->auth->verfiyToken();

        if (!$verify->allowNext()) {
            return $verify;
        }

        $uid = $this->auth->getUserId();
//        $uid = 94;
        $play_id = $this->request->getParam('play_id');
        $game_id = $this->request->getParam('game_id');
        $play_games = \Model\Game3th::where('id',$play_id)->first();
        if(!$play_games && !$game_id) {
            return $this->lang->set(3010);
        }
        $game_id = $game_id ? $game_id : $play_games['game_id'];
        try {
            $games = (array)\DB::table('game_menu')->where('id', $game_id)->first();
            $gameClass = \Logic\GameApi\GameApi::getApi($games['type'], $uid);
            // 退出游戏
            $res = $gameClass->quitGame();
            // 下分
            $b = $gameClass->rollOutThird();
            //var_dump($b);die;
            // 获取主钱包
            $wid = \Model\User::where('id',$uid)->value('wallet_id');
            $funds = \Model\Funds::where('id',$wid)->first(['balance','freeze_money']);
            $child_balace = \Model\FundsChild::where('pid',$wid)->where('status','enabled')->sum('balance');
            $b['thirdBalance'] = isset($b['thirdBalance']) ? (int)$b['thirdBalance'] : 0;
            $b['sum_balance'] = $funds['balance'] + $child_balace;
            $b['available_balance'] = $funds['balance'];
            return $this->lang->set(0,[],['quit'=>$res,'rollOut'=>$b]);
        }catch (\Exception $e) {
            return $this->lang->set(3011);
        }
    }
};