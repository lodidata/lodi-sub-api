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

        $time      = time();
        $redis_key = \Logic\Define\CacheKey::$perfix['pageHotGameList'] . date('Y-m-d', $time);
        $date      = date('Y-m-d H:i:s', $time);
        $data2     = $this->redis->get($redis_key);
        if (!empty($data2) ) {
            $res = json_decode($data2, true);
            foreach($res as &$v1){
                $v1['img'] = showImageUrl($v1['img']);
                foreach($v1['childrens'] as &$v2){
                    $v2['img'] = showImageUrl($v2['img']);
                }
            }
            unset($v1,$v2);
            return $res;
        }

        //所有游戏
        $menu_id_list = DB::table('game_menu')
                          ->select(['id', 'type as name','img'])
                          ->where('status', 'enabled')
                          ->where('switch', 'enabled')
                          ->where('pid', '!=', 0)
                          ->get()->toArray();

        if(empty($menu_id_list)) {
            return [];
        }
        $hotGameIdList = [];
        foreach($menu_id_list as $key=>$item) {
            $item=(array)$item;
            $game= DB::table('game_3th')
                     ->where('status', '=', 'enabled')
                     ->where('game_id', $item['id'])
                     ->where('is_hot', 1)
                     ->orderBy("sort")
                     ->get(['id', 'game_img as img', 'game_name'])
                     ->toArray();
            if(!empty($game)){
                $jump_url = '/game/third/app?';  // 固定跳转进入第三方
                //$quit_url = '/game/third/quit?';  // 固定跳转退出第三方
                foreach($game ?? [] as $value) {
                    $value->url      = $jump_url . 'play_id=' . $value->id;
                    //$value->quit_url = $quit_url . 'play_id=' . $value->id;
                }
                $item['childrens']=$game;
                $hotGameIdList[]=$item;

            }
        }


        $this->redis->setex($redis_key, 20 * 60 * 60, json_encode($hotGameIdList));
        foreach($hotGameIdList as &$v1){
            $v1['img'] = showImageUrl($v1['img']);
            foreach($v1['childrens'] as &$v2){
                $v2->img = showImageUrl($v2->img);
            }
        }
        unset($v1,$v2);
        return $this->lang->set(0, [], $hotGameIdList);
    }

};