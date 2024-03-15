<?php
use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const TITLE       = '第三方代付转账申请';
    const DESCRIPTION = '第三方代付';
    
    const QUERY       = [

    ];
    
    const PARAMS      = [];
    const SCHEMAS     = [
        "balance"=>'实际转账余额'
    ];
//前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];

    public function run($id=null)
    {
        $this->checkID($id);
        $result = (new Logic\Transfer\ThirdTransfer($this->ci))->getTransferResult($id);
        return $this->lang->set($result['code'],[$result['msg']],['balance'=>$result['balance']]);
    }
};
