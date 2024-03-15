<?php

use Logic\Admin\BaseController;

return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '会员出款详情';
    const DESCRIPTION = '获取用户出款详情';

    const QUERY       = [
        'id' => 'int(required) #提款申请id'
    ];

    const PARAMS      = [];
    const SCHEMAS     = [
        [
            'id'                   => 'int    #记录ID',
            'money'                => 'int #申请金额',
            'created'              => 'datetime()    #申请时间',
            'ip'                   => 'string #IP',
            'receive_bank_info'    => 'string  #取款银行信息',
            'today_withdraw_times' => 'int   #今日取款次数',
            'today_withdraw_money' => 'int   #今日取款金额',
            'confirm_time'         => 'datetime()   #最后确认时间',
            'process_uname'        => 'string  #处理人',
        ],
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];
    public function run($id)
    {
        $this->checkID($id);

        $data = \DB::connection('slave')->table('funds_withdraw as withdraw')
            ->leftJoin('user','withdraw.user_id','=','user.id')
            ->leftJoin('level','user.ranting','=','level.id')
            ->leftJoin('user_agent as agent','user.id','=','agent.user_id')
            ->leftJoin('label','user.tags','=','label.id')
            ->leftJoin('admin_user as admin','withdraw.process_uid','=','admin.id')
            ->where('withdraw.id','=',$id)
            ->first([
                'withdraw.id',
                'withdraw.admin_fee',
                'withdraw.confirm_time',
                'withdraw.created',
                'withdraw.fee',
                'withdraw.ip',
                'withdraw.money',
                'withdraw.marks',
                'withdraw.memo',
                'withdraw.previous_time',
                'admin.username as process_uname',
                'withdraw.receive_bank_info',
                'withdraw.state',
                'withdraw.status',
                'withdraw.today_money as today_withdraw_money',
                'withdraw.today_times as today_withdraw_times',
                'withdraw.trade_no',
                'withdraw.user_id',
                'withdraw.user_type',
            ]);
        $data && ($total = \Model\FundsDeposit::where('user_id','=',$data->user_id)
            ->where('status','=','paid')->sum('money'));
        $data && ($withdraw = \Model\FundsWithdraw::where('user_id','=',$data->user_id)
            ->where('status','=','paid')->sum('money'));
        $data->deposit_total = $total ?? 0;
        $data->withdraw_total = $withdraw ?? 0;
        $data = (array)$data;
        $data = \Utils\Utils::DepositPatch($data);
        $data['receive_bank_info'] = json_decode($data['receive_bank_info']);
        return (array)$data;
    }
};
