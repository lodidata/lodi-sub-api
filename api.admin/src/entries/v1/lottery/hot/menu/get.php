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
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run()
    {
        $params = $this->request->getParams();
        (new BaseValidate([
            'timeType'=>'require|in:1,2'
        ],
            [],
            ['timeType'=>'时间段']
        ))->paramsCheck('',$this->request,$this->response);
        $query = HotLottery::from('hot_lottery as hl')->leftJoin('lottery as l','hl.lottery_id','=','l.id')
            ->where('hl.timeType',$params['timeType'])
            ->selectRaw('hl.*,l.name');

        if(isset($params['lottery_pid']) && !empty($params['lottery_pid'])){

            $lotteryIds = DB::table('lottery')->where('pid',$params['lottery_pid'])->selectRaw('id')->pluck('id')->toArray();
            $query = $query->whereIn('hl.lottery_id',$lotteryIds);
        }
//        $query = isset($params['lottery_pid']) && !empty($params['lottery_pid']) ? $query->where('hl.lottery_id',$params['lottery_id']) : $query ;

        $query = isset($params['lottery_id']) && !empty($params['lottery_id']) ? $query->where('hl.lottery_id',$params['lottery_id']) : $query ;

        $lottery = $query->where('hl.state',0)->get()->toArray();

        return $lottery;


    }


};
