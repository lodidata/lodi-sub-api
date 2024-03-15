<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/4/23
 * Time: 17:16
 */

use Utils\Www\Action;

/**
 * 首页菜单【第三方游戏】
 */
return new class extends Action {
    const HIDDEN = true;
    const TITLE = "首页菜单及游戏列表（横屏版）";
    const DESCRIPTION = "首页菜单及游戏列表（横屏版）";
    const TAGS = '首页';
    const QUERY = [];
    const SCHEMAS = [
        [
            "id"        => "int(required) #ID 16",
            "pid"       => "int(required) #上级ID 0",
            "type"      => "string(required) #游戏类型 HOST",
            "name"      => "string(required) #游戏名称",
            "img2"      => "string() #游戏图片地址",
            "quit"      => "int() #是否可踢下线，1可以，2否",
            "list_mode" => "int() #模式1--game_3th表，2--跳转第三方大厅list，8--自营",
            "across_sort" => "int() #横版排序 1，2，3",
            "switch"    => "string() #超管总开关 enabled,disabled",
            "childrens" => [
                [
                    "id"        => "int(required) #ID 16",
                    "pid"       => "int(required) #上级ID 0",
                    "type"      => "string(required) #游戏类型 VTDZ",
                    "name"      => "string(required) #游戏名称",
                    "qp_icon"   => "string() #选中显示图标",
                    "qp_un_icon" =>  "string() #未选中显示图标",
                    "img"       => "string() #棋牌图片地址",
                    "img2"      => "string(required) #游戏图片地址",
                    "quit"      => "int(required) #是否可踢下线，1可以，2否",
                    "list_mode" => "int(required) #模式1--game_3th表，2--跳转第三方大厅list，8--自营",
                    "across_sort" => "int(required) #横版排序 1，2，3",
                    "switch"    => "string(required) #超管总开关 enabled,disabled",
                    "m_start_time" => "string() #维护开始时间 2018-12-30 21:04:05.000000",
                    "m_end_time" => "string() #维护结束时间 2019-01-21 23:04:07.000000",
                    "url"       => "string(required) #固定跳转进入第三方/game/third/app?play_id=551",
                    "quit_url"  => "string(required) #固定跳转退出第三方/game/third/quit?play_id=551",
                    "childrens" => [
                        [
                            "id"        => "int(required) #ID 16",
                            "pid"       => "int(required) #上级ID 0",
                            "name"      => "string(required) #游戏名称",
                            "alias"     => "string() #游戏别名 HOST",
                            "img"       => "string() #棋牌图片地址",
                            "img2"      => "string(required) #游戏图片地址",
                            "is_hot"    => "int(required) #是否热门 1是 0否",
                            "across_sort" => "int(required) #横版排序 1，2，3",
                            "parent_type" => "string(required) #上级游戏类型 VTDZ",
                            "url"       => "string(required) #固定跳转进入第三方/game/third/app?play_id=551",
                            "quit_url"  => "string(required) #固定跳转退出第三方/game/third/quit?play_id=551"
                        ]
                    ]
                ]
            ]
        ]
    ];

    public function run() {

        $data2 = $this->redis->get(\Logic\Define\CacheKey::$perfix['qp_h_menuList']);

        if (!empty($data2) ) {
            $list = json_decode($data2, true);
            foreach($list as &$item){
                $item['img2'] = showImageUrl($item['img2']);
                foreach($item['childrens'] as &$item2){
                    $item2['img'] = showImageUrl($item2['img']);
                    $item2['img2'] = showImageUrl($item2['img2']);
                    if(!empty($item2['children'])){
                        foreach($item2['children'] as &$item3){
                            $item3['img'] = showImageUrl($item3['img']);
                            $item3['img2'] = showImageUrl($item3['img2']);
                        }
                    }
                }
            }
            unset($item,$item2,$item3);
            return $list;
        }

        $gameInfo = DB::table('game_3th')
            ->select(['id', 'game_id as pid', 'game_name as name', 'alias', 'qp_img as img', 'game_img as img2' ,'is_hot', 'across_sort'])
            ->where('across_status', '=', 'enabled')
            ->orderBy('across_sort')
            ->get()
            ->toArray();

        $data = DB::table('game_menu')
            ->where('across_status', '=', 'enabled')
            ->where('switch','enabled')
            ->orderBy('pid')
            //->whereNotIn("id", [23, 26, 27])// 排除彩票
            ->get(['id', 'pid', 'type', 'name', 'qp_icon', 'qp_un_icon', 'qp_img as img', 'qp_img2 as img2', 'quit', 'list_mode', 'across_sort', 'switch', 'm_start_time', 'm_end_time'])
            ->toArray();


        $newArr = array();
        $hot_arr = array();
        $num = 0;
        foreach ($data as $item) {
            $item = (array)$item;
            if ($item['pid'] == 0) {
                // 不需要的删除
                unset($item['qp_icon']);
                unset($item['qp_un_icon']);
                unset($item['img']);
                unset($item['m_start_time']);
                unset($item['m_end_time']);
                $newArr[$item['id']] = $item;
            } else {
                //超管的总开关控制
                if ($item['switch'] != 'enabled' && !in_array($item['type'], ['ZYCPSTA', 'ZYCPCHAT'])) {
                    continue;
                }
                if (isset($newArr[$item['pid']])) {
                    $newArr[$item['pid']]['childrens'][$num] = $item;
                    $num++;
                    $newArr[$item['pid']]['childrens'] = array_values($newArr[$item['pid']]['childrens']);

                    array_multisort(array_column($newArr[$item['pid']]['childrens'], 'across_sort'), SORT_ASC, $newArr[$item['pid']]['childrens']);
                }
            }
        }

        //以sort对数组进行排序
        array_multisort(array_column($newArr, 'across_sort'), SORT_ASC, $newArr);
        $tmp[0] = [             //方便前端展示做以下操作
            "id" => 0,
            "pid" => 0,
            "type" => "",
            "name" => "",
        ];
        $jump_url = '/game/third/app?';  // 固定跳转进入第三方
        //$quit_url = '/game/third/quit?';  // 固定跳转退出第三方
        foreach ($newArr as $key => $newitem) {
            if (isset($newitem['childrens'])) {
                foreach ($newitem['childrens'] as $keyCh => $children) {
                    if (!in_array($children['id'], [26, 27])) {
                        // 第三方游戏
                        foreach ($gameInfo as $gameitem) {
                            $gameitem = (array)$gameitem;
                            if ($children['id'] == $gameitem['pid']) {
                                $gameitem['url'] = $jump_url . 'play_id=' . $gameitem['id'];
                                $gameitem['parent_type'] = $children['type'];
                                //$gameitem['quit_url'] = $quit_url . 'play_id=' . $gameitem['id'];
                                /*if (in_array($children['pid'], [22, 16])) {
                                    // 体育 or 捕鱼
                                    if ($newArr[$key]['childrens'][0]['id'] != 0) {
                                        $newArr[$key]['childrens'] = $tmp;
                                    }
                                    $newArr[$key]['childrens'][0]['children'][] = $gameitem;
                                } else {*/
                                    // 其他
                                    $newArr[$key]['childrens'][$keyCh]['children'][] = $gameitem;
                                    // 如果他是热门游戏，加入列表
                                    if (isset($gameitem['is_hot']) && $gameitem['is_hot'] == 1) {
                                        $gameitem['type'] = $gameitem['parent_type'];
                                        unset($gameitem['parent_type']);
                                        $hot_arr[] = $gameitem;
                                    }
                                //}
                            }
                        }
                    } else {
                        // 彩票
                         if ($children['id'] == 26) {
                            $newArr[$key]['childrens'][$keyCh]['children'] = DB::table('lottery')
                                ->select(['id', 'pid', 'name', 'alias', 'index_f_img as img'])
                                ->where('pid', '<>', 0)
                                ->whereRaw("FIND_IN_SET('enabled', state)")
                                ->whereRaw("FIND_IN_SET('standard', state)")
                                ->orderBy('sort')
                                ->get()
                                ->toArray();;
                        } else {
                            $newArr[$key]['childrens'][$keyCh]['children'] = DB::table('lottery')
                                ->select(['id', 'pid', 'name', 'alias', 'index_f_img as img'])
                                ->where('pid', '<>', 0)
                                ->whereRaw("FIND_IN_SET('enabled', state)")
                                ->whereRaw("FIND_IN_SET('chat', state)")
                                ->orderBy('sort')
                                ->get()
                                ->toArray();;
                        }
                    }
                }
            }
        }
        // 热门游戏
        $hot_list = [
            [
                'id' => 0,
                'pid' => 0,
                'type' => "HOT",
                'name' => "热门游戏",
                'childrens' => $hot_arr
            ],
        ];

        $result = [];
        foreach ($newArr as $item) {
            $type = $item['type'];
            // 真人移动第一级
            if ($type == 'LIVE') {
                if (isset($item['childrens'])) {
                    $childrens = $item['childrens'];
                    foreach ($childrens as $key => $child) {
                        if ($child['type'] == 'DGVIDEO') {
                            // DG 把三级全部合并为 2级
                            $childrenList = $child['children'];
                            foreach ($childrenList as $i => $bean) {
                                $old = $childrens[$key];
                                $old['name'] = $old['name'] . "-" . $bean['name'];
                                $old['url'] = $bean['url'];
                                $old['quit_url'] = $bean['quit_url'];
                                $old['img'] = $bean['img'];
                                $old['img2'] = $bean['img2'];
                                unset($old['children']);
                                $childrens[] = $old;
                            }
                            unset($childrens[$key]);
                        } elseif ($child['type'] == 'BGOPEN'){
                            if (isset($child['children'])){
                                $childrenList = $child['children'];
                                $bean = $childrenList[0];
                                $old = $childrens[$key];
                                $old['url'] = $bean['url'];
                                $old['quit_url'] = $bean['quit_url'];
                                unset($old['children']);
                                $childrens[] = $old;
                            }
                            unset($childrens[$key]);
                        } else {
                            // 把三级全部去掉，直接进大厅
                            $child['url'] = $jump_url . 'game_id=' . $child['id'];
                            //$child['quit_url'] = $quit_url . 'game_id=' . $child['id'];
                            unset($child['children']);
                            $childrens[$key] = $child;
                        }
                    }
                    // 修改
                    array_multisort(array_column($childrens, 'across_sort'), SORT_ASC, $childrens);
                    $item['childrens'] = $childrens;
                }
                $result[] = $item;
            } else {
                $result[] = $item;
            }
        }
        $data_list = array_merge($hot_list, $result);

        $this->redis->setex(\Logic\Define\CacheKey::$perfix['qp_h_menuList'], 1800, json_encode($data_list));

        foreach($data_list as &$item){
            $item['img2'] = showImageUrl($item['img2']);
            foreach($item['childrens'] as &$item2){
                $item2['img'] = showImageUrl($item2['img']);
                $item2['img2'] = showImageUrl($item2['img2']);
                if(!empty($item2['children'])){
                    foreach($item2['children'] as &$item3){
                        $item3['img'] = showImageUrl($item3['img']);
                        $item3['img2'] = showImageUrl($item3['img2']);
                    }
                }
            }
        }
        unset($item,$item2,$item3);

        return $data_list;
    }

};
