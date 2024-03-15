<?php

use Utils\Www\Action;
use Logic\Activity\Activity;
return new class extends Action {
    const TOKEN = true;
    const TITLE = "登录第三方游戏";
    const TAGS = '游戏';
    const QUERY = [
        "play_id" => "int(required) #游戏菜单ID",
        //"game_id" => "int() #第三方对应的具体小游戏的id",
    ];
    const SCHEMAS = [
        'status'    => "int(required) #状态 0开启",
        'url'       => "string(required) #游戏跳转链接",
        "isBrowser" => "boolean() #默认浏览器，因为android可能不支持的游戏"
    ];

    public function run() {

        $verify = $this->auth->verfiyToken();

        if (!$verify->allowNext()) {
            return $verify;
        }

        $allow = \Logic\Set\SystemConfig::getModuleSystemConfig('gameCon');

        if(!isset($allow['allow_in']) || $allow['allow_in'] != 1) {
            return $this->lang->set(3014);
        }

        //$data=(new \Logic\Set\SystemConfig($this->ci))->getStartGlobal();

        $uid = $this->auth->getUserId();
        $u_tags = \Model\User::where('id',$uid)->value('tags');
        if(in_array($u_tags, [4,7])) {
            return $this->lang->set(3013);
        }

        $play_id = $this->request->getParam('play_id');
        $game_id = $this->request->getParam('game_id');
        $play_games = \Model\Game3th::where('id', $play_id)->where('status','enabled')->first();
        if (!$play_games && !$game_id) {
            return $this->lang->set(3010);
        }
        $play_games = $play_games ? $play_games->toArray() : [];
        if (isset($play_games['maintain']) && $play_games['maintain'] == 'maintenance') {
            return $this->lang->set(3012);
        }

        //如果不是电子游戏 判断能否玩
        $game_type = strtolower($play_games['type']);
        if(!in_array($game_type,['slot','rng'])){
            if(!Activity::canPlayAllGame($uid)){
                return $this->lang->set(886,[$this->lang->text("You can't play all game")]);
            }
        }

        $game_id = $game_id ? $game_id : $play_games['game_id'];
        try {
            $games = (array)DB::table('game_menu')
                ->where('id', $game_id)
                ->where('switch', "=", 'enabled')
//                ->whereRaw('m_end_time < NOW()')
//                ->whereRaw('m_end_time < NOW()')
                ->first();//var_dump($games);die;
            // 游戏维护中
            $time = time();
            if (count($games) == 0 || $time <= strtotime($games['m_end_time'])  && $time >= strtotime($games['m_start_time'])) {
                return $this->lang->set(3012,[$games['m_start_time'],$games['m_end_time']]);
            }
            /*$quota=DB::table('quota')
                ->select('surplus_quota')
                ->get()
                ->first();
            if($quota){
                if($quota->surplus_quota<=0){
                    return $this->lang->set(886,[$this->lang->text("The third party exchange quota has been used up, please contact the administrator!")]);
                }
            }*/


            //进入游戏前判断注册限制
            $date = date("Y-m-d H:i:s");
            $active=(array)DB::table('active as a')
                      ->leftJoin('active_rule as r','a.id','=','r.active_id')
                      ->where('a.begin_time','<',$date)
                      ->where('a.end_time','>',$date)
                      ->where('a.status','enabled')
                      ->where('a.type_id','5')
                      ->get(['a.id','a.name','r.game_type','r.limit_value'])
                      ->first();
            if(!empty($active)) {
                if ($games['pid'] == 0) {
                    $type = $games['type'];
                } else {
                    $game = (array)DB::table("game_menu")->where('id', $games['pid'])->value('type');
                    $type = $game[0];
                }
                $res = DB::table('active_register')
                    ->where('user_id', $uid)
                    ->where('active_id', $active['id'])
                    ->where('status', 1)
                    ->first();
                if (!empty($active['game_type']) && !empty($active['limit_value']) && !empty($res)) {
                    if (strpos($active['game_type'],',') !== false) {
                        $gameType = explode(',', $active['game_type']);
                    } else {
                        $gameType[] = $active['game_type'];
                    }
                    $limitValue = explode(',', $active['limit_value']);
                    $orderMoney = DB::table('order_game_user_middle')
                        ->where('user_id', $uid)
                        ->sum('bet');

                    $rechargeMoney = DB::table('funds_deposit')
                        ->where('user_id', $uid)
                        ->where('status', 'paid')
                        ->sum('money');
                    //条件满足，解除限制
                    if ($orderMoney >= $limitValue[0] && $rechargeMoney >= $limitValue[1]) {
                        DB::table('active_register')->where('user_id', $uid)
                            ->where('active_id', $active['id'])
                            ->where('game_type', $type)
                            ->update(['status' => 2]);
                    } else {
                        if (!in_array($type, $gameType)) {
                            return $this->lang->set(190);
                        }
                    }

                }
            }

            //进入游戏限制：查询用户最近一条领取彩金记录，再判断领取彩金时间后是否达到彩金活动指定的充值要求，满足充值要求解除进入游戏限制
            $handsel = (array)DB::table('handsel_receive_log')->whereRaw('user_id=? and status=?',[$uid,1])
                ->selectRaw('receive_time,limit_game,recharge_num')->orderBy('id','desc')->first();
            if ($handsel) {
                //判断当前进入的游戏分类是否为彩金活动指定的游戏分类
                $limitGame = explode(',',$handsel['limit_game']);
                $in_game = (array)DB::table("game_menu")->whereIn('type',$limitGame)->whereRaw('id=?',[$games['pid']])->first();
                if (empty($in_game) && $handsel['recharge_num'] > 0) {
                    //查询用户领取彩金后是否满足彩金活动指定的充值金额
                    $recMoney = DB::table('funds_deposit')->whereRaw('user_id=? and status=? and recharge_time>=?',[$uid,'paid',$handsel['receive_time']])->sum('money');
                    if ($recMoney < $handsel['recharge_num']) {
                        return $this->lang->set(201);
                    }
                }
            }

            //进入游戏前回收余额

            $gamesLastCache = $this->redis->get(\Logic\Define\Cache3thGameKey::$perfix['gameUserCacheLast'] . $uid);
            if(!is_null($gamesLastCache)){
                $gamesLastCache = json_decode($gamesLastCache,true);
                //同游戏不转出
                if($gamesLastCache['alias'] != $games['alias']){
                    try{
                        $gameClass = \Logic\GameApi\GameApi::getApi($gamesLastCache['alias'], $uid);
                        $out_res = $gameClass->rollOutThird(0, null, 1);
                    }catch (\Exception $e){
                        $this->logger->error('【转出余额失败】 play_id-'.$play_id.':'.$e->getMessage());
                    }

                    /*if(!$out_res['status']){
                        return $this->lang->set(886, ['_'.$out_res['msg']]);
                    }*/
                }
            }
            $gameClass = \Logic\GameApi\GameApi::getApi($games['type'], $uid);
            if(is_object($gameClass)){
                $jumpUrl = $gameClass->getJumpUrl($play_games);
                if ($jumpUrl['status'] === 0) {
                    unset($jumpUrl['status']);
                    $t = [
                        'alias' => $games['alias'],
                        'game_type' => $games['type'],
                    ];
                    $this->redis->set(\Logic\Define\Cache3thGameKey::$perfix['gameUserCacheLast'].$uid,json_encode($t));

                    // 默认浏览器，因为android可能不支持的游戏
                    if ($games['id'] == 46 || $games['id'] == 51){
                        $jumpUrl['isBrowser'] = true;
                    }

                    return $this->lang->set(0, [], $jumpUrl);
                }
            }
            return $this->lang->set(886, ['--'.$jumpUrl['message']]);
        } catch (\Exception $e) {
            $this->logger->error('【进入游戏】 play_id-'.$play_id.':'.$e->getMessage());
            return $this->lang->set(3011);
        }
    }
};