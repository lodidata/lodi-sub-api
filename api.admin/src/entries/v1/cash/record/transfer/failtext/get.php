<?php

use Logic\Admin\BaseController;
use Logic\Set\SystemConfig;

return new class() extends BaseController {
    const STATE = '';

    const TITLE = '余额转换失败补单说明';

    const DESCRIPTION = '';

    const QUERY = [

    ];

    const PARAMS = [];

    const SCHEMAS = [
        [

        ]
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run() {
        //获取有自动补余额的游戏
        $gameInfo = DB::table('game_menu')
                      ->whereRaw('status=? and pid!=?',['enabled',0])
                      ->select(['type'])
                      ->get()
                      ->toArray();

        $gameStr = "";
        if(empty($gameInfo)) {
            $return = [
                'game' => $gameStr
            ];

            return $return;
        }

        foreach($gameInfo as $value) {
            $class = 'Logic\GameApi\Game\\'.strtoupper($value->type);
            if(class_exists($class) && method_exists($class, 'checkMoney')) {
                $return = (new $class($this->ci))->checkMoney();
                //checkMoney空为手动补余额
                if(empty($return)){
                    $gameStr .= $value->type . ',';
                }
            }
        }
        $gameStr = rtrim($gameStr,',');

        $return = [
            'game' => $gameStr
        ];

        return $return;
    }
};
