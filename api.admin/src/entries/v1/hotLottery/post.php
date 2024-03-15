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

        $data = $params['data'];


        foreach ($data as $value) {
            $set = [];
            $set2 = [];
            if (isset($value['sort']) && $value['sort']) {
                $set=['sort'=>$value['sort']];
            }
            if (isset($value['state'])) {
                $set2=['state'=>$value['state']];
            }
            DB::table('hot_lottery')->where('id',$value['id'])->where('type',$value['type'])->update(array_merge($set,$set2));



        }
        $time = $params['time'];
        foreach ($time as $value){
            $set =$set2=$set3=$set4= [];
            if (isset($value['start_time1']) && $value['start_time1']) {
                $set=['start_time1'=>$value['start_time1']];
            }
            if (isset($value['start_time2']) && $value['start_time2']) {
                $set2=['start_time2'=>$value['start_time2']];
            }
            if (isset($value['end_time1']) && $value['end_time1']) {
                $set3=['end_time1'=>$value['end_time1']];
            }
            if (isset($value['end_time2']) && $value['end_time2']) {
                $set4=['end_time2'=>$value['end_time2']];
            }
            DB::table('hot_lottery_time')->update(array_merge($set,$set2,$set2,$set4));
        }
        return [];

    }


};
