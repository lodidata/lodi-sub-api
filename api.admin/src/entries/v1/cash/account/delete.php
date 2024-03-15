<?php

use Logic\Admin\BaseController;
use Logic\Admin\Log;

return new class() extends BaseController {
    const TITLE = "删除厅主设置的银行账户";
    const DESCRIPTION = "删除厅主设置的银行账户";

    const PARAMS = [
    ];
    const QUERY = [
    ];
    const SCHEMAS = [
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];
    
    public function run($id)
    {
        $this->checkID($id);
        /*---操作日志代码---*/
        $data = DB::table('bank_account')
            ->where('id', '=', $id)
            ->get()
            ->first();
        $data = (array)$data;
        /*---操作日志代码---*/
        if(\Model\BankAccount::where('id','=',$id)->update(['state'=>'deleted'])){
            /*================================日志操作代码=================================*/
            $types = [
                '1' => '银行转账',
                '2' => '支付宝',
                '3' => '微信',
                '4' => 'QQ钱包',
                '5' => '京东支付',
            ];
            $card = \Utils\Utils::RSADecrypt($data['card']);
            (new Log($this->ci))->create(null, null, Log::MODULE_CASH, '收款账号', '收款账号', '删除', 1, "类型:{$types[$data['type']]}/户名：{$data['name']}/账号： $card ");
            /*==================================================================================*/
            return $this->lang->set(0);
        }

        return $this->lang->set(-2);
    }
};
