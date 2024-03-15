<?php

use Utils\Www\Action;

return new class extends Action
{
    const TOKEN = true;
    const TITLE = "盈亏返佣-游戏类别";
    const TAGS = "首页代理";
    const SCHEMAS = [ ];


    public function run() {
        $game_menu = DB::table('game_menu')->where('pid',0)
                                           ->where('type','!=','CP')
                                           ->where('type','!=','HOT')
                                           ->select('id','type','rename')
                                           ->get()->toArray();
        if(!empty($game_menu)){
            foreach($game_menu as $val){
                $val->rename=$this->lang->text($val->type);
            }
        }
        return $game_menu;
    }

};