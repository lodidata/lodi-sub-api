<?php

use Logic\Admin\BaseController;
use Illuminate\Database\Capsule\Manager as DB;
return new class() extends BaseController
{
//    const STATE       = \API::DEPRECATED;
    const TITLE       = '根据彩种获取厅展示';
    const DESCRIPTION = '["map #status: 状态 enabled disabled，type: 快速还是标准"]';


    const QUERY       = [
        'lottery_id'   => 'int(required) #对应彩票id，比如：时时彩、11选5、快三...，见彩种列表接口',
    ];
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

        $query = DB::table('hall')
            ->where('lottery_id',$params['lottery_id'])
            ->where('hall_name','<>','pc房')
            ->where('hall_name','<>','传统');

        if(isset($params['hall_level'])){

            $query = $query->whereIn('hall_level',$params['hall_level']);
        }

        return $query->get(['id','hall_name'])->toArray();
    }

};
