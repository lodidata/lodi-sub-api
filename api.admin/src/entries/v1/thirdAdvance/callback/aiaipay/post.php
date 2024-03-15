<?php
/**
 * aiaipay 代付 回调
 */
use Logic\Admin\BaseController;
use Logic\Transfer\ThirdParty\BASES;

return new class() extends BaseController {
    //前置方法
    protected $beforeActionList = [
//        'verifyToken', 'authorize',
    ];

    public function run() {
        $jsonData    =file_get_contents("php://input");
        $params = (array)json_decode($jsonData);
        BASES::addLogByTxt($params);

        $transfer = new  Logic\Transfer\ThirdTransfer($this->ci);
        try{
            $transfer->anotherCallbackResult($params['merchant_order'], $params);
        }catch (\Throwable $e){
            $data = [
                'pay_type'  => 'aiaipay',
                'method'    => $this->ci->request->getMethod(),
                'content'   => json_encode($params, JSON_UNESCAPED_UNICODE),
                'error'     => $e->getMessage()
            ];
            \DB::table('transfer_callback_failed')->insert($data);
            echo $e->getMessage();
            die;
        }
        echo 'SUCCESS';
        die;
    }
};
