<?php
/**
 * zepay 代付 回调
 */
use Logic\Admin\BaseController;
use Logic\Transfer\ThirdParty\BASES;
use Logic\Recharge\Recharge;
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
            $transfer->anotherCallbackResult($params['order_no'], $params);
        }catch (\Throwable $e){
            $data = [
                'pay_type'  => 'epay',
                'method'    => $this->ci->request->getMethod(),
                'content'   => json_encode($params),
                'error'     => $e->getMessage()
            ];
            \DB::table('transfer_callback_failed')->insert($data);
            exit($e->getMessage());
        }
        exit('success');
    }
};
