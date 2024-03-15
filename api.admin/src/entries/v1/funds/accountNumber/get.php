<?php

use Logic\Admin\BaseController;

return new class() extends BaseController
{
    const TITLE       = '第三方需要获取的收款银行信息';
    const QUERY       = [
        'order_number' => 'string() #订单ID',
    ];
    
    const PARAMS      = [];
    const SCHEMAS     = [
    ];
    public function run()
    {
        $ips = \DB::table('pay_site_config')->value('ip');
        if($ips){
            $ips = explode(',',$ips);
        }else{
            $ips = [];
        }
        $data = [];
        $ip = \Utils\Client::getIp();
        if(in_array($ip,$ips) || true) {
//            $order_number = $this->request->getParam('order_number');
//            if(\Model\FundsDeposit::where('trade_no',$order_number)->value('id')){//证明有此订单
                $data = \Model\BankAccount::where('state','enabled')->where('type',1)->orderBy('sort','ASC')->get()->toArray();
                $bands = \Model\Bank::get()->toArray();
                $bands = array_column($bands,'code','id');
                foreach ($data as &$v) {
                    $v['bank_name'] = $this->lang->text($bands[$v['bank_id']]);
                }
                unset($v);
//            }
        }
        return $data;
    }
};
