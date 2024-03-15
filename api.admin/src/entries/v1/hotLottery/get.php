<?php

//use Logic\Admin\Active as activeLogic;
use Logic\Admin\BaseController;
return new class() extends BaseController
{
//    const STATE       = \API::DRAFT;
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

        $params =$this->request->getParams();

        $query = DB::table('hot_lottery')->select(['hot_lottery.*','l.name'])->leftJoin('lottery as l','hot_lottery.lottery_id','=','l.id');
        if(isset($params['timeType']) && $params['timeType']){
            $query->where('timeType',$params['timeType']);
        }
        $data = $query->get()->toArray();
        $time = DB::table('hot_lottery_time')->get()->toArray();
        return ['data'=>$data,'time'=>$time];

    }


};
