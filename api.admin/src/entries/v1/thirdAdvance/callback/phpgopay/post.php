<?php

use Logic\Admin\BaseController;
use Logic\Transfer\ThirdParty\BASES;

return new class() extends BaseController {

    protected $beforeActionList = [
    ];

    public function run() {
        $params   = $this->ci->request->getParams();
        BASES::addLogByTxt($params);

        $transfer = new Logic\Transfer\ThirdTransfer($this->ci);
        try{
            $transfer->anotherCallbackResult($params['order_id'], $params);
        }catch (\Throwable $e){
            $data = [
                'pay_type'  => 'phpgopay',
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
