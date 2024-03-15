<?php
/**
 * mpay 代付 回调
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
        BASES::addLogByTxt($jsonData);

        if(!is_array($jsonData))
        {
            $params = json_decode($jsonData, true);
        }
        $order_id = '';
        if(isset($params['data']['orderNo'])){
            $order_id = $params['data']['orderNo'];
        }

        $transfer = new  Logic\Transfer\ThirdTransfer($this->ci);
        try{
            $transfer->anotherCallbackResult($order_id, $params);
        }catch (\Throwable $e){
            $data = [
                'pay_type'  => 'MPAY',
                'method'    => $this->ci->request->getMethod(),
                'content'   => json_encode($params, JSON_UNESCAPED_UNICODE),
                'error'     => $e->getMessage()
            ];
            \DB::table('transfer_callback_failed')->insert($data);
            exit($e->getMessage());
        }
        exit('ok');
    }


};
