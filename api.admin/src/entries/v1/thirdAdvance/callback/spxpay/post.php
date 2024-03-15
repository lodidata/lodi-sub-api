<?php

use Logic\Admin\BaseController;
use Logic\Transfer\ThirdParty\BASES;
return new class() extends BaseController {
    //前置方法
    protected $beforeActionList = [
//        'verifyToken', 'authorize',
    ];

    public function run() {
        $input = file_get_contents("php://input");
        $params = json_decode($input, true);
        $thirdWay = (array)\DB::table('transfer_config')
            ->where('code', 'SPXPAY')
            ->first();
        if (!$thirdWay) {
            die('pay type error');
        }
        try {
            $obj = new \Logic\Transfer\ThirdParty\SPXPAY();   //初始化类
            $obj->init($thirdWay, null); //初始化数据
            $data = $obj->dataDecrypt($params);
            if ($data) {
                $params = array_merge($params, $data);
            }
        } catch (\Throwable $e) {
            die($e->getMessage());
        }
        BASES::addLogByTxt($params);
        $transfer = new  Logic\Transfer\ThirdTransfer($this->ci);
        try{
            $transfer->anotherCallbackResult($params['merchant_order_no'], $params);
        }catch (\Throwable $e){
            $data = [
                'pay_type'  => 'spxpay',
                'method'    => $this->ci->request->getMethod(),
                'content'   => json_encode($params, JSON_UNESCAPED_UNICODE),
                'error'     => $e->getMessage()
            ];
            \DB::table('transfer_callback_failed')->insert($data);
            exit($e->getMessage());
        }
        exit('SUCCESS');
    }
};
