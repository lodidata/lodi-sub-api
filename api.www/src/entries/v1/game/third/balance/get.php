<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/4/23
 * Time: 17:16
 */

use Utils\Www\Action;

return new class extends Action {
    const TOKEN = true;
    const TITLE = "回收第三方金额";
    const TAGS = '游戏';
    const QUERY = [
        "game_type" => "string() #游戏类型 JOKER",
    ];
    const SCHEMAS = [
        'freeMoney' => "float(required) #可下分金额 10.01",
        'balance' => "float(required) #总金额 100.10"
    ];

    public function run() {
        $verify = $this->auth->verfiyToken();

        if (!$verify->allowNext()) {
            return $verify;
        }
        $uid = $this->auth->getUserId();
        $game_type = $this->request->getParam('game_type');
        if (!$game_type) {
            return $this->lang->set(0, [], [
                    'freeMoney' => 0,   //可下分金额
                    'balance' => 0,
                ]
            );
        }
        try {
            $gameClass = \Logic\GameApi\GameApi::getApi($game_type, $uid);
            // 下分
            list($freeMoney, $totalMoney) = $gameClass->getThirdBalance();
            if($totalMoney) {
                $wid = \Model\User::where('id',$uid)->value('wallet_id');
                \Model\FundsChild::where('pid',$wid)->where('game_type',$game_type)->update(['balance' => $totalMoney]);
            }
            $gameClass->rollOutThird($freeMoney);
            return $this->lang->set(0, [], [
                'freeMoney' => $freeMoney,  // 可下分金额
                'balance' => $totalMoney    // 总金额
            ]);
        } catch (Exception $e) {
            return $this->lang->set(0, [], [
                'freeMoney' => 0,
                'balance' => 0,
            ]);
        }
    }
};
