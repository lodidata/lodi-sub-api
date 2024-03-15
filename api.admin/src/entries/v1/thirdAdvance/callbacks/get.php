<?php

use Logic\Admin\BaseController;

return new class() extends BaseController {
    //前置方法
    protected $beforeActionList = [
//        'verifyToken', 'authorize',
    ];

    public function run($codeid='') {
        //不处理代付的结果，通过查询接口去处理
        if(!empty($codeid)){
            $code_data = DB::table('transfer_config')->where('id', $codeid)->first();
            $code = $code_data->code;
            $class = "\Logic\Transfer\ThirdParty\\{$code}";
            if($code && class_exists($class) && is_callable(array($class,'callbackMsg'))) {
                $obj = new $class($this->ci);
                echo $obj->callbackMsg();
                exit;
            }
        }
        exit('00000');//返回已经收到通知了
    }
};
