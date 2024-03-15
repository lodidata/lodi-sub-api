<?php

use Utils\Www\Action;
/**
 * 个人报表：棋牌报表、电子报表、捕鱼报表、视讯报表、体育报表
 *
 */
return new class extends Action
{
    const TOKEN = true;
    const TITLE = "个人报表-获取个人报表";
    const DESCRIPTION = "个人报表：棋牌报表、电子报表、捕鱼报表、视讯报表、体育报表";
    const TAGS = "个人报表";
    const QUERY = [
        "start_time" => "string(required) #查询开始日期 2019-09-12",
        "end_time"   => "string(required) #查询结束日期  2019-09-12",
        "game_id"    => "int() #游戏ID 默认为17",
    ];
    const SCHEMAS = [
        'bet'       => "int(required) #下注金额",
        'rebet'     => "int(required) #返点金额（回水）",
        'profit'    => "int(required) #盈利",
        "send_money"=> "int(required) #派奖金额"
    ];

    public function run()
    {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $userId = $this->auth->getUserId();
        $gameId = $this->request->getParam('game_id', 17);
        $start_time=$this->request->getParam('start_time',date('Y-m-d'));
        $end_time=$this->request->getParam('end_time',date('Y-m-d'));
        if($start_time>$end_time){
            return $this->lang->set(886,[$this->lang->text("Start time cannot be greater than end time!")]);
        }

        //获取菜单
        $gameTypeArr = DB::table('game_menu')
            ->select('type')
            ->where('switch','enabled')
            ->where('pid', '=', $gameId)
            ->get()
            ->toArray();
        $arr = [];
        foreach ($gameTypeArr as $key => $item) {
            $item = (array)$item;
            $arr[$key] = $item['type'];
        }

        //获取回水信息
        $rebet=DB::table('rebet')
            ->selectRaw('SUM(rebet) as rebet')
            ->where('rebet.user_id', '=', $userId)
            ->whereBetween('day',[$start_time,$end_time])
            ->whereIn('type', $arr)
            ->get()
            ->first();
        $rebet=(array)$rebet;

        //获取其他信息
        /**
         * bet:下注金额
         * rebet:返点金额（回水）
         * profit：盈利
         * send_money：派奖金额
         *
         */
        $data = \DB::table('order_game_user_middle')
            ->selectRaw('SUM(bet) as bet,SUM(profit) as profit ,SUM(send_money) as send_money')
            ->where('user_id', '=', $userId)
            ->whereBetween('date',[$start_time,$end_time])
            ->whereIn('game_type', $arr)
            ->get()
            ->first();
        $data=(array)$data;
        $data['rebet']=$rebet['rebet']*100;
        return $this->lang->set(0, [], $data);
    }

};