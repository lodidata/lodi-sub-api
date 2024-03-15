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
return new class extends Action
{
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
                    "children" => [
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
        $data2 = $this->redis->get(\Logic\Define\CacheKey::$perfix['menuList']);

        /*if (!empty($data2) ) {
            $list = json_decode($data2, true);
            foreach($list as &$item){
                $item['img'] = showImageUrl($item['img']);
                if(!empty($item['childrens'])) {
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
            return $list;
        }*/

        $gameInfo = DB::table('game_3th')
            ->select(['id', 'game_id as pid', 'game_name as name', 'alias', 'qp_img as img', 'across_sort as sort'])
            ->where('across_status', '=', 'enabled')
            ->orderBy('across_sort')
            ->get()
            ->toArray();

        $data = DB::table('game_menu')
            ->where('across_status', '=', 'enabled')
            //->whereNotIn("id", [23, 26, 27])// 排除彩票
            ->orderBy('pid')
            ->get(['id', 'pid', 'type', 'name', 'qp_img as img', 'quit', 'list_mode', 'across_sort as sort', 'switch', \DB::raw('UNIX_TIMESTAMP(m_start_time) m_start_time'), \DB::raw('UNIX_TIMESTAMP(m_end_time) m_end_time')])
            ->toArray();


        $newArr = array();
        $num = 0;
        foreach ($data as $item) {
            $item = (array)$item;
            $time = time();
            $maintenance = 0;
            if($time >= $item['m_start_time'] && $time <= $item['m_end_time']){
                $maintenance = 1;
            }

            unset($item['m_start_time'], $item['m_end_time']);

            if ($item['pid'] == 0) {
                $newArr[$item['id']] = $item;
            } else {
                //超管的总开关控制
                if ($item['switch'] != 'enabled' && !in_array($item['type'], ['ZYCPSTA', 'ZYCPCHAT'])) {
                    continue;
                }
                $item['maintenance'] = $maintenance;
                if (isset($newArr[$item['pid']])) {
                    $newArr[$item['pid']]['childrens'][$num] = $item;
                    $num++;
                    $newArr[$item['pid']]['childrens'] = array_values($newArr[$item['pid']]['childrens']);

                    array_multisort(array_column($newArr[$item['pid']]['childrens'], 'sort'), SORT_ASC, $newArr[$item['pid']]['childrens']);
                }
            }

        }

        //以sort对数组进行排序
        array_multisort(array_column($newArr, 'sort'), SORT_ASC, $newArr);
        $tmp[0] = [             //方便前端展示做以下操作
            "id" => 0,
            "pid" => 0,
            "type" => "",
            "name" => "",
        ];
        $jump_url = '/game/third/app?';  // 固定跳转进入第三方
        $quit_url = '/game/third/quit?';  // 固定跳转退出第三方
        foreach ($newArr as $key => $newitem) {
            if (isset($newitem['childrens'])) {
                foreach ($newitem['childrens'] as $keyCh => $children) {
                    if (!in_array($children['id'], [26, 27])) {
                        // 第三方游戏
                        foreach ($gameInfo as $gameitem) {
                            $gameitem = (array)$gameitem;
                            if ($children['id'] == $gameitem['pid']) {
                                $gameitem['url'] = $jump_url . 'play_id=' . $gameitem['id'];
                                $gameitem['quit_url'] = $quit_url . 'play_id=' . $gameitem['id'];
                                /*if (in_array($children['pid'],[22,16] )){
                                    if($newArr[$key]['childrens'][0]['id'] != 0){
                                        $newArr[$key]['childrens'] = $tmp;
                                    }
                                    $newArr[$key]['childrens'][0]['children'][] = $gameitem;
                                }else{*/
                                    $newArr[$key]['childrens'][$keyCh]['children'][] = $gameitem;
                                //}
                            }
                        }
                    } else {
                        // 彩票
                        if($children['id'] == 26) {
                            $newArr[$key]['childrens'][$keyCh]['children'] = DB::table('lottery')
                                ->select(['id', 'pid', 'name', 'alias', 'index_f_img as img'])
                                ->where('pid', '<>', 0)
                                ->whereRaw("FIND_IN_SET('enabled', state)")
                                ->whereRaw("FIND_IN_SET('standard', state)")
                                ->orderBy('sort')
                                ->get()
                                ->toArray();
                        }else {
                            $newArr[$key]['childrens'][$keyCh]['children'] = DB::table('lottery')
                                ->select(['id', 'pid', 'name', 'alias', 'index_f_img as img'])
                                ->where('pid', '<>', 0)
                                ->whereRaw("FIND_IN_SET('enabled', state)")
                                ->whereRaw("FIND_IN_SET('chat', state)")
                                ->orderBy('sort')
                                ->get()
                                ->toArray();
                        }

                    }
                }
            }
        }

        $this->redis->setex(\Logic\Define\CacheKey::$perfix['menuList'], 1800, json_encode($newArr));

        foreach($newArr as &$item){
            $item['img'] = showImageUrl($item['img']);
            if(!empty($item['childrens'])) {
                foreach ($item['childrens'] as &$item2) {
                    $item2['img'] = showImageUrl($item2['img']);
                    if (!empty($item2['children'])) {
                        foreach ($item2['children'] as &$item3) {
                            if(is_object($item3)){
                                $item3->img = showImageUrl($item3->img);
                            }else{
                                $item3['img'] = showImageUrl($item3['img']);
                            }
                        }
                    }
                }
            }
        }
        unset($item,$item2,$item3);

        return $this->lang->set(0, [], $newArr);
    }

};
