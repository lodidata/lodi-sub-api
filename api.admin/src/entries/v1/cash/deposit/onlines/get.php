<?php

use Logic\Admin\BaseController;
use Model\FundsDealLog;

return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '线上入款列表';
    const DESCRIPTION = '获取线上充值列表';
    
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
        'page'       => 'int(required)   #页码',
        'page_size'  => 'int(required)    #每页大小',
    ];
    
    const PARAMS      = [];
    const SCHEMAS     = [
        [
            "id"            => "int",
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
        $page = $this->request->getParam('page',1);
        $size= $this->request->getParam('page_size',20);
        $sort_field = $this->request->getParam('sort_field', '');    // 要排序的字段
        $sort_type = $this->request->getParam('sort_type', 'asc');   // 排序的方式: asc-升序，desc-降序
        $channel_no = $this->request->getParam('channel_no');        // 用户渠道号
        $payment_id = $this->request->getParam('payment_id');        // 渠道号

        if ($page == 1) {
            $this->redis->set('admin:UnreadNum1', date('Y-m-d H:i:s'));
        }

        $query = \DB::connection('slave')->table('funds_deposit as deposit')
            ->leftJoin('user','deposit.user_id','=','user.id')
            ->leftJoin('user_level as level','user.ranting','=','level.level')
//            ->leftJoin('user_agent as agent','user.id','=','agent.user_id')
            ->leftJoin('admin_user as admin','deposit.process_uid','=','admin.id')
            ->whereRaw('FIND_IN_SET("online",deposit.state)');

        $pay = new Logic\Recharge\Pay($this->ci);

        if($pay_scence){
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

        $channel_no && $query->where('user.channel_id', '=', $channel_no);

        if($payment_id) {
            $channel_ids = \DB::table('payment_channel')->where("pay_channel_id", $payment_id)->pluck('id');
            $query->whereIn('deposit.payment_id', $channel_ids);
        }

        $sum = clone $query;
        $total = $sum->count();
        //当前搜索统计
        $paid_query = clone $query;
        $current_paid_count = $paid_query->where('deposit.status','=','paid')->count();
        $money_query = clone $query;
        $current_money_count = $money_query->where('deposit.status', '=','paid')->sum('money');
        $paid_fail_query = clone $query;
        $current_paid_fail_count = $paid_fail_query->where('deposit.status', '!=', 'paid')->count();
        $money_fail_query = clone $query;
        $current_money_fail_count = $money_fail_query->where('deposit.status', '!=', 'paid')->sum('money');
        $coupon_money_query = clone $query;
        $current_coupon_money_count = $coupon_money_query->where('deposit.status', '=','paid')->sum('coupon_money');
        $current_account_count = $current_money_count;
        //根据页面指定字段排序
        if (!empty($sort_field)) {
            $sort_str = 'deposit.'.$sort_field;
            $sort_t = $sort_type;
        } else {
            $sort_str = 'deposit.created';    //默认的排序方式
            $sort_t = 'DESC';
        }

        $data = $query->orderBy($sort_str,$sort_t)->forPage($page,$size)->get([
            'deposit.id',
            'deposit.active_apply',
            'deposit.active_id',
            'deposit.active_name',
            'deposit.active_id_other',
            'deposit.coupon_money',
            'deposit.created',
            'deposit.over_time',
            'deposit.deposit_type',
            'deposit.marks',
            'deposit.memo',
            'deposit.money',
            'deposit.receive_bank_info as channel_name',
            'admin.username as process_uname',
            'deposit.process_time',
            'deposit.recharge_time',
            'deposit.status',
            'deposit.state',
            'deposit.origin',
            \DB::raw("concat(deposit.trade_no,'') as trade_no "),
            'deposit.pay_no',
            'deposit.user_id',
            'deposit.ip',
            'deposit.currency_name',
            'deposit.coin_type',
            'deposit.currency_amount',
            'deposit.rate',
            'deposit.name as user_name',
            'level.name as ranting',
            'user.channel_id as channel_no',
            'deposit.payment_id',
        ])->toArray();


        $payment_info = $pay->getPaymentByPayID(array_unique(array_column($data, 'payment_id')));

        $attributes = [
            'total' => $total,'sum' => 0,
            'failed_count' => 0,'failed_sum' => 0,
            'pending_count' => 0,'pending_sum' => 0,
            'refuse_count' => 0,'refuse_sum' => 0,
            'success_count' => 0,'success_sum' => 0,
        ];
        $attributes['cur_sum'] = 0;
        $origins = [0=>'',1=>'PC',2=>'H5',3=>'APP',4=>'APP'];

        $payGiveOrder = [];
        foreach ($data as &$val){
            if($val->status == 'paid')
                $val->in_money = $val->money + $val->coupon_money;
            else
                $val->in_money = 0;
                $val->channel_name = json_decode($val->channel_name,true);
            $attributes['cur_sum'] += $val->money;
            $val->active_name = '';

            if ($val->coupon_money > 0) {
                array_push($payGiveOrder, $val->trade_no);
            }

            $actives = property_exists($val,'active_apply') ? $val->active_apply : '';
            if($actives){
                foreach (explode(',',$actives) ?? [] as $active_apply_id){
                    $active_apply= DB::connection('slave')->table('active_apply')->find($active_apply_id);
                    if($active_apply)
                        $val->active_name .= " ".$active_apply->active_name .'赠送'.($active_apply->coupon_money/100). ($active_apply->state == 'auto' ? '(自动)' : '(手动)');
                }
            }
            $val->origin_str = $origins[$val->origin] ? : '';
            $val->vender_name = "";
            if(isset($val->channel_name['vender'])){
                $val->vender_name = $val->channel_name['vender'];
            }
            $val->notify_time = $val->process_time;

            // 用户渠道号（推广渠道）
            $val->channel_no = !is_null($val->channel_no) ? $val->channel_no : '';
            if($val->currency_name != 'USDT'){
                $val->coin_type = '';
            }
            // 支付渠道信息
            $val->payment_belong = isset($payment_info[$val->payment_id]) ? $payment_info[$val->payment_id]->name : '';
        }

        // 获取支付渠道优惠
//        if (!empty($payGiveOrder)) {
//            $payGiveLog = \Model\FundsDealLog::select('order_number', 'memo')
//                ->whereIn('order_number', $payGiveOrder)->where('deal_type', FundsDealLog::TYPE_ACTIVITY)->get();
//            foreach ($payGiveLog as $log) {
//                if (strpos($log->memo, 'payment_give') === 0) {
//                    collect($data)->where('trade_no', $log->order_number)->first()->active_name .= $log->memo;
//                }
//            }
//        }

        $attributes['count']                    = $total;
        $attributes['size']                     = $size;
        $attributes['number']                   = $page;
        $attributes['current_paid_count']       = $current_paid_count;
        $attributes['current_money_count']      = $current_money_count;
        $attributes['current_paid_fail_count']  = $current_paid_fail_count;
        $attributes['current_money_fail_count'] = $current_money_fail_count;
        $attributes['current_coupon_money_count'] = $current_coupon_money_count;
        $attributes['current_account_count']      = $current_account_count;
        // 成功率
        $rate = $current_paid_count ? (bcdiv($current_paid_count, ($current_paid_count + $current_paid_fail_count) / 100, 2) ?? '0') : '0';
        $attributes['current_success_rate'] = $rate . '%';
        return $this->lang->set(0,[],$data,$attributes);
    }
};
