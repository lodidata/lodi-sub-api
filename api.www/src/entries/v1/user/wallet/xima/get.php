<?php
use Utils\Www\Action;
return new class extends Action {
    const TOKEN = true;
    const TITLE = "获取游戏打码量";
    const TAGS = "打码量";
    const SCHEMAS = [
        'last_settle' => 'date() #打码日期',
        'total_dml' => 'int(required) #总打码量',
        'amount' => 'decimal(required) #转换金额 如：10.02',
        'data' => [
            [
                'level' => 'int(required) #会员等级 1,2,3',
                'game_type' => 'string(required) #游戏类型 JOKER',
                'name' => 'string(required) #游戏名称 JOKER',
                'parent_type' => 'string(required) #类型前缀 GAME BY QP LIVE',
                'percent' => 'int(required) #百分比 500 500/10000',
            ]
        ],
    ];


    public function run() {

        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $userId = $this->auth->getUserId();
        $level = DB::table('user')->where('id',$userId)->value('ranting');
        $games = DB::table('xima_config as xc')
            ->leftJoin('game_menu as gm','gm.id','=','xc.game_type_id')
            ->where('xc.level',$level)
            ->where('gm.name','<>','')
            ->selectRaw('xc.level,xc.game_type,gm.name,xc.parent_type,percent')
            ->get([])->toArray();
        $user_dml = DB::table('user_dml')->where('user_id',$userId)->first();
        $data['last_settle'] = $user_dml->last_settle;
        $data['total_dml'] = 0;
        $data['amount'] = 0;

        foreach ($games as &$game){
            if(isset($user_dml->{$game->game_type}) && $user_dml->{$game->game_type}){
                $game->dml = $user_dml->{$game->game_type};
                $game->value = ceil(($game->dml * $game->percent)/10000);
            }else{
                $game->dml = 0 ;
                $game->value = 0 ;
            }

            $data['total_dml'] += $game->dml;
            $data['amount'] += $game->value;
        }

        $data['data'] = $games ;
        $data['amount'] = ceil($data['amount']) ;

        return $data;


    }
};