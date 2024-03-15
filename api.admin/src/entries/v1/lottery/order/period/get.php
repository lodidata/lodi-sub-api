<?php

use Logic\Admin\BaseController;

return new class() extends BaseController
{
    const TITLE = '获取彩票设定信息';
    const DESCRIPTION = '';
    
    const QUERY = [

    ];
    
    const PARAMS = [];
    const SCHEMAS = [];
//前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run()
    {

        $allLottery = DB::table('lottery')
            //->whereRaw("FIND_IN_SET('enabled', state)")
            ->selectRaw('  id,name,state,type, open_type,pc_open_type, pid,alias,switch,code,sort,start_delay,FIND_IN_SET(\'standard\',state) AND FIND_IN_SET(\'standard\',switch) AS is_standard,FIND_IN_SET(\'fast\',state) AND FIND_IN_SET(\'fast\',switch) AS is_fast')
            ->orderBy('sort')
            ->get()
            ->toArray();
        $allLottery = array_map('get_object_vars', $allLottery);
        $result = [];

        $lotteryNameArr = [];
        foreach ($allLottery as $k => $v) {
            $id = $v['id'];
            $name = $v['name'];
            $lotteryNameArr[$id] = $name;
        }


        foreach ($allLottery as $k => $v) {
            if ($v['pid'] == 0) {
                continue;
            }
            //开关状态：开启还是关闭
            $v['is_fast'] = strpos($v['state'], 'fast') === false ? 0 : 1;
            $v['is_enabled'] = strpos($v['state'], 'enabled') === false ? 0 : 1;
            $v['is_chat'] = strpos($v['state'], 'chat') === false ? 0 : 1;
            $v['is_video'] = strpos($v['state'], 'video') === false ? 0 : 1;
            $v['is_standard'] = strpos($v['state'], 'standard') === false ? 0 : 1;

            //开关是否可以编辑（开关是否禁用）
            $v['support_standard'] = strpos($v['switch'], 'standard') === false ? 0 : 1;
            $v['support_fast'] = $v['support_chat'] = strpos($v['switch'], 'fast') === false ? 0 : 1;
            $v['support_video'] = strpos($v['switch'], 'video') === false ? 0 : 1;
            $v['support_enabled'] = 1;

            $v['lottery_name'] = $v['name'];
//            $v['state'] = $v['state'];
            $v['start_delay'] = $v['start_delay'];
            $v['p_name'] = isset($lotteryNameArr[$v['pid']]) ? $lotteryNameArr[$v['pid']] : '';
            $result[] = $v;
        }
        return $result;

    }

};
