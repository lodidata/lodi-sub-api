<?php
use Logic\Admin\BaseController;
use Logic\Admin\Log;
use Model\FundsDealLog;
return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '修改状态';
    const DESCRIPTION = '修改状态';

    const QUERY       = [
        'id' => 'int(required) #需要修改的 id',
    ];

    const PARAMS      = [];
    const SCHEMAS = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];
    public function run($id)
    {
        $this->checkID($id);
        $type   = $this->request->getParam('type');

        if($type == 'channel'){
            $table='pay_channel';
            $fun_name = "支付渠道:";
        }elseif($type == 'payment'){
            $table='payment_channel';
            $fun_name = "支付通道:";
        }else{
            return $this->lang->set(-2);
        }

        $check=DB::table($table)->where('id',$id)->first();
        if(!$check){
            return $this->lang->set(10014);
        }
        $status=$check->status == 0 ? 1:0;
        if ($check->status == 1) {
            $str = $fun_name.$id."更改[启用状态]，[启用]改位[停用]";
            $up_stat = "停用";
        } else {
            $str = $fun_name.$id."更改[启用状态]，[停用]改位[启用]";
            $up_stat = "启用";
        }
        $res=DB::table($table)->where('id',$id)->update(['status'=>$status]);
        if($res){
            (new Log($this->ci))->create(null, null, Log::MODULE_CASH, '第三方支付', $fun_name, $up_stat, 1, $str);
            return $this->lang->set(0);
        }
        return $this->lang->set(-2);
    }
};
