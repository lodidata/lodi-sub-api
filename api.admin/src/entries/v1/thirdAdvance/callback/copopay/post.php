<?php
/**
 * Copopay 代付 回调
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
            $transfer->anotherCallbackResult($params['merchant_order'], $params);
        }catch (\Throwable $e){
            $data = [
                'pay_type'  => 'copopay',
                'method'    => $this->ci->request->getMethod(),
                'content'   => json_encode($params, JSON_UNESCAPED_UNICODE),
                'error'     => $e->getMessage()
            ];
            \DB::table('transfer_callback_failed')->insert($data);
            echo $e->getMessage();
            die;
        }
        echo 'success';
        die;
    }
};
