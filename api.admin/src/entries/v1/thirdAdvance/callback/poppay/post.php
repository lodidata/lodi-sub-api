<?php
/**
 * poppay 代付 回调
 */
use Logic\Admin\BaseController;
use Logic\Transfer\ThirdParty\BASES;

return new class() extends BaseController {
    //前置方法
    protected $beforeActionList = [
//        'verifyToken', 'authorize',
    ];

    public function run() {
        $params   = $this->ci->request->getParams();
        BASES::addLogByTxt($params);

        $transfer = new  Logic\Transfer\ThirdTransfer($this->ci);
        try{
            $transfer->anotherCallbackResult($params['merchant_trade_no'], $params);
        }catch (\Throwable $e){
            $data = [
                'pay_type'  => 'poppay',
                'method'    => $this->ci->request->getMethod(),
                'content'   => json_encode($params),
                'error'     => $e->getMessage()
            ];
            \DB::table('pay_callback_failed')->insert($data);
            die;
        }
        echo 'SUCCESS';
        die;
    }
};
