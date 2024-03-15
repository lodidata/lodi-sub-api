<?php

use Logic\Admin\BaseController;

return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '会员出款列表';
    const DESCRIPTION = '获取会员出款列表';
    
    const QUERY       = [
        'page'        => 'int()   #页码',
        'page_size'   => 'int()    #每页大小',
        'member_name' => 'string()    #用户名',
        'trade_no'    => 'string()   #订单号',
        'level'       => 'int() #用户等级',
        'register_from'   => 'date()  #申请时间',
        'register_to'     => 'date()    #申请时间',
        'money_from'  => 'int()    #申请金额',
        'money_to'    => 'int()  #申请金额',
        'admin_user'  => 'string #操作者',
        // 'status'      => 'set[rejected,pending]()  #支付状态，rejected:已拒绝, pending:待处理',
    ];
    
    const PARAMS      = [];
    const SCHEMAS     = [
        [
            "coupon_money"      => null,
            "withdraw_bet"      => "8000",
            "valid_bet"         => "0",
            "id"                => "50",
            "user_name"         => "string #会员账号，xiaoming",
            "user_tags"         => 'string #标签',
            "agent_id"          => "0",
            "user_id"           => "1",
            "ranting"           => "string #层级，黄金vip3",
            "agent_name"        => "string #上级代理名称",
            "trade_no"          => "201708110633464096",
            "money"             => "uint #申请金额",
            "fee"               => "int #手续费",
            "admin_fee"         => "int #行政费",
            "apply_time"        => "datetime #申请时间，2017-08-11 15:04:08",
            "receive_bank_info" => [
                "bank"    => "中国银行",
                "name"    => "肖然",
                "card"    => "6022****077",
                "address" => "中国建行"
            ],
            "ip"                => "127.0.0.1",
            "state"             => "string #如果含new，则代表为首次出款",
            "status"            => "pending",
            "confirm_time"      => "datetime #确认时间",
            "previous_time"     => "datetime #确认时间，2017-07-20 16:32:18",
            "process_uname"     => " ",
            "today_times"       => "1",
            "memo"              => "描述",
            "process_uid"       => null,
            "admin_user"        => "string #操作者",
            "marks"             => "54071533d1195519fae259d706a06e0b2b7d31b4",
            "valid"             => "0",
        ],
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];
    public function run()
    {
        $ranting = $this->request->getParam('level');
        $trade_no = $this->request->getParam('trade_no');
        $username = $this->request->getParam('member_name');
        $admin_user = $this->request->getParam('admin_user');
        $status = $this->request->getParam('status') ?? 'pending';
        $smoney = $this->request->getParam('money_from');
        $emoney = $this->request->getParam('money_to');
        $stime = $this->request->getParam('register_from');
        $etime = $this->request->getParam('register_to');
        $page = $this->request->getParam('page') ?? 1;
        $size= $this->request->getParam('page_size') ?? 1;

        if ($page == 1) {
            $this->redis->set('admin:UnreadNum3', date('Y-m-d H:i:s'));
        }

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
        $smoney && $query->where('withdraw.money','>=',$smoney);
        $emoney && $query->where('withdraw.money','<=',$emoney);
        $ranting && $query->whereIn('user.ranting',explode(',',$ranting));
        $stime && $query->where('withdraw.created','>=',$stime." 00:00:00");
        $etime && $query->where('withdraw.created','<=',$etime ." 23:59:59");
        $sum = clone $query;
        $data = $query->orderBy('withdraw.id','DESC')->forPage($page,$size)->get([
            'withdraw.id',
            'withdraw.admin_fee',
            'admin.username as admin_user',
            'agent.uid_agent as agent_id',
            'agent.uid_agent_name as agent_name',
            'withdraw.created as apply_time',
            'withdraw.confirm_time',
            'withdraw.coupon_money',
            'withdraw.fee',
            'withdraw.ip',
            'withdraw.marks',
            'withdraw.memo',
            'withdraw.money',
            'withdraw.previous_time',
            'withdraw.process_uid',
            'admin.username as process_uname',
            'level.name as ranting',
            'withdraw.receive_bank_info',
            'withdraw.state',
            'withdraw.status',
            'withdraw.today_times',
            'withdraw.trade_no',
            'withdraw.user_id',
            'user.name as user_name',
            'label.title as user_tags',
            'withdraw.valid_bet',
            'withdraw.withdraw_bet'
        ])->toArray();

        $sum = $sum->groupBy('withdraw.status')->get([
            \DB::raw('count(withdraw.id) as count'),
            \DB::raw('sum(withdraw.money) as money'),
            'withdraw.status',
        ])->toArray();
        $attributes = [
            'total' => 0,'sum' => 0,
            'paid_count' => 0,'paid_sum' => 0,
            'pending_count' => 0,'pending_sum' => 0,
            'refused_count' => 0,'refused_sum' => 0,
            'rejected_count' => 0,'rejected_sum' => 0,
        ];
        $attributes['page_sum'] = 0;
        foreach ($data as &$val){
            $val = (array)$val;
            $attributes['page_sum'] += $val['money'];
            $val = \Utils\Utils::DepositPatch($val);
            $val['receive_bank_info'] = json_decode($val['receive_bank_info']);
        }

        foreach ($sum as $v){
            $attributes[$v->status.'_count'] = (int)$v->count;
            $attributes[$v->status.'_sum'] = (int)$v->money;
            $attributes['total'] += $v->count;
            $attributes['sum'] += $v->money;
        }
        $attributes['size'] = $size;
        $attributes['number'] = $page;

        return $this->lang->set(0,[],$data,$attributes);
    }
};
