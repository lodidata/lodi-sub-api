<?php

use Logic\Admin\BaseController;

return new class() extends BaseController {
    //前置方法
    protected $beforeActionList = [
//        'verifyToken', 'authorize',
    ];

    public function run() {
        if (strtolower($this->request->getMethod()) == 'post'){
            $str = $this->request->getParams();
            $log = json_encode($str);
//            $log = $str = file_get_contents('php://input');  //不管啥格式统一用二进制流接收
        } else {
            $str = $this->request->getParams();  //不管啥格式统一用二进制流接收
            $log = json_encode($str);
        }
//        $log = '{"merchantNo":"1000008","orderNo":"1209115848582125388","amount":"1","code":"20003","msg":"\u8ba2\u5355\u5904\u7406\u5931\u8d25","sysOrderNo":"20181209120031434501","sign":"40440a394b503c1da8886cafa40d1d4a"}';
        /*============================日志操作代码================================*/
        DB::table('log')->insert(['user_id'=>1, 'ip'=>'127.0.0.1', 'context'=>$log]);

        if(!empty($str)){
            $id = \DB::table('transfer_order')
//                ->where('status', '=', 'pending')
                ->where('trade_no', '=', $str['orderNo'])
                ->pluck('id')->first();
            if(!empty($id)){
                $transfer = new  Logic\Transfer\ThirdTransfer($this->ci);
                $transfer->getTransferResult($id);
            }
        }
        exit('00000');//返回已经收到通知了
    }
};
