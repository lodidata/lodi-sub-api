<?php
/**
 * nn88pay 代付 回调
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
        $params    = $this->request->getParams();
        $params['signature']=$this->request->getHeader('signature')[0];
        $params['timestamp']=$this->request->getHeader('timestamp')[0];
        BASES::addLogByTxt($params);
        $transfer = new  Logic\Transfer\ThirdTransfer($this->ci);
        try{
            $transfer->anotherCallbackResult($params['outID'], $params);
        }catch (\Throwable $e){
            $data = [
                'pay_type'  => 'wq57pay',
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
