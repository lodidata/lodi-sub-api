<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/4/23
 * Time: 17:16
 */

use Utils\Www\Action;

/**
 * 首页菜单详细列表
 */
return new class extends Action
{
    const TITLE = "一级分类下所有游戏列表";
    const DESCRIPTION = "一级分类下所有游戏列表";
    const TAGS = '首页';
    const QUERY = [
        "type"  => "string() #游戏类型 game_menu表type GAME,LIVE,SPORT,QP,BY 默认为BY"
    ];
    const SCHEMAS = [
        [
            "id"        => "int(required) #ID 16",
            "game_id"   => "int(required) #game_menu外键ID 156",
            "game_name" => "string(required) #游戏名称 炸金花",
            "game_alias"=> "string(required) #游戏别名 Fraud Jinhua",
            "extension_img" => "string() #单独的捕鱼游戏界面的图标 其他没有",
            "sort"      => "int() #竖版排序 1，2，3",
            "switch"    => "string() #超管总开关 enabled,disabled",
            "url"       => "string(required) #固定跳转进入第三方/game/third/app?play_id=551",
            "quit_url"  => "string(required) #固定跳转退出第三方/game/third/quit?play_id=551"
        ]
    ];

    public function run()
    {
        $type = $this->request->getParam('type');
        $type = $type ? $type : 'BY';
        if(!in_array($type,['GAME','LIVE','SPORT','QP','BY'])) {
            return $this->lang->set(-1);
        }
        $typdId=(array)DB::table('game_menu')->where('switch','enabled')->where('type',$type)->get(['id'])->first();

        $data = DB::table('game_3th as g3')
            ->leftJoin('game_menu as gm','gm.id','=','g3.game_id')
            ->select(['g3.id', 'game_id', 'game_name', 'g3.alias as game_alias', 'extension_img', 'g3.sort', 'switch'])
            ->where('gm.pid', '=', $typdId['id'])
            ->where('gm.switch', '=', 'enabled')
            ->where('g3.status', '=', 'enabled')
            ->where('gm.status', '=', 'enabled')
            ->orderBy('sort')
            ->get()
            ->toArray();

        $jump_url = '/game/third/app?';  // 固定跳转进入第三方
        $quit_url = '/game/third/quit?';  // 固定跳转退出第三方

        foreach ($data as $key=>$item) {
            $item=(array)$item;
            //  直接进入大厅不进入子游戏
            $item['url'] = $type == 'QP' ? $jump_url . 'play_id=39' : $jump_url . 'play_id=' . $item['id'];
            $item['quit_url'] = $quit_url . 'play_id=' . $item['id'];

            $data[$key]=$item;
        }
        return $this->lang->set(0, [], $data);
    }
};
