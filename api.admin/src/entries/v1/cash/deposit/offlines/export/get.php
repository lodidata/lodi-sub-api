<?php

use Logic\Admin\BaseController;

return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '线下入款列表导出';
    const DESCRIPTION = '获取线下入款列表';
    
    const QUERY       = [
        'user_name'    => 'string()   #用户名称',
        'pay_no'       => 'string()   #交易号',
        'ranting'      => 'string()   #用户等级，查询多个层级，逗号(,)分隔',
        'status'       => 'enum[all,paid,pending,canceled]()   #交易状态， all 全部，paid(已存款), pending(未处理), canceled(已取消)',
        'bank_id'      => 'int()   #银行ID',
        'date_from'    => 'date()   #开始时间',
        'date_to'      => 'date() #结束时间',
        'money_from'   => 'int()  #存款金额',
        'money_to'     => 'int()    #存款金额',
        'admin_user'   => 'string #操作者',
        'deposit_type' => 'set[1,2,3,4,5,6,7,9]()    #存款方式，1,银行柜台  2,ATM现金入款  3,ATM自动柜员机 4,手机转账  5,支付宝转账 6,财付通 7,微信支付 9:网银转账',
        'pay_type' => 'set[1,2,3,4,5]()    #支付类型，1,银行转账  2,支付宝  3,微信 4,QQ  5,京东',
    ];

    protected $title=[
        'trade_no'=>'订单号','user_name'=>'用户名','origin_str'=>'来源','ranting_name'=>'会员等级',
        'pay_type'=>'存入类型','receive_bank'=>'存入银行','receive_name'=>'存入用户','receive_card'=>'存入卡号',
        'pay_bank'=>'会员银行','pay_name'=>'会员用户','pay_card'=>'会员卡号','money'=>'存入金额',
        'active_name'=>'参与活动','coupon_money'=>'自动赠送优惠金额','created'=>'申请时间','status'=>'状态',
        'in_money'=>'到账金额','process_uname'=>'操作者','process_time'=>'操作时间','memo'=>'备注',
    ];
    protected $en_title=[
        'trade_no'=>'Referrence number','user_name'=>'Username','origin_str'=>'sort','ranting_name'=>'member Level',
        'pay_type'=>'type of deposit','receive_bank'=>'destination bank account','receive_name'=>'Deposit to user','receive_card'=>'member bank account',
        'pay_bank'=>'Member Banks','pay_name'=>'Deposit to user','pay_card'=>'Membership Card No','money'=>'deposit amount',
        'active_name'=>'participate promotion','coupon_money'=>'auto send bonus','created'=>'Application time','status'=>'status',
        'in_money'=>'Received amount','process_uname'=>'operator','process_time'=>'Operation time','memo'=>'remark',
    ];
    protected $status=[
        'paid'=>'已存入','pending'=>'未处理','rejected'=>'已拒绝','canceled'=>'已取消'
    ];
    
    const PARAMS      = [];
    const SCHEMAS     = [
        [
            'type'  => 'enum[rowset, row, dataset]',
            'size'  => 'unsigned',
            'total' => 'unsigned',
            'data'  => 'rows[id:int,trade_no:string,ranting:int,agent_name:string,user_name:string,deposit_type:int,pay_type:string
                name:string,money:int,pay_bank_info:string,receive_bank_info:string, coupon_money:int,recharge_time:string,
                status:set[paid,pending,cancel,deposit], ip:string,memo:string,state:set[show,new,auto,online],update_uname:string,update:string]',
        ],
    ];
    public $pay_types=[1=>'银行转账',2=>'支付宝',3=>'微信',4=>'QQ',5=>'JD'];
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];
    public function run()
    {
        $username   = $this->request->getParam('user_name');
        $status     = $this->request->getParam('status');
        $ranting    = $this->request->getParam('ranting');
        $paytype    = $this->request->getParam('pay_type');
        $smoney     = $this->request->getParam('money_from');
        $emoney     = $this->request->getParam('money_to');
        $stime      = $this->request->getParam('date_from', date('Y-m-d'));
        $etime      = $this->request->getParam('date_to', date('Y-m-d 23:59:59'));
        $cstime     = $this->request->getParam('create_from');
        $cetime     = $this->request->getParam('create_to');


        $query = \DB::connection('slave')->table('funds_deposit as deposit')
            ->leftJoin('user','deposit.user_id','=','user.id')
            ->leftJoin('user_level as level','user.ranting','=','level.level')
//            ->leftJoin('user_agent as agent','user.id','=','agent.user_id')
            ->leftJoin('admin_user as admin','deposit.process_uid','=','admin.id')
            ->whereRaw('!FIND_IN_SET("online",deposit.state)');
        $username && $query->where('user.name','=',$username);
        $status && ($status != 'all') && $query->whereRaw('FIND_IN_SET("'.$status.'",deposit.status)');
        $smoney && $query->where('deposit.money','>=',$smoney);
        $emoney && $query->where('deposit.money','<=',$emoney);
        $ranting && $query->where('user.ranting','=',$ranting);
        $paytype && $query->where('deposit.pay_type','=',$paytype);
//        $stime && $query->where('deposit.recharge_time','>=',$stime." 00:00:00");
//        $etime && $query->where('deposit.recharge_time','<=',$etime. " 23:59:59");
        $stime && $query->where('deposit.created','>=',$stime);
        $etime && $query->where('deposit.created','<',$etime);
        $cstime && $query->where('deposit.created','>=',$cstime);
        $cetime && $query->where('deposit.created','<',$cetime);
        $data  = $query->orderBy('deposit.created','DESC')->get([
            'deposit.id',
            'deposit.active_id',
            'deposit.active_apply',
            'deposit.active_name',
            'deposit.active_id_other',
            'deposit.coupon_money',
            'deposit.created',
            'deposit.deposit_type',
            'deposit.marks',
            'deposit.memo',
            'deposit.money',
            'deposit.pay_bank_info',
            'deposit.pay_type',
            'deposit.receive_bank_info',
            'admin.username as process_uname',
            'deposit.process_time',
            'deposit.recharge_time',
            'deposit.status',
            'deposit.state',
            \DB::raw('CONCAT(deposit.trade_no,\'\') as trade_no') ,
            'deposit.user_id',
            'deposit.origin',
            'user.name as user_name',
            'level.name as ranting_name',
//            'agent.uid_agent_name as agent_name'
        ])->toArray();
        //获取所有的活动
        $activeData = \DB::connection('slave')->table('active')->leftJoin('active_rule AS rule','active.id','=','rule.active_id')
            ->select(['active.id','active.name','rule.issue_mode'])->get()->toArray();
        $activeArr=[];

        foreach ($activeData ?? [] as $k=>$v){
            $v = (array)$v;
            $id = $v['id'];
            $activeArr[$id]['name'] = $v['name'];
            $activeArr[$id]['issue_mode'] = $v['issue_mode'] == 'auto' ? '（自动）' : '（手动）';
        }

        $origins = [0=>'',1=>'PC',2=>'H5',3=>'APP',4=>'APP'];

        foreach ($data as &$val){
            if($val->status == 'paid') {
                $val->in_money = $val->money + $val->coupon_money;
            }else {
                $val->in_money = 0;
            }

            !$val->receive_bank_info && ($val->receive_bank_info = '{"bank":"","accountname":"","card":""}');
            $s = json_decode($val->receive_bank_info,true);
            $val->receive_card = \Utils\Utils::RSADecrypt($s['card'] ?? '');
            $val->receive_bank = isset($s['bank']) ? $s['bank'] : '';
            $val->receive_name = isset($s['accountname']) ? $s['accountname'] : '';
            $val->origin_str = $origins[$val->origin] ? : '';

            !$val->pay_bank_info && ($val->pay_bank_info = '{"bank":"","accountname":"","card":""}');
            $payStr = json_decode($val->pay_bank_info,true);
            $val->pay_card = \Utils\Utils::RSADecrypt($payStr['card'] ?? '');
            $val->pay_bank = isset($payStr['bank']) ? "=\"{$payStr['bank']}\"" : '';
            $val->pay_name = isset($payStr['name']) ? $payStr['name'] : '';

            $val->active_name = '';
            $actives = property_exists($val,'active_apply') ? $val->active_apply : '';
            if($actives){
                foreach (explode(',',$actives) ?? [] as $active_apply_id){
                    $active_apply= DB::connection('slave')->table('active_apply')->find($active_apply_id);
                    if($active_apply)
                        $val->active_name .= " ".trim($active_apply->active_name) .'赠送'.($active_apply->coupon_money/100). ($active_apply->state == 'auto' ? '(自动)' : '(手动)');
                }
            }
            $val->pay_type = isset($this->pay_types[$val->pay_type]) ? $this->pay_types[$val->pay_type] : '';
            if($val->status == 'canceled'){   //用户主动撤销   操作人为用户本人
                $val->process_uname = $val->user_name;
            }
            $val->trade_no = "=\"{$val->trade_no}\"";
            $val->receive_card = "=\"{$val->receive_card}\"";
            $val->pay_card = "=\"{$val->pay_card}\"";
            $val->status   = $this->status[$val->status];
            $val->money    = bcdiv($val->money,100,2);
            $val->coupon_money    = bcdiv($val->coupon_money,100,2);
            $val->in_money    = bcdiv($val->in_money,100,2);
        }
        foreach ($this->en_title as &$value){
            $value = $this->lang->text($value);
        }
        array_unshift($data,$this->en_title);
        \Utils\Utils::exportExcel('offlines',$this->title,$data);
    }
};
