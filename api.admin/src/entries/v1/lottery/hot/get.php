<?php

use Logic\Admin\BaseController;
use Model\Admin\HotLottery;
use lib\validate\BaseValidate;
return new class() extends BaseController
{
    const TITLE       = '热门彩种';
    const DESCRIPTION = '';
    
    const QUERY       = [
    ];

    const PARAMS      = [];
    const SCHEMAS     = [
        "day"=>[
            'desc'=>'时间段1',
            'data'=>[
                "id"=>'首页菜单id',
                "lottery_id"=>"彩种id",
                "name"=>"彩种名称",
                "sort"=>"排序",
                "state"=>"状态",
                "timeType"=>"时间段",
                "type"=>"彩种类型"
            ]
        ]
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run()
    {

        $lottery['day'] = HotLottery::from('hot_lottery as hl')->leftJoin('lottery as l','hl.lottery_id','=','l.id')
            ->selectRaw('hl.*,l.name')
            ->where('hl.state',1)
            ->where('hl.timeType',1)
            ->orderBy('hl.sort')
            ->get()->toArray();

        $lottery['night'] = HotLottery::from('hot_lottery as hl')->leftJoin('lottery as l','hl.lottery_id','=','l.id')
            ->selectRaw('hl.*,l.name')
            ->where('hl.state',1)
            ->where('hl.timeType',2)
            ->orderBy('hl.sort')
            ->get()->toArray();

        return $lottery;


    }


};
