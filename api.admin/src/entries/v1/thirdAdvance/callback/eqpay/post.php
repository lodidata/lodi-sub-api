<?php
/**
 * eqpay 代付 回调
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
        $jsonData   = $this->ci->request->getParams();
        BASES::addLogByTxt($jsonData);
        if(!is_array($jsonData))
        {
            $params = json_decode($jsonData, true);
        }else{
            $params = $jsonData;
        }

        $transfer = new  Logic\Transfer\ThirdTransfer($this->ci);
        try{
            $data = $params['data'] ?? [];
            $transfer->anotherCallbackResult($data['order_number'], $data);
        }catch (\Throwable $e){
            $data = [
                'pay_type'  => 'eqpey',
                'method'    => $this->ci->request->getMethod(),
                'content'   => json_encode($params),
                'error'     => $e->getMessage()
            ];
            \DB::table('transfer_callback_failed')->insert($data);
            exit($e->getMessage());
        }
        exit('SUCCESS');
    }
};
