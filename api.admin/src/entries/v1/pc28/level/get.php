<?php

use Logic\Admin\BaseController;

return new class() extends BaseController {
    const TITLE = '彩票种类';
    const DESCRIPTION = '';

    const QUERY = [

    ];

    const PARAMS = [];
    const SCHEMAS = [];

    //  前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run() {
        $type = $this->request->getParam('type');

        //  第三方游戏类型 101-视讯(直播) 102-电子 103-体育
        if (in_array($type,['game','live','sport'])) {
            $levelArray = DB::table('partner')
                            ->select('id as level', 'id as plat_id', 'name')
                            ->where('3th_name', '=', $type)
                            ->get()
                            ->toArray();

            return $levelArray;
        }

        if ($type == 'lhc' || $type == 'bjc') {
            $levelArray = [
                ['level' => 5, 'name' => '传统模式', 'plat_id' => 0],
            ];

            return $levelArray;
        }

        if ($type == 'k3' || $type == 'ssc' || $type == '11x5') {
            $levelArray = [
                ['level' => 2, 'name' => '保本厅', 'plat_id' => 0],
                ['level' => 1, 'name' => '回水厅', 'plat_id' => 0],
                ['level' => 3, 'name' => '高赔率厅', 'plat_id' => 0],
                ['level' => 5, 'name' => '传统模式', 'plat_id' => 0],
            ];

            return $levelArray;
        }

        $levels = DB::table('hall')
                    ->selectRaw('DISTINCT `hall_level`')
                    ->get()
                    ->toArray();

        $levelSpec = [
            1 => ['level' => 1, 'name' => '回水厅', 'plat_id' => 0],
            2 => ['level' => 2, 'name' => '保本厅', 'plat_id' => 0],
            3 => ['level' => 3, 'name' => '高赔率厅', 'plat_id' => 0],
            //4 => ['level' => 4, 'name' => 'PC房', 'plat_id' => 0],
            5 => ['level' => 5, 'name' => '传统模式', 'plat_id' => 0],
            6 => ['level' => 6, 'name' => '直播', 'plat_id' => 0],
        ];

        $levelArray = [];
        foreach ($levels as $level) {
            if (!isset($levelSpec[$level->hall_level])) {
                continue;
            }
            $levelArray[] = $levelSpec[$level->hall_level];
        }

        return $levelArray;
    }

};
