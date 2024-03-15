<?php
/**
 * jjpay 代付 回调
 */
use Logic\Admin\BaseController;
use Logic\Transfer\ThirdParty\BASES;

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
            $transfer->anotherCallbackResult($params['order'], $params);
        }catch (\Throwable $e){
            $data = [
                'pay_type'  => 'EASYPAY',
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
