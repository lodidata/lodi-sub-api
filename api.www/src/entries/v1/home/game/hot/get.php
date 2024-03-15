<?php

use Utils\Www\Action;
use Model\GameMenu;
use Model\Favorites;

return new class extends Action
{
    const TITLE = "热门游戏 最新游戏 大奖游戏 免费旋转 热门捕鱼 (竖版)";
    const DESCRIPTION = "从电子游戏里取12个 每日一换(竖版)";
    const TAGS = '首页';
    const QUERY = [

    ];
    const SCHEMAS = [
        "hot" => [
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
        "big_prize" => [
            [
                "id"        => "int(required) #ID 16",
                "img"       => "string() #游戏图片地址",
                "url"       => "string(required) #固定跳转进入第三方/game/third/app?play_id=551",
                "quit_url"  => "string(required) #固定跳转退出第三方/game/third/quit?play_id=551",
                "favorite"  => "int(required) 1：已收藏，0:未收藏"
            ]
        ],
        "free" => [
            [
                "id"        => "int(required) #ID 16",
                "img"       => "string() #游戏图片地址",
                "url"       => "string(required) #固定跳转进入第三方/game/third/app?play_id=551",
                "quit_url"  => "string(required) #固定跳转退出第三方/game/third/quit?play_id=551",
                "favorite"  => "int(required) 1：已收藏，0:未收藏"
            ]
        ],
        "fish" => [
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
        $time           = time();
        $redis_key      = \Logic\Define\CacheKey::$perfix['todayHotGameList'].date('Y-m-d',$time);
        $date           = date('Y-m-d H:i:s', $time);
        $data2          = $this->redis->get($redis_key);
        $game_list_type = ['hot', 'new', 'big_prize', 'free', 'fish'];

        if (!empty($data2) ) {
            $res = json_decode($data2, true);
            foreach ($game_list_type as $type_name){
                if(empty($res[$type_name])) continue;
                foreach ($res[$type_name] as &$item){
                    $item['img'] = showImageUrl($item['img']);
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
        //捕鱼游戏
        $menu_fish_id_list = DB::table('game_menu')
            ->select(['id'])
            ->where('status', 'enabled')
            ->where('switch', 'enabled')
            ->where('pid', 22)
            ->get()
            ->toArray();
        if($menu_fish_id_list){
            
            $menu_fish_id_list = array_column($menu_fish_id_list,'id');
        }else{
            $menu_fish_id_list = [];
        }
        //所有游戏
        $menu_id_list = DB::table('game_menu')
            ->select(['id'])
            ->where('status', 'enabled')
            ->where('switch', 'enabled')
            ->where('pid','!=', 0)
            ->get()
            ->toArray();

        if($menu_id_list){
            $menu_id_list = array_column($menu_id_list,'id');
        }else{
            return [];
        }

        //热门游戏后台有设置
        $hotGameIdList = DB::table('game_3th')
            ->where('status', '=', 'enabled')
            ->whereIn('game_id', $menu_id_list)
            ->where('is_hot',1)
            ->get(['id'])
            ->toArray();
        $hotGameIdList && $hotGameIdList = GameMenu::getGameIdList($hotGameIdList);

        $hotGameList = DB::table('game_3th')
            ->select(['id', 'game_img as img'])
            ->whereIn('id', $hotGameIdList)
            ->orderBy('sort')
            ->get()
            ->toArray();

        //最新游戏 大奖游戏 免费旋转
        $newGameIdList = DB::table('game_3th')
            ->where('status', '=', 'enabled')
            ->whereIn('game_id', $menu_slot_id_list)
            ->get(['id'])
            ->toArray();
        $newGameIdList && $newGameIdList = GameMenu::getRondomGameIdList($newGameIdList,36);
        $newGameList = DB::table('game_3th')
            ->select(['id', 'game_img as img'])
            ->whereIn('id', $newGameIdList)
            ->orderBy('sort')
            ->get()
            ->toArray();

        //捕鱼游戏
        $fishIdList = DB::table('game_3th')
            ->where('status', '=', 'enabled')
            ->whereIn('game_id', $menu_fish_id_list)
            ->get(['id'])
            ->toArray();
        $fishIdList && $fishIdList = GameMenu::getRondomGameIdList($fishIdList,12);

        $fishList = DB::table('game_3th')
            ->select(['id', 'game_img as img'])
            ->whereIn('id', $fishIdList)
            ->orderBy('sort')
            ->get()
            ->toArray();

        $jump_url  = '/game/third/app?';  // 固定跳转进入第三方
        $quit_url  = '/game/third/quit?';  // 固定跳转退出第三方

        $all_game_list = [];
        foreach ($hotGameList ?? [] as &$value){
            $value->url      = $jump_url . 'play_id=' . $value->id;
           // $value->quit_url = $quit_url . 'play_id=' . $value->id;
        }
        unset($value);

        foreach ($fishList ?? [] as &$fish){
            $fish->url      = $jump_url . 'play_id=' . $fish->id;
            //$fish->quit_url = $quit_url . 'play_id=' . $fish->id;
        }
        unset($fish);

        foreach ($newGameList ?? [] as &$v){
            $v->url      = $jump_url . 'play_id=' . $v->id;
            //$v->quit_url = $quit_url . 'play_id=' . $v->id;
        }
        unset($v);

        while($newGameList){
            $newGameList && $all_game_list['new'][]       = array_shift($newGameList);
            $newGameList && $all_game_list['big_prize'][] = array_shift($newGameList);
            $newGameList && $all_game_list['free'][]      = array_shift($newGameList);
        }
        $all_game_list['hot']  = $hotGameList;
        $all_game_list['fish'] = $fishList;

        $this->redis->setex($redis_key, 60*60*24, json_encode($all_game_list));

        foreach ($game_list_type as $type_name){
        if(empty($all_game_list[$type_name])) continue;
        foreach ($all_game_list[$type_name] as &$item){
            $item->img = showImageUrl($item->img);
        }
        unset($item);
    }
        return $this->lang->set(0, [], $all_game_list);
    }

};
