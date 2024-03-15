<?php

use Logic\Admin\BaseController;

return new class() extends BaseController
{
    const STATE = '';

    const TITLE = '导出付款情况列表';

    const DESCRIPTION = '申请金额-手续费-行政费-扣优惠=实际';

    const QUERY = [
        'signature' => 'string(required) #下载签名token值',
        'nonce' => 'string(required) #',
        'time' => 'string(required) #',
        'uuid' => 'string(required) #',
        'username' => 'string() # 用户名',
        'order_no' => 'string() # 订单号',
        'time_start' => 'datetime() #时间起点',
        'time_end' => 'datetime() #时间结束点',
        'status' => 'string() # paid:支付成功, prepare:准备支付，failed 支付失败'
    ];


    const PARAMS = [];

    const SCHEMAS = [
    ];

    protected $title = array(
        '用户名','订单号','付款金额','付款银行','存款时间','状态','操作者','备注'
    );

    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];
    public function run()
    {
        $trade_no = $this->request->getParam('order_no');
        $username = $this->request->getParam('member_name');
        $admin_user = $this->request->getParam('process_user');
        $status = $this->request->getParam('status') ?? "paid,prepare,refused";
        $stime = $this->request->getParam('time_start');
        $etime = $this->request->getParam('time_end');
        $page = $this->request->getParam('page') ?? 1;
        $size= $this->request->getParam('page_size') ?? 1;

        $query = \DB::connection('slave')->table('funds_withdraw as withdraw')
            ->leftJoin('user','withdraw.user_id','=','user.id')
            ->leftJoin('level','user.ranting','=','level.id')
            ->leftJoin('user_agent as agent','user.id','=','agent.user_id')
            ->leftJoin('label','user.tags','=','label.id')
            ->leftJoin('admin_user as admin','withdraw.process_uid','=','admin.id');

        $admin_user && $query->where('admin.username','=',$admin_user);
        $trade_no && $query->where('withdraw.trade_no','=',$trade_no);
        $username && $query->where('user.name','=',$username);
        $status && $query->whereRaw('FIND_IN_SET(withdraw.status,"'.$status.'")');
        $stime && $query->where('withdraw.created','>=',$stime);
        $etime && $query->where('withdraw.created','<=',$etime);
        global $payment_export;
        $payment_export = [];
        $query->select([
            'user.name as user_name',
            'withdraw.trade_no',
            'withdraw.money',
            'withdraw.receive_bank_info',
            'withdraw.confirm_time',
            'withdraw.status',
            'admin.username as process_uname',
            'withdraw.memo',
        ])->orderBy('withdraw.id')->chunk(100,function ($payment){
            foreach ($payment as $val){
                $s = json_decode($val->receive_bank_info,true);
                $receive = \Utils\Utils::DepositPatch($s);
                $val->receive_bank_info = ($receive['bank']??'').' '.($receive['name']??'').' '.($receive['card']??'').' '.($receive['address'] ?? '');
                global $payment_export;
                $payment_export[] = $val;
            }
        });
        \Utils\Utils::exportExcel('payment',$this->title,$payment_export);
    }
};
