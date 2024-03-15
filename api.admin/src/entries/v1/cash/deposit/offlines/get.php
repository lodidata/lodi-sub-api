<?php

use Logic\Admin\BaseController;

return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '线下入款列表';
    const DESCRIPTION = '获取线下入款列表';
    
    const QUERY       = [
        'page'         => 'int()   #页码',
        'page_size'    => 'int()    #每页大小',
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
        $page       = $this->request->getParam('page') ?? 1;
        $size       = $this->request->getParam('page_size') ?? 20;
        $sort_field = $this->request->getParam('sort_field', '');    // 要排序的字段
        $sort_type  = $this->request->getParam('sort_type', 'asc');  // 排序的方式: asc-升序，desc-降序
        $channel_no = $this->request->getParam('channel_no');        // 用户渠道   

        if ($page == 1) {
            $this->redis->set('admin:UnreadNum2', date('Y-m-d H:i:s'));
        }

        $query = \DB::table('funds_deposit as deposit')
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
        $channel_no && $query->where('user.channel_id', '=', $channel_no);

        $sum   = clone $query;
        $total = $sum->count();
        //通过的订单数
        $success_count_query = clone $query;
        $success_count = $success_count_query->where('deposit.status', '=', 'paid')->count();
        //通过的总金额
        $success_sum_query = clone $query;
        $success_sum = $success_sum_query->where('deposit.status', '=', 'paid')->sum('money');
        //拒绝的订单数
        $refuse_count_query = clone $query;
        $refuse_count = $refuse_count_query->where('deposit.status', '=', 'rejected')->count();
        //拒绝的总金额
        $refuse_sum_query = clone $query;
        $refuse_sum = $refuse_sum_query->where('deposit.status', '=', 'rejected')->sum('money');
        //赠送彩金
        $coupon_query = clone $query;
        $coupon_sum = $coupon_query->where('deposit.status', '=', 'paid')->sum('coupon_money');

        //根据页面指定字段排序
        if (!empty($sort_field)) {
            $sort_str = 'deposit.'.$sort_field;
            $sort_t = $sort_type;
        } else {
            $sort_str = 'deposit.created';    //默认的排序方式
            $sort_t = 'DESC';
        }
//        $data  = $query->orderBy('deposit.id','DESC')->forPage($page,$size)->get([
        $data  = $query->orderBy($sort_str,$sort_t)->forPage($page,$size)->get([
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
            'user.channel_id as channel_no',
//            'agent.uid_agent_name as agent_name'
        ])->toArray();
        //获取所有的活动
        $activeData = \DB::table('active')->leftJoin('active_rule AS rule','active.id','=','rule.active_id')
            ->select(['active.id','active.name','rule.issue_mode'])->get()->toArray();
        $activeArr=[];

        foreach ($activeData ?? [] as $k=>$v){
            $v = (array)$v;
            $id = $v['id'];
            $activeArr[$id]['name'] = $v['name'];
            $activeArr[$id]['issue_mode'] = $v['issue_mode'] == 'auto' ? '（自动）' : '（手动）';
        }

        $attributes['cur_sum'] = 0;
        $origins = [0=>'',1=>'PC',2=>'H5',3=>'APP',4=>'APP'];

        foreach ($data as &$val){
            if($val->status == 'paid') {
                $val->in_money = $val->money + $val->coupon_money;
            }else {
                $val->in_money = 0;
            }

            !$val->receive_bank_info && ($val->receive_bank_info = '{"bank":"","accountname":"","card":""}');
            $s = json_decode($val->receive_bank_info,true);
            $s['card'] = \Utils\Utils::RSADecrypt($s['card'] ?? '');
            $s['bank'] = isset($s['bank']) ? $s['bank'] : '';
            $val->origin_str = $origins[$val->origin] ? : '';
            $val->receive_bank_info = $s;
            !$val->pay_bank_info && ($val->pay_bank_info = '{"bank":"","accountname":"","card":""}');
            $s = json_decode($val->pay_bank_info,true);
            $s['card'] = \Utils\Utils::RSADecrypt($s['card'] ?? '');
            $val->pay_bank_info = $s;
            $val->active_name = '';
            $actives = property_exists($val,'active_apply') ? $val->active_apply : '';
            if($actives){
                foreach (explode(',',$actives) ?? [] as $active_apply_id){
                    $active_apply= DB::table('active_apply')->find($active_apply_id);
                    if($active_apply)
                        $val->active_name .= " ".$active_apply->active_name .'赠送'.($active_apply->coupon_money/100). ($active_apply->state == 'auto' ? '(自动)' : '(手动)');
                }
            }
            $val->pay_type = isset($this->pay_types[$val->pay_type]) ? $this->pay_types[$val->pay_type] : '';
            $attributes['cur_sum'] += $val->money;
            if($val->status == 'canceled'){   //用户主动撤销   操作人为用户本人
                $val->process_uname = $val->user_name;
            }

            // 用户渠道号（推广渠道）
            $val->channel_no = !is_null($val->channel_no) ? $val->channel_no : '';
        }

        $attributes = [
            'total' => $total,'sum' => 0,
            'failed_count' => 0,'failed_sum' => 0,
            'pending_count' => 0,'pending_sum' => 0,
            'refuse_count' => $refuse_count,'refuse_sum' => $refuse_sum,
            'success_count' => $success_count,'success_sum' => $success_sum,
            'coupon_sum' => $coupon_sum,
        ];
        $attributes['count'] = $total;
        $attributes['size'] = $size;
        $attributes['number'] = $page;

        return $this->lang->set(0,[],$data,$attributes);
    }
};
