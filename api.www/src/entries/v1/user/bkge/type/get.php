<?php
use Utils\Www\Action;

return new class extends Action {
    const TOKEN = true;
    const TITLE = "获取游戏类型";
    const TAGS = "个人中心";
    const QUERY = [

    ];
    const SCHEMAS = [
        [

        ]
    ];

    public function run() {
        //获取游戏分类
        $gameType = DB::table("game_menu")
                        ->where('pid', 0)
                        ->where('status', 'enabled')
                        ->where('switch', 'enabled')
                        ->get(['id', 'type', 'name'])
                        ->toArray();

        //获取结算方式
        $bkge_info = DB::table("system_config")->where('module','rakeBack')->where('key','bkge_settle_type')->first();
        $bkge_type = 1;
        if(!empty($bkge_info)){
            $bkge_type = $bkge_info->value;
        }

        return $this->lang->set(0, [], $gameType, ['bkge_type'=>$bkge_type]);
    }
};