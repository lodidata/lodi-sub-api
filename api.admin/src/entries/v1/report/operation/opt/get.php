<?php

use Logic\Admin\BaseController;

return new class() extends BaseController {
    const TITLE = '游戏运营报表-下拉筛选框';
    const QUERY = [

    ];

    const PARAMS = [];
    const SCHEMAS = [

    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run()
    {
        $game_menu = \DB::connection('slave')->table('game_menu')->selectRaw("id,pid,type,`name`,alias,`rename`,status")->get()->toArray();

        $game_type = [];    //游戏类型: 只取pid=0的一级分类
        $operator = [];    //运营商： 只去pid>0的alias字段
        foreach ($game_menu as $g) {
            if ($g->pid == 0) {
                $game_type[$g->rename] = $g->type;
            }
            if ($g->pid > 0) {
                $operator[$g->alias] = $g->alias;
            }
        }

        $result = [
            'game_type' => $game_type,       //游戏类型
            'game_menu' => $operator,        //游戏运营商
        ];
        return $result;
    }
};