<?php

use Utils\Www\Action;

return new class extends Action {
    const TOKEN = true;
    const TITLE = "获取游戏类型";
    const TAGS = '游戏';
    const QUERY = [
    ];
    const SCHEMAS = [
    ];

    public function run() {

        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $data = DB::table('game_menu')
                  ->select(['id', 'name', 'rename', 'sort'])
                  ->where('pid','=',0)
                  ->where('id','!=',23)
                  ->where('status','=','enabled')
                  ->where('switch','=','enabled')
                  ->orderBy('sort','asc')
                  ->get()
                  ->toArray();
                  
        return $data;
    }
};