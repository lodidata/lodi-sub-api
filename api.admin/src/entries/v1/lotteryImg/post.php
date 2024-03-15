<?php

use Logic\Admin\Log;
use Model\Game3th;
use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const TITLE       = '彩种图标';
    const DESCRIPTION = '';

    const QUERY       = [

    ];
    
    const PARAMS      = [];
    const SCHEMAS     = [

    ];
//前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];

    public function run()
    {

        $params=$this->request->getParams();

        $sql = "update   lottery  set id= id  ";
        $set = "";
        if(isset($params['index_f_img']) && $params['index_f_img']){
            $set .=",index_f_img  = '{$params['index_f_img']}' ";
        }
        if(isset($params['index_c_img']) && $params['index_c_img']){
            $set .=",index_c_img = '{$params['index_c_img']}' ";
        }
        if(isset($params['buy_f_img']) && $params['buy_f_img']){
            $set .=",buy_f_img = '{$params['buy_f_img']}'";
        }
        if(isset($params['buy_c_img']) && $params['buy_c_img']){
            $set .=",buy_c_img = '{$params['buy_c_img']}'";
        }
        if(isset($params['open_img']) && $params['open_img']){
            $set .=",open_img = '{$params['open_img']}'";
        }
        $sql = $sql.$set;
        $sql .=" where id = {$params['id']}";

        DB::update($sql);
        $this->logs($params['id']);

        return [];
    }

    public function logs($id){
        $data=DB::table('lottery')
            ->select('name')
            ->where('id','=',$id)
            ->get()
            ->first();
        $data=(array)$data;

        (new Log($this->ci))->create(null, null, Log::MODULE_LOTTERY, '彩种图标', '彩种图标', '编辑', 1, "彩种名称:{$data['name']}");
    }

};
