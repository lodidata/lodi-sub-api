<?php
use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const TITLE       = '第三方代付账户余额查询';
    const DESCRIPTION = '第三方代付账户余额查询';
    
    const QUERY       = [

    ];
    
    const PARAMS      = [];
    const SCHEMAS     = [
        "balance"=>'第三方余额'
    ];
//前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];

    public function run($thirdId)
    {
        $this->checkID($thirdId);
        //是否开启小数点
        try{
            $result = (new Logic\Transfer\ThirdTransfer($this->ci))->getThirdBalance($thirdId);
        }catch(\Throwable $e){
            return $this->lang->set(886, [$e->getMessage()], ['balance' => 0]);
        }
        $balance = 0;
        if(isset($result['balance'])){
            $balance = $result['balance'];
        }

        return $this->lang->set($result['code'], [$result['msg']], ['balance' => $balance]);
    }
};
