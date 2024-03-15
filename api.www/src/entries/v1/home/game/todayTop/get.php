<?php

use Utils\Www\Action;
use Model\GameMenu;
use Model\Favorites;

return new class extends Action
{
    const TITLE = "今日热门 特别游戏 受欢迎游戏 最新游戏(竖版)";
    const DESCRIPTION = "从电子游戏里取10个 每日一换(竖版)";
    const TAGS = '首页';
    const QUERY = [

    ];
    const SCHEMAS = [
        "today_top" => [
            [
                "id"        => "int(required) #ID 16",
                "img"       => "string() #游戏图片地址",
                "url"       => "string(required) #固定跳转进入第三方/game/third/app?play_id=551",
                "quit_url"  => "string(required) #固定跳转退出第三方/game/third/quit?play_id=551",
                "favorite"  => "int(required) 1：已收藏，0:未收藏"
            ]
        ],
        "feature" => [
            [
                "id"        => "int(required) #ID 16",
                "img"       => "string() #游戏图片地址",
                "url"       => "string(required) #固定跳转进入第三方/game/third/app?play_id=551",
                "quit_url"  => "string(required) #固定跳转退出第三方/game/third/quit?play_id=551",
                "favorite"  => "int(required) 1：已收藏，0:未收藏"
            ]
        ],
        "popular" => [
            [
                "id"        => "int(required) #ID 16",
                "img"       => "string() #游戏图片地址",
                "url"       => "string(required) #固定跳转进入第三方/game/third/app?play_id=551",
                "quit_url"  => "string(required) #固定跳转退出第三方/game/third/quit?play_id=551",
                "favorite"  => "int(required) 1：已收藏，0:未收藏"
            ]
        ],
        "new" => [
            [
                "id"        => "int(required) #ID 16",
                "img"       => "string() #游戏图片地址",
                "url"       => "string(required) #固定跳转进入第三方/game/third/app?play_id=551",
                "quit_url"  => "string(required) #固定跳转退出第三方/game/third/quit?play_id=551",
                "favorite"  => "int(required) 1：已收藏，0:未收藏"
            ]
        ],
    ];

    public function run()
    {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $user_id        = $this->auth->getUserId();
        $time           = time();
        $redis_key      = \Logic\Define\CacheKey::$perfix['todayTopGameList'].date('Y-m-d',$time);
        $date           = date('Y-m-d H:i:s', $time);
        $data2          = $this->redis->get($redis_key);
        $favorite_list  = Favorites::getGameIdList($user_id);
        $game_list_type = ['today_top', 'feature', 'popular', 'new'];

        if (!empty($data2) ) {
            $res = json_decode($data2, true);
            //判断是否收藏
            foreach ($game_list_type as $type_name){
                if(empty($res[$type_name])) continue;

                foreach ($res[$type_name]  as &$item){
                    $favorite = 0;
                    if(in_array($item['id'], $favorite_list)){
                        $favorite = 1;
                    }
                    $item['favorite'] = $favorite;
                    $item['img'] = showImageUrl(item['img']);
                }
                unset($item);
            }

            return $res;
        }

        //电子游戏
        $menu_slot_id_list = DB::table('game_menu')
            ->select(['id'])
            ->where('status', 'enabled')
            ->where('switch', 'enabled')
            ->where('pid', 4)
            ->get()
            ->toArray();
        if($menu_slot_id_list){
            $menu_slot_id_list = array_column($menu_slot_id_list,'id');
        }else{
            $menu_slot_id_list = [];
        }

        $newGameIdList = DB::table('game_3th')
            ->where('status', '=', 'enabled')
            ->whereIn('game_id', $menu_slot_id_list)
            ->get(['id'])
            ->toArray();

        $newGameIdList && $newGameIdList = GameMenu::getRondomGameIdList($newGameIdList,40);

        $newGameList = DB::table('game_3th')
            ->select(['id', 'game_img as img'])
            ->whereIn('id', $newGameIdList)
            ->orderBy('sort')
            ->get()
            ->toArray();


        $jump_url  = '/game/third/app?';  // 固定跳转进入第三方
        //$quit_url  = '/game/third/quit?';  // 固定跳转退出第三方
        // 第三方游戏
        foreach ($newGameList ?? [] as &$v){
            $v->url      = $jump_url . 'play_id=' . $v->id;
            //$v->quit_url = $quit_url . 'play_id=' . $v->id;
        }
        unset($v);

        $new_game_list = [];
        while($newGameList){
            $newGameList && $new_game_list['today_top'][]   = array_shift($newGameList);
            $newGameList && $new_game_list['feature'][]     = array_shift($newGameList);
            $newGameList && $new_game_list['popular'][]     = array_shift($newGameList);
            $newGameList && $new_game_list['new'][]         = array_shift($newGameList);
        }

        $this->redis->setex($redis_key, 60*60*24, json_encode($new_game_list));

        //判断是否收藏
        foreach ($game_list_type as $type_name){
            foreach ($new_game_list[$type_name] ?? [] as &$item){
                $favorite = 0;
                if(in_array($item->id, $favorite_list)){
                    $favorite = 1;
                }
                $item->favorite = $favorite;
                $item->img = showImageUrl($item->img);
            }
            unset($item);
        }

        return $this->lang->set(0, [], $new_game_list);
    }

};
