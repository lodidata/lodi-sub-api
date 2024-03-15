<?php

use Utils\Www\Action;
use Model\Favorites;
/**
 * 首页菜单【第三方游戏】
 */
return new class extends Action
{
    const TITLE = "首页菜单及游戏列表（新竖屏版）";
    const DESCRIPTION = "参数ID 二级游戏id 不传获取一二级分类 传ID获取第三级游戏列表";
    const TAGS = '首页';
    const QUERY = [
        'id' => 'int() #二级游戏id 空获取一二级分类 传ID获取第三级游戏列表'
    ];
    const SCHEMAS = [
        [
            "id"        => "int(required) #ID 16",
            "pid"       => "int(required) #上级ID 0",
            "type"      => "string(required) #游戏类型 HOST",
            "name"      => "string(required) #游戏名称",
            "img"       => "string() #游戏图片地址",
            "quit"      => "int() #是否可踢下线，1可以，2否",
            "list_mode" => "int() #模式1--game_3th表，2--跳转第三方大厅list，8--自营",
            "sort"      => "int() #横版排序 1，2，3",
            "switch"    => "string() #超管总开关 enabled,disabled",
            "m_start_time" => "string() #维护开始时间 2018-12-30 21:04:05.000000",
            "m_end_time" => "string() #维护结束时间 2019-01-21 23:04:07.000000",
            "childrens" => [
                [
                    "id"        => "int(required) #ID 16",
                    "pid"       => "int(required) #上级ID 0",
                    "type"      => "string(required) #游戏类型 HOST",
                    "name"      => "string(required) #游戏名称",
                    "img"       => "string() #游戏图片地址",
                    "quit"      => "int() #是否可踢下线，1可以，2否",
                    "list_mode" => "int() #模式1--game_3th表，2--跳转第三方大厅list，8--自营",
                    "sort"      => "int() #横版排序 1，2，3",
                    "switch"    => "string() #超管总开关 enabled,disabled",
                    "m_start_time" => "string() #维护开始时间 2018-12-30 21:04:05.000000",
                    "m_end_time" => "string() #维护结束时间 2019-01-21 23:04:07.000000",
                    "childrens" => [
                        [
                            "id"        => "int(required) #ID 16",
                            "pid"       => "int(required) #上级ID 0",
                            "name"      => "string(required) #游戏名称",
                            "alias"     => "string() #游戏别名 HOST",
                            "img"       => "string() #游戏图片地址",
                            "sort"      => "int(required) #横版排序 1，2，3",
                            "url"       => "string(required) #固定跳转进入第三方/game/third/app?play_id=551",
                            "quit_url"  => "string(required) #固定跳转退出第三方/game/third/quit?play_id=551"
                        ]
                    ]
                ]
            ]
        ]
    ];

    public function run()
    {
        $id   = (int)$this->request->getParam('id');
        $verify = $this->auth->verfiyToken();
        /*if (!$verify->allowNext()) {
            return $verify;
        }*/
        $user_id        = $this->auth->getUserId();
        if($id){
            $redis_key = \Logic\Define\CacheKey::$perfix['verticalMenuList'].':'.$id;
        }else{
            $redis_key = \Logic\Define\CacheKey::$perfix['verticalMenuList'];
        }

        $data2 = $this->redis->get($redis_key);

        if (!empty($data2) ) {
            $res = json_decode($data2, true);
            if($id && $user_id){
                $favorite_list  = Favorites::getGameIdList($user_id);
                //判断是否收藏
                foreach ($res as &$item){
                    $favorite = 0;
                    if(in_array($item['id'], $favorite_list)){
                        $favorite = 1;
                    }
                    $item['favorite'] = $favorite;
                }
                unset($item);
            }
            //图片拼接
            foreach ($res as &$item){
                $item['img'] = showImageUrl($item['img']);
                if(isset($item['childrens']) && !empty($item['childrens'])) {
                    foreach ($item['childrens'] as &$item2) {
                        $item2['img'] = showImageUrl($item2['img']);
                        if (!empty($item2['children'])) {
                            foreach ($item2['children'] as &$item3) {
                                $item3['img'] = showImageUrl($item3['img']);
                            }
                        }
                    }
                }
            }
            unset($item,$item2,$item3);

            return $res;
        }

        if($id){
            if (!in_array($id, [26, 27])) {
                $gameInfo = DB::table('game_3th')
                    ->select(['id', 'game_name as name', 'game_img as img','is_hot'])
                    ->where('status', '=', 'enabled')
                    ->where('game_id', $id)
                    ->orderBy('sort')
                    ->get()
                    ->toArray();
                $jump_url = '/game/third/app?';  // 固定跳转进入第三方
                //$quit_url = '/game/third/quit?';  // 固定跳转退出第三方
                // 第三方游戏
                foreach ($gameInfo as &$gameitem) {
                    $gameitem->url      = $jump_url . 'play_id=' . $gameitem->id;
                   // $gameitem->quit_url = $quit_url . 'play_id=' . $gameitem->id;
                }
                unset($gameitem);

            } else {
                // 彩票
                if($id == 26) {
                    $gameInfo = DB::table('lottery')
                        ->select(['id',  'name','index_f_img as img', 'is_hot'])
                        ->where('pid', '<>', 0)
                        ->whereRaw("FIND_IN_SET('enabled', state)")
                        ->whereRaw("FIND_IN_SET('standard', state)")
                        ->orderBy('sort')
                        ->get()
                        ->toArray();;
                }else {
                    $gameInfo = DB::table('lottery')
                        ->select(['id',  'name', 'index_f_img as img' , 'is_hot'])
                        ->where('pid', '<>', 0)
                        ->whereRaw("FIND_IN_SET('enabled', state)")
                        ->whereRaw("FIND_IN_SET('chat', state)")
                        ->orderBy('sort')
                        ->get()
                        ->toArray();;
                }

            }
            if($gameInfo){
                $this->redis->setex($redis_key, 60*60, json_encode($gameInfo));
            }
            if($user_id){
                $favorite_list  = Favorites::getGameIdList($user_id);
                //判断是否收藏
                foreach ($gameInfo as &$item){
                    $favorite = 0;
                    if(in_array($item->id, $favorite_list)){
                        $favorite = 1;
                    }
                    $item->favorite = $favorite;
                    $item->img = showImageUrl($item->img);
                }
                unset($item);
            }else{
                foreach ($gameInfo as &$item){
                    $item->img = showImageUrl($item->img);
                }
                unset($item);
            }

            return $gameInfo;
        }


        $data = DB::table('game_menu')
            ->where('status', '=', 'enabled')
            ->where('switch','enabled')
            ->whereNotIn("id", [23, 26, 27])// 排除彩票
            ->orderBy('pid')
            ->orderBy('sort')
            ->get(['id', 'pid', 'type', 'name', 'img'])
            ->toArray();


        $newArr = array();
        $num = 0;
        foreach ($data as $item) {
            $item = (array)$item;

            if ($item['pid'] == 0) {
                $newArr[$item['id']] = $item;
            } else {
                //超管的总开关控制
               /* if ($item['switch'] != 'enabled' && !in_array($item['type'], ['ZYCPSTA', 'ZYCPCHAT'])) {
                    continue;
                }*/
                //$item['maintenance'] = $maintenance;
                if (isset($newArr[$item['pid']])) {
                    $newArr[$item['pid']]['childrens'][$num] = $item;
                    $num++;
                    $newArr[$item['pid']]['childrens'] = array_values($newArr[$item['pid']]['childrens']);

                   // array_multisort(array_column($newArr[$item['pid']]['childrens'], 'sort'), SORT_ASC, $newArr[$item['pid']]['childrens']);
                }
            }

        }

        //以sort对数组进行排序
        //array_multisort(array_column($newArr, 'sort'), SORT_ASC, $newArr);
        $tmp[0] = [             //方便前端展示做以下操作
            "id" => 0,
            "pid" => 0,
            "type" => "",
            "name" => "",
        ];

        //增加热门游戏
        $hot_game = DB::table('game_menu')->select('id', 'pid', 'type', 'name', 'img')->where('hot_status', '=', 'enabled')->where('alias','HOT')->first();
        if(!empty($hot_game)){
            $hot_game = (array)$hot_game;
            array_splice($newArr,0,0,[$hot_game]);
        }
        if($newArr){
            $newArr = array_values($newArr);
        }
        $this->redis->setex($redis_key, 60*60, json_encode($newArr));

        //图片拼接
        foreach ($newArr as &$item){
            $item['img'] = showImageUrl($item['img']);
            if(isset($item['childrens']) && !empty($item['childrens'])) {
                foreach ($item['childrens'] as &$item2) {
                    $item2['img'] = showImageUrl($item2['img']);
                    if (!empty($item2['children'])) {
                        foreach ($item2['children'] as &$item3) {
                            $item3['img'] = showImageUrl($item3['img']);
                        }
                    }
                }
            }
        }
        unset($item,$item2,$item3);
        return $this->lang->set(0, [], $newArr);
    }

};
