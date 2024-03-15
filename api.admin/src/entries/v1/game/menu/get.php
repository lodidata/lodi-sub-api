<?php

use Logic\Admin\BaseController;

/**
 * 获取当前游戏的开关信息
 *
 */
return new class() extends BaseController
{

    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run()
    {
        //是否查找渠道下的游戏
        $type = $this->request->getParam('type','');

        $menus = DB::table('game_menu')
            ->selectRaw('id,type,`name`,`rename`')
            ->where('pid',0)
            ->where('id','<>',23)
            ->where('switch', 'enabled')
            ->get()
            ->toArray();
        foreach ($menus as $menu){
            $menu->childs = DB::table('game_menu')->where('pid',$menu->id)->where('switch', 'enabled')->selectRaw('id,name,`rename`,type')->get()->toArray();

             //找出渠道下的游戏
            if(!empty($type))
            {
                foreach ($menu->childs as $value) {
                    $value->game = DB::table('game_3th')->where('game_id', $value->id)->selectRaw('id, kind_id, game_id, game_name, `rename`')->get()->toArray();
                }
                
            }
        }


        return (array)$menus;
    }
};
