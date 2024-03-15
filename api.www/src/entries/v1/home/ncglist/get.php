<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/4/23
 * Time: 17:16
 */

use Utils\Www\Action;
use Model\Favorites;

/**
 * 二级菜单详细列表
 */
return new class extends Action
{
    const TITLE       = '游戏列表及搜索游戏';
    const DESCRIPTION = '二级分类下的游戏或者模糊搜索游戏名称 menu表进同步ID菜单列表(传game_id才有)，hot热门游戏 list 游戏列表';
    const TAGS = '首页';
    const QUERY       = [
        'game_id'   => 'int() #游戏分类ID号 与名称二选一',
        'game_name' => "string() #游戏名称 模糊搜索",
    ];
    const SCHEMAS     = [
        'menu' => [
            [
                "id"        => "int(required) #ID 16",
                "type"      => "string(required) #游戏类型 HOST",
                "name"      => "string(required) #游戏名称",
                "img"       => "string() #游戏图片地址",
            ]
        ],
        'hot' => [
            [
                "id" => 'int(required) #ID 108',
                "pid" => 'int(required) #上级ID 5',
                'favorite' => 'int(required) #收藏 1是 0 否',
                'is_hot' => 'int(required) #热门 1是 0 否',
                "name" => "string(required) #游戏名称 幸运快3",
                "alias"=> "string(required) #游戏别名 KS",
                "img" => "string(required) #游戏图片",
                "url"       => "string(required) #固定跳转进入第三方/game/third/app?play_id=551",
                "quit_url"  => "string(required) #固定跳转退出第三方/game/third/quit?play_id=551"
            ]
        ],

        'list' => [
            [
                "id" => 'int(required) #ID 108',
                "pid" => 'int(required) #上级ID 5',
                'favorite' => 'int(required) #收藏 1是 0 否',
                'is_hot' => 'int(required) #热门 1是 0 否',
                "name" => "string(required) #游戏名称 幸运快3",
                "alias"=> "string(required) #游戏别名 KS",
                "img" => "string(required) #游戏图片",
                "url"       => "string(required) #固定跳转进入第三方/game/third/app?play_id=551",
                "quit_url"  => "string(required) #固定跳转退出第三方/game/third/quit?play_id=551"
            ]
        ]
    ];
    public function run()
    {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $user_id   = $this->auth->getUserId();

        $game_name = $this->request->getParam('game_name') ;
        $game_id   = $this->request->getParam('game_id') ;

        $favorite_list  = Favorites::getGameIdList($user_id);
        $table = DB::table('game_3th')
            ->select(['game_3th.id', 'game_3th.game_id as pid', 'game_3th.is_hot', 'game_3th.game_name as name', 'game_3th.alias', 'game_3th.game_img as img'])
            ->leftJoin('game_menu as gm','game_3th.game_id','=','gm.id')
            ->where('gm.status', '=', 'enabled')
            ->where('gm.switch', '=', 'enabled');

        //增加同级菜单
        $menu = [];
        if(empty($game_name)) {
            if(empty($game_id)){
                return [];
            }
            $pid = (array)DB::table('game_menu')->where('id', $game_id)->get(['pid'])->first();
            $menu = DB::table('game_menu')
                ->where('status', '=', 'enabled')
                ->where('pid', '=', $pid)
                ->get(['id', 'type', 'name', 'img'])
                ->toArray();
            
            if(!empty($menu)){
                foreach($menu as &$item){
                    $item->img = showImageUrl($item->img);
                }
                unset($item);
            }

            $table->where('game_3th.game_id', '=', $game_id);
        }else{
            $table->where('game_3th.game_name', 'like', "%".$game_name."%");
        }

        //彩票26 27
        if($game_id == 26){
            $list = DB::table('lottery')
                ->select(['id', 'pid', 'name', 'alias', 'index_f_img as img'])
                ->where('pid', '<>', 0)
                ->whereRaw("FIND_IN_SET('enabled', state)")
                ->whereRaw("FIND_IN_SET('standard', state)")
                ->orderBy('sort')
                ->get()
                ->toArray();
        }elseif ($game_id == 27){
            $list = DB::table('lottery')
                ->select(['id', 'pid', 'name', 'alias', 'index_f_img as img'])
                ->where('pid', '<>', 0)
                ->whereRaw("FIND_IN_SET('enabled', state)")
                ->whereRaw("FIND_IN_SET('chat', state)")
                ->orderBy('sort')
                ->get()
                ->toArray();
        } else {
            $list = $table->where('game_3th.status', '=', 'enabled')
                        ->orderBy('game_3th.sort')
                        ->get()
                        ->toArray();
        }
        $jump_url = '/game/third/app?';  // 固定跳转进入第三方
        $quit_url = '/game/third/quit?';  // 固定跳转退出第三方
        $hot_list = [];
        if(is_array($list)){
            foreach ($list as $k => &$v){
                $favorite = 0;
                if(in_array($v->id, $favorite_list)){
                    $favorite = 1;
                }
                $v->favorite = $favorite;
                $v->img = showImageUrl($v->img);
                if($v->is_hot){
                    $hot_list [] = $v;
                }
                $v->url      = $jump_url . 'play_id=' . $v->id;
                $v->quit_url = $quit_url . 'play_id=' . $v->id;
            }
            unset($v);
        }

        return $this->lang->set(0, [], ['menu' => $menu, 'hot' => $hot_list, 'list' => $list]);
    }
};
