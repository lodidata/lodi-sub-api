<?php

use Logic\Admin\BaseController;

/**
 * 获取渠道下的游戏列表
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
        $game_id = $this->request->getParam('game_id','');
        $query = DB::table('game_menu');
            if(!empty($game_id))
            {
                $str = explode(',', $game_id);
                $query->whereIn('game_menu.type', $str);
            }
        $ids=$query->pluck('id')->toArray();
            
        $game_list = DB::table('game_menu as g')
            ->join('game_3th as gm','g.id','=','gm.game_id')
            ->whereIn('g.pid',$ids)
            ->where('g.status','=','enabled')
            ->selectRaw('gm.id, gm.kind_id, gm.game_id, gm.game_name, gm.rename')
            ->get()
            ->toArray();
        return (array)$game_list;
    }
};
