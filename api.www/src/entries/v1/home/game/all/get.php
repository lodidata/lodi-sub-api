<?php

use \Utils\Www\Action;

return new class extends Action {

    const TITLE = "首页热门游戏悬浮图标";
    const DESCRIPTION = "";
    const TAGS = '首页';
    const QUERY = [

    ];

    const SCHEMAS = [

    ];

    public function run() {

        $time = time();
        $redis_key = \Logic\Define\CacheKey::$perfix['hotGameList'];
        $date = date('Y-m-d H:i:s', $time);
        $hotGameIdList = $this->redis->get($redis_key);
        if (empty($hotGameIdList) ) {
            //所有游戏
            $alias_id_list = DB::table('game_menu')
                ->select(['alias as name', 'img', 'id'])
                ->where('hot_status', 'enabled')
                ->where('status', 'enabled')
                ->where('switch', 'enabled')
                ->where('pid', '!=', 0)
                ->where('pid', '!=', 23)
                //->whereRaw("'{$date}' not between m_start_time and m_end_time")
                ->groupBy('alias')
                ->orderBy('hot_sort', 'asc')
                ->get()->toArray();
            if (empty($alias_id_list)) {
                return [];
            }

            $hotGameIdList = [];
            foreach ($alias_id_list as $key => &$item) {
                $item = (array)$item;
                $ids = DB::table("game_menu")->where('alias', $item['name'])
                    ->where('hot_status', 'enabled')
                    ->where('status', 'enabled')
                    ->where('switch', 'enabled')
                    ->pluck('id')
                    ->toArray();
                $game = DB::table('game_3th')
                    ->where('hot_status', '=', 'enabled')
                    ->where('status', '=', 'enabled')
                    ->whereIn('game_id', $ids)
                    ->orderBy("hot_sort")
                    ->get(['id', 'game_img as img', 'game_name'])
                    ->toArray();
                if (!empty($game)) {
                    $jump_url = '/game/third/app?';  // 固定跳转进入第三方
                    //$quit_url = '/game/third/quit?';  // 固定跳转退出第三方
                    foreach ($game ?? [] as $value) {
                        $value->url = $jump_url . 'play_id=' . $value->id;
                        //$value->quit_url = $quit_url . 'play_id=' . $value->id;
                    }
                    $item['childrens'] = $game;
                    $hotGameIdList[] = $item;
                }
            }
            $this->redis->setex($redis_key, 2 * 60 * 60, json_encode($hotGameIdList));
        }else{
            $hotGameIdList = json_decode($hotGameIdList, true);
        }
        foreach($hotGameIdList as &$v1){
            $v1['img'] = showImageUrl($v1['img']);
            foreach($v1['childrens'] as &$v2){
                if(is_object($v2)){
                    $v2->img = showImageUrl($v2->img);
                }else{
                    $v2['img'] = showImageUrl($v2['img']);
                }
            }
        }
        unset($v1,$v2);

        return $this->lang->set(0, [], $hotGameIdList);
    }

};