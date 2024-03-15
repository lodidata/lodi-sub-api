<?php

use Utils\Www\Action;
use Logic\Activity\Activity;

return new class extends Action
{
    const TOKEN = true;
    const TITLE = "POST 会员注单";
    const DESCRIPTION = "会员注单";
    const TAGS = "彩票";
    const PARAMS = [
        [
            "lottery_id"     => "int() #彩票id [快速玩法提交订单格式]",
            "room_id"        => "int() #房间ID",
            "origin"         => "int() #来源1pc 2h5 3APP",
            "lottery_number" => "string() #彩票期号",
            "pid"            => "int() #彩票父级id",
            "play"           => [
                [
                    "id"    => "int() #彩玩法id",
                    "num"   => "string() #选号",
                    "price" => "int() #投注额",
                    "times" => "int() #倍投",
                ],
            ],
            "chase"          => [
                "chase_type" => "int(required)#(1为中奖不停止 2为中奖停止)",
                "chase_list" => [
                    [
                        "lottery_number" => "int() #期号",
                        "times"          => "int() #赔率",
                    ],
                ],
            ],
        ],
    ];
    const SCHEMAS = [
         [
            "id"              => "1",
            "uuid"            => "149032155258d480902d0a3",
            "name"            => "sam的钱包",
            "balance"         => "56516",
            "balance_before"  => "56536",
            "freeze_withdraw" => "0",
            "freeze_append"   => "0",
            "currency"        => "CNY",
            "updated"         => "2017-04-10 15:45:03",
            "comment"         => "sam的主钱包",
            "children"        => [
                [
                    "id"           => "2",
                    "uuid"         => "149032183258d481a87184a",
                    "name"         => "BBIN子钱包",
                    "game_type"    => "BBIN",
                    "balance"      => "0",
                    "last_updated" => "2017-03-24 10:28:10",
                ],
                [
                    "id"           => "1",
                    "uuid"         => "149032179458d48182006b5",
                    "name"         => "MG子钱包",
                    "game_type"    => "MG",
                    "balance"      => "10",
                    "last_updated" => "2017-03-24 10:27:39",
                ],
            ],
        ],
    ];


    protected $state = 13;

    protected $stateParams = [];

    public function run() {
        $verify = $this->auth->verfiyToken();

        if (!$verify->allowNext()) {
            return $verify;
        }

        $userId = $this->auth->getUserId();
        $lang = \Utils\Client::getApiProtectByUser($userId, $tags = 'addOrder');

        if ($lang->getState()) {
            return $lang;
        }

        if ($this->auth->isPcPlatform()) {
            $origin = 1;
        } else if ($this->auth->isH5Platform()) {
            $origin = 2;
        } else {
            $origin = 3;
        }
        if($this->auth->getTrialStatus()){
            //试玩投注
            return (new \Logic\Lottery\Order($this->ci))->addTrialOrder($userId, $this->request->getParam('data'), $origin);
        }else{
            //判断能否玩所有游戏
            if(!Activity::canPlayAllGame($userId)){
                return $this->lang->set(886,[$this->lang->text("You can't play all game")]);
            }
            //  回收所有钱包以供下注
            $wid = \Model\User::where('id',$userId)->value('wallet_id');
            $tmp_game = \Model\FundsChild::where('pid',$wid)->where('balance','>',0)->pluck('game_type')->toArray();
            foreach ($tmp_game as $val) {
                $gameClass = \Logic\GameApi\GameApi::getApi($val, $userId);
                $gameClass->rollOutThird();
            }

            $data = (new \Logic\Lottery\Order($this->ci))->addOrder($userId, $this->request->getParam('data'), $origin);
            if(isset($data->lang->get()[1]) && $data->lang->get()[1] != 0){
                return $data;
            }
            $code = isset($data->lang->get()[1]) ? $data->lang->get()[1] : 0;
            $user = \DB::table('user')->where('id',$userId)->first();
            $user_login = new Logic\User\User($this->ci);
            $user_login->upgradeLevelMsg((array)$user);
            $upgrade = $user_login->upgradeLevelWindows($userId);
            return $this->lang->set($code,[],[],['upgrade'=>$upgrade]);
//            return (new \Logic\Lottery\Order($this->ci))->addOrder($userId, $this->request->getParam('data'), $origin);
        }
    }
};