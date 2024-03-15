<?php

use Utils\Www\Action;

/**
 * 个人报表：菜单列表
 *
 */
return new class extends Action
{
    const TOKEN = true;
    const TITLE = "个人报表-菜单列表";
    const TAGS = "个人报表";
    const SCHEMAS = [
        [
            'id'    => "int(required) #游戏菜单ID",
            'type'  => "string(required) #游戏类型 KAIYUN",
            'name'  => "string(required) #游戏名称"
        ]
    ];
    public function run()
    {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $data=DB::table('game_menu')
            ->select(['id','type','name'])
            ->where('pid','=', 0)
            ->where('type','<>','CP')
            ->where('switch','enabled')
            ->get()
            ->toArray();

        return $this->lang->set(0, [], $data);
    }

};