<?php
/**
 * XPAY 代付 回调
 */
use Logic\Admin\BaseController;
use Logic\Transfer\ThirdParty\BASES;

return new class() extends BaseController {
    //前置方法
    protected $beforeActionList = [
//        'verifyToken', 'authorize',
    ];

    public function run() {
        $params = $this->ci->request->getParams();
        BASES::addLogByTxt($params);
        $transfer = new  Logic\Transfer\ThirdTransfer($this->ci);
        $return = $transfer->callbackResult($params['mer_order_no'], $params);
        if($return['code'] == 0){
            echo 'success';
        }else{
            $data = [
                'pay_type'  => 'xpay',
                'method'    => $this->ci->request->getMethod(),
                'content'   => json_encode($params, JSON_UNESCAPED_UNICODE),
                'error'     => $return['msg']
            ];
            \DB::table('transfer_callback_failed')->insert($data);
            echo $return['msg'];
        }
        die;
    }
};
