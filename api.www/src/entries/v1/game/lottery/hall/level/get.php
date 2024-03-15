<?php

use Logic\Admin\BaseController;

return new class() extends BaseController {
    const HIDDEN = true;
    const TITLE = '彩票种类';
    const DESCRIPTION = '';
    const TAGS = "彩票";
    const QUERY = [

    ];
    const SCHEMAS = [

    ];
//    前置方法
   protected $beforeActionList = [
       'verifyToken', 'authorize'
   ];

    public function run() {

        $common = [
            ['level' => 1, 'name' => '回水厅'],
            ['level' => 2, 'name' => '保本厅'],
            ['level' => 3, 'name' => '高赔率厅'],
            ['level' => 5, 'name' => '传统模式'],
        ];

        $levels = DB::table('hall')
                    ->selectRaw('DISTINCT `hall_level`')
                    ->get()
                    ->toArray();

        $level_spec = [
            1 => ['level' => 1, 'name' => '回水厅'],
            2 => ['level' => 2, 'name' => '保本厅'],
            3 => ['level' => 3, 'name' => '高赔率厅'],
            //4 => ['level' => 4, 'name' => 'PC房'],
            5 => ['level' => 5, 'name' => '传统模式'],
            6 => ['level' => 6, 'name' => '直播'],
        ];

        $hall_level = [];
        foreach ($levels as $level) {
            if (!isset($level_spec[$level->hall_level])) {
                continue;
            }

            $hall_level[] = $level_spec[$level->hall_level];
        }

        $level_array = [
            [
                'type' => 'lottery', 'type2' => 'lottery', 'name' => '彩票', 'children' => [
                [
                    'type' => 'XYRB', 'type2' => 'pc28', 'name' => '幸运28类', 'children' => $hall_level,
                ],
                [
                    'type' => 'SSC', 'type2' => 'ssc', 'name' => '时时彩类', 'children' => $common,
                ], [
                    'type' => 'SC', 'type2' => 'sc', 'name' => '赛车类', 'children' => $common,
                ], [
                    'type' => 'KS', 'type2' => 'k3', 'name' => '快3类', 'children' => $common,
                ], [
                    'type' => 'SYXW', 'type2' => '11x5', 'name' => '11选5类', 'children' => $common,
                ], [
                    'type' => 'LHC', 'type2' => 'lhc', 'name' => '六合彩', 'children' => [['level' => 5, 'name' => '传统模式']],
                ]
            ],
            ],
        ];

        return $level_array;
    }
};
