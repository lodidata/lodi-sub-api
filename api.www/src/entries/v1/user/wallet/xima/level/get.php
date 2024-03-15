<?php
use Utils\Www\Action;
return new class extends Action {
    const TOKEN = true;
    const TITLE = "不同会员级别的打码量";
    const TAGS = "打码量";
    const SCHEMAS = [
        'currentLevel'  => "int(required) #当前会员级别",
        'nextLevel'     => "int(required) #下一个会员级别",
        'data' => [
            "game_type_id"=> "int #游戏类型ID",
            "type"=> "string #游戏类型",
            "name"=> "string #游戏名称",
            "next_percent"=> "int #下一级百分比",
            "percent"=> "int #百分比",
        ]
    ];


    public function run() {

        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $userId = $this->auth->getUserId();
        $level = DB::table('user')->where('id',$userId)->value('ranting');
        $type = $this->request->getQueryParam('type','GAME');
        $type_id = DB::table('game_menu')->where('switch','enabled')->where('type',$type)->value('id');
        $currentLevelxima = DB::table('xima_config as xc')
                ->leftJoin('game_menu as gm','gm.id','=','xc.game_type_id')
                ->where('gm.pid',$type_id)
                ->where('xc.level',$level)
                ->selectRaw('xc.game_type_id,gm.name,gm.type,percent')
                ->get()->toArray();
        $maxLevel = DB::table('user_level')->max('level');
        $data['currentLevel'] = $level;
        if($level == $maxLevel){
            $data['nextLevel'] = '';
            foreach ($currentLevelxima as &$value){
                $value->nextLevelPercent = '';
            }
        }else{
            $data['nextLevel'] = $level + 1;
            $nextLevelxima = DB::table('xima_config')->where('level',$data['nextLevel'])->pluck('percent','game_type_id')->toArray();
            foreach ($currentLevelxima as &$value){
                $value->next_percent = $nextLevelxima[$value->game_type_id];
            }
        }
        unset($value);

        $data['data'] = $currentLevelxima;

        return $data;

    }
};