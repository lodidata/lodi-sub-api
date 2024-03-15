<?php

use Logic\Admin\BaseController;
use Utils\Utils;

return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '线上入款导出';
    const DESCRIPTION = '导出线上充值列表';

    const QUERY       = [
        'user_name'  => 'string()   #用户名称',
        //'name'       => 'string(required) #商户名称id',
        'trade_no'   => 'string()   #订单号',
        'ranting'    => 'string()   #用户等级，查询多个，逗号(,)分隔',
        'pay_scene'  => 'enum[wx,alipay,unionpay,qq,tz,jd] #支付类型/场景[wx,alipay,unionpay,qq,tz,jd]',
        'status'     => 'enum[paid,pending,failed]   #交易状态,支付状态(paid(已支付), pending(待支付),failed(支付失败))',
        //'channel'    => 'int()   #渠道ID',
        'date_from'  => 'date()   #开始时间',
        'date_to'    => 'date() #结束时间',
        'money_from' => 'int()  #存款金额，起始',
        'money_to'   => 'int()    #存款金额，结束',
        'name'       => 'string() #商户名称',
    ];

    const PARAMS      = [];
    const SCHEMAS     = [
        [
            "trade_no"      => "string #订单号",
            "ranting"       => "int",
            "agent_name"    => "string",
            "user_name"     => "string",
            "app_id"        => "int #商户编号",
            "vender_name"   => "string #商户名称",
            "channel_id"    => "int #渠道id",
            "pay_no"        => "string #外部交易号",
            "money"         => "int",
            "coupon_money"  => "int",
            "recharge_time" => "string #交易时间",
            "status"        => "enum[pending,failed,paid] #[pending,failed,paid]",
            "ip"            => "string #存款ip",
            "memo"          => "string #备注",
            "state"         => "enum[show,new,auto,online] #[show,new,auto,online]",
        ]
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];
    protected $title = [
        'trade_no' => '外部订单号',
        'pay_name' => '商户名称',
        'payment_channel' => '支付渠道',
        'vender_name' => '支付类型',
        'user_name' => '会员账号',
        'origin_str' => '来源',
        'ranting' => '等级',
        'deposit_money' => '存入金额',
        'money' => '实付金额',
        'coupon_money' => '自动赠送优惠金额',
        'created' => '申请时间',
        'over_time' => '过期时间',
        'recharge_time' => '回调时间',
        'status' => '状态'
    ];
    protected $en_title = [
        'trade_no' => 'External ref. no.',
        'pay_name' => 'merchant name',
        'payment_channel' => 'Payment channel',
        'vender_name' => 'Payment type',
        'user_name' => 'member ID',
        'origin_str' => 'sort',
        'ranting' => 'Level',
        'deposit_money' => 'request amount',
        'money' => 'actual amount',
        'coupon_money' => 'auto send bonus',
        'created' => 'Application time',
        'over_time' => 'expire time',
        'recharge_time' => 'reflected time',
        'status' => 'STATUS'
    ];
    protected $pay_status = [
        'paid' => '已支付',
        'pending' => '待支付',
        'failed' => '支付失败',
    ];
    public function run()
    {
        $pay_channel = $this->request->getParam('pay_channel');
        $pay_scence = $this->request->getParam('pay_scence');
        $trade_no = $this->request->getParam('trade_no');
        $username = $this->request->getParam('user_name');
        $status = $this->request->getParam('status');
        $ranting = $this->request->getParam('ranting') ? explode(',',$this->request->getParam('ranting')) : false;
        $smoney = $this->request->getParam('money_from');
        $emoney = $this->request->getParam('money_to');
        $stime = $this->request->getParam('date_from', date('Y-m-d'));
        $etime = $this->request->getParam('date_to', date('Y-m-d 23:59:59'));
        $cstime = $this->request->getParam('create_from');
        $cetime = $this->request->getParam('create_to');


        $query = DB::connection('slave')->table('funds_deposit as deposit')
            ->leftJoin('user','deposit.user_id','=','user.id')
            ->leftJoin('user_level as level','user.ranting','=','level.level')
//            ->leftJoin('user_agent as agent','user.id','=','agent.user_id')
            ->leftJoin('admin_user as admin','deposit.process_uid','=','admin.id')
            ->whereRaw('FIND_IN_SET("online",deposit.state)');

        if($pay_scence){
            $pay = new Logic\Recharge\Pay($this->ci);
            $types =  $pay->payConfig();
            $query->where('deposit.deposit_type','=',$types[$pay_scence]['id']);
        }
        $pay_channel && $query->where('deposit.pay_bank_id','=',$pay_channel);
        $trade_no && $query->where('deposit.trade_no','=',$trade_no);
        $username && $query->where('deposit.name','=',$username);
        $status && $query->whereRaw('FIND_IN_SET("'.$status.'",deposit.status)');
        $smoney && $query->where('deposit.money','>=',$smoney);
        $emoney && $query->where('deposit.money','<=',$emoney);
        $ranting && $query->whereIn('user.ranting',$ranting);
//        $stime && $query->where('deposit.recharge_time','>=',$stime." 00:00:00");
//        $etime && $query->where('deposit.recharge_time','<=',$etime ." 23:59:59");
        $stime && $query->where('deposit.created','>=',$stime);
        $etime && $query->where('deposit.created','<',$etime);
        $cstime && $query->where('deposit.created','>=',$cstime);
        $cetime && $query->where('deposit.created','<',$cetime);

        $data = $query->orderBy('deposit.created','DESC')->get([
            'deposit.active_id',
            'deposit.active_name',
            'deposit.active_id_other',
            'deposit.coupon_money',
            'deposit.created',
            'deposit.over_time',
            'deposit.deposit_type',
            'deposit.money',
            'deposit.receive_bank_info as channel_name',
            'deposit.recharge_time',
            'deposit.status',
            'deposit.state',
            'deposit.origin',
            DB::raw("concat(deposit.trade_no,'') as trade_no "),
            //'deposit.pay_no',
            'deposit.name as user_name',
            'level.name as ranting',
//            'vender.name as vender_name',
            'deposit.payment_id',
        ])->toArray();

        $payChannelArr = DB::connection('slave')
            ->table('payment_channel')
            ->leftJoin('pay_channel', 'pay_channel.id', '=', 'payment_channel.pay_channel_id')
            ->pluck('pay_channel.name', 'payment_channel.id')->toArray();

        $origins = [0=>'',1=>'PC',2=>'H5',3=>'APP',4=>'APP'];
        foreach ($data as $val){
            $val->trade_no = "=\"$val->trade_no\"";
            $val->money = bcdiv($val->money,100,2);
            $val->deposit_money = $val->money;
            $val->coupon_money = bcdiv($val->coupon_money,100,2);
            if($val->status == 'paid')
                $val->in_money = bcadd($val->money ,$val->coupon_money,2);
            else
                $val->in_money = 0;
                $val->channel_name = json_decode($val->channel_name,true);

            $val->origin_str = $origins[$val->origin] ? : '';
            $val->vender_name = $val->channel_name['vender'];
            $val->pay_name = $val->channel_name['pay'];
            $val->status = $this->pay_status[$val->status] ?? '';
            $val->payment_channel = $payChannelArr[$val->payment_id] ?? '';
            unset($val->channel_name);
        }
        foreach ($this->en_title as &$value){
            $value = $this->lang->text($value);
        }
        array_unshift($data,$this->en_title);
        Utils::exportExcel('userDeposit',$this->title,$data);
    }
};
