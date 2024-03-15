<?php

use Logic\Admin\BaseController;

return new class() extends BaseController {
    const STATE = '';
    const TITLE = '新会员出款列表导出（提现审核，提现付款合并）';
    const DESCRIPTION = '获取会员出款列表';
    const TRANSFER_ID = '0'; # 人工处理

    const QUERY = [
        'page'          => 'int()   #页码',
        'page_size'     => 'int()    #每页大小',
        'member_name'   => 'string()    #用户名',
        'trade_no'      => 'string()   #订单号',
        'register_from' => 'date()  #申请时间',
        'register_to'   => 'date()    #申请时间',
        'status'        => 'enum[rejected,pending]  #支付状态，rejected:已拒绝, pending:待处理，paid:已支付',
    ];

    const PARAMS = [];
    const SCHEMAS = [
        [
            "id"                => 76,
            "user_name"         => "用户名",
            "trade_no"          => "转账订单号",
            "money"             => '申请金额',
            "receive_bank_info" => [
                "bank"    => "中国农业银行",
                "name"    => "的撒大和",
                "card"    => "123123132131231",
                "address" => "撒打算",
                'bank_code' => '银行简称KBAK'
            ],
            "created"           => "申请时间",
            "is_new"            => "no:否，yes:是",
            "status"            => "rejected:已拒绝, pending:待处理，paid:已支付",
            "admin_user"        => '操作者',
        ],
    ];

    protected $title=[
        'user_name'=>'用户名','trade_no'=>'订单号',
        'bank'=>'提现银行','name'=>'开户姓名','card'=>'银行账号','money'=>'提现金额',
        'created'=>'申请时间','ip'=>'IP稽核','fee'=>'手续费','reality_money'=>'实际金额','is_new'=>'首次出款','type'=>'提现类型','status'=>'状态','third_no'=>'提现订单号',
        'third_type'=>'提现三方','admin_user'=>'操作者','updated'=>'操作时间','memo'=>'备注',
        /*'today_tz' => '今日流水', 'today_cj' => '今日赠送彩金',
        'total_cz' => '总充值', 'total_dh' => '总兑换', 'total_tz' => '总流水', 'total_cj' => '累计赠送彩金', */'confirm_time' => '出款成功时间'
    ];
    protected $en_title=[
        'user_name'=>'Username','trade_no'=>'ref. no.',
        'bank'=>'withdrawal bank','name'=>'account name','card'=>'bank account number','money'=>'withdraw amount',
        'created'=>'Application time','ip'=>'IP checking','fee'=>'Withdraw fee','reality_money'=>'Reality money','is_new'=>'1st withdrawal','type'=>'withdrawal type','status'=>'STATUS','third_no'=>'withdrawal ref. no.',
        'third_type'=>'withdrawal 3rd party','admin_user'=>'operator','updated'=>'Operation time','memo'=>'remark',
        /*'today_tz' => 'today turnover', 'today_cj' => 'today bonus',
        'total_cz' => 'total deposit', 'total_dh' => 'total withdrawal', 'total_tz' => 'total turnover', 'total_cj' => 'accumulated bonus', */'confirm_time' => 'withdraw success time'
    ];
    protected $status=[
        'paid'      => '提现成功',
        'pending'   => '待处理',
        'rejected'  => '提现失败',
        'canceled'  => '已取消',
        'confiscate'=> '没收',
        'lock'      => '锁定',
        'obligation' => '待付款'
    ];

    protected  $type=[
        '1'=>'主钱包',
        '2'=>'股东分红',
    ];
    protected $isNew=[
        'yes'=>'是',
        'no'=>'否',
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run() {
        $trade_no = $this->request->getParam('trade_no');
        $username = $this->request->getParam('member_name');
        $status = $this->request->getParam('status');
        $state = $this->request->getParam('is_new');
        $stime = $this->request->getParam('register_from', date('Y-m-d'));
        $etime = $this->request->getParam('register_to', date('Y-m-d 23:59:59'));
        $transfer_id=$this->request->getParam('transfer_id');
        $type = $this->request->getParam('type');
        $bank_id = (int)$this->request->getParam('bank_id',0);
        $fee        = $this->request->getParam('fee');

        if ($status) {
            switch ($status) {
                case 'rejected':
                    $status = 'rejected,refused,failed';
                    break;
                case 'pending':
                    $status = 'prepare,pending';
                    break;
                case 'paid':
                    $status = 'paid';
                    break;
                case 'confiscate':
                    $status = 'confiscate';
                    break;
                case 'canceled':
                    $status = 'canceled';
                    break;
            }
        }

        $query = \DB::connection('slave')->table('funds_withdraw as withdraw')
                    ->leftJoin('user', 'withdraw.user_id', '=', 'user.id')
                    ->leftJoin('admin_user as admin', 'withdraw.process_uid', '=', 'admin.id')
            ->leftJoin(\DB::raw("(select t1.third_id,t1.withdraw_order,t1.trade_no from transfer_order as t1,(select max(id) id from transfer_order where created >= '{$stime}' and created <= '{$etime}' group by withdraw_order) as t2 where t1.id = t2.id) as tr"), 'withdraw.trade_no', '=', 'tr.withdraw_order')
                    ->leftJoin('transfer_config as tc', 'tr.third_id', '=', 'tc.id');;

        $trade_no && $query->where('withdraw.trade_no', '=', $trade_no);
        $transfer_id && $query->where('tc.id', '=', $transfer_id);
        $username && $query->where('user.name', '=', $username);
        $type && $query->where('withdraw.type', '=', $type);
        $status && $query->whereRaw('FIND_IN_SET(withdraw.status,"' . $status . '")');
        $stime && $query->where('withdraw.created', '>=', $stime);
        $etime && $query->where('withdraw.created', '<', $etime);
        $fee && $query->where('withdraw.fee', '=', $fee);

        if($bank_id > 0){
            $query = $query->where('withdraw.bank_id', $bank_id);
        }
        if ($transfer_id === self::TRANSFER_ID){
            $query->whereRaw('withdraw.trade_no not In'.\DB::raw("(select withdraw_order from transfer_order where withdraw_order > '1' and created >= '$stime' and created <  '$etime' )"));
        }
        switch ($state) {
            case 'yes' :
                $query = $query->whereRaw('find_in_set("new",withdraw.state)');
                break;

            case 'no' :
                $query = $query->whereRaw('!find_in_set("new",withdraw.state)');
                break;
            case '' :

                break;
        }
//        $query->groupBy('withdraw.trade_no');
        $data = $query->orderBy('withdraw.id','DESC')->get([
            'withdraw.id',
            'user.name as user_name',
            'withdraw.trade_no',
            'withdraw.money',
            'withdraw.type',
            'withdraw.receive_bank_info',
            'withdraw.created',
            'withdraw.ip',
            'withdraw.process_time as updated',
            'withdraw.memo',
            'withdraw.fee',
            'withdraw.confirm_time as pay_time',
            \DB::raw('IF(FIND_IN_SET("new",withdraw.state),"yes","no") AS is_new'),
            'withdraw.status',
            'withdraw.origin',
            'admin.username as admin_user',
            'user.id as user_id',
            'tr.trade_no as third_trade_no',
//            \DB::raw('IF(FIND_IN_SET("new",withdraw.state),"yes","no") AS is_new'),
            'tc.name as third_name'
        ])->toArray();

        foreach ($data as &$val) {
            $val = (array)$val;
            $val = \Utils\Utils::DepositPatch($val);
            $val['reality_money']  = $val['money'];
            $val['fee_money']      = 0;
            if ($val['fee']){
                $val['reality_money']  = bcsub($val['money'],bcmul($val['money'], $val['fee'] / 100,2),2);
                $val['fee_money']      = $val['money'] - $val['reality_money'];
            }
            $receive_bank_info = json_decode($val['receive_bank_info'],true);
            if(is_array($receive_bank_info)){
                foreach($receive_bank_info as $k=>$value){
                    $val[$k]=$value;
                }
            }
            $val['confirm_time'] =  $val['status'] == 'paid' ? $val['pay_time'] : '';
            $val['third_type'] = '';
            $val['third_no'] = '';
            if ($val['status'] == 'pending') {
                $daifu = \DB::connection('slave')->table('transfer_order')
                    ->where('status', '=', 'pending')
                    ->where('withdraw_order', '=', $val['trade_no'])
                    ->orderBy('id', 'DESC')
                    ->value('status');
                if ($daifu) {
                    $val['status'] = 'transfer';
                } else {
                    $ids = \DB::connection('slave')->table('transfer_order')
                        ->where('withdraw_order', '=', $val['trade_no'])
                        ->pluck('third_id')
                        ->toArray();
                    if ($ids) {
                        $dai = \DB::connection('slave')->table('transfer_config')
                            ->whereIn('id', $ids)
                            ->pluck('name')
                            ->toArray();
                        $val['memo'] = implode(',', $dai) . '代付失败';
                    }
                }
            }elseif($val['status'] == 'paid'){
                $val['third_type'] = '人工处理';
                $val['third_no']   = '人工处理';
                $daifu = \DB::connection('slave')->table('transfer_order')
                    ->where('status', '=', 'paid')
                    ->where('withdraw_order', '=', $val['trade_no'])
                    ->orderBy('id', 'DESC')
                    ->first();
                if(!empty($daifu)){
                    $dai_config = \DB::connection('slave')->table('transfer_config')
                        ->where('id', $daifu->third_id)
                        ->first();
                    if(!empty($dai_config)){
                        $val['third_type'] = $dai_config->name;
                        $val['third_no']   = $daifu->transfer_no;
                    }
                }
            }elseif($val['status'] == 'rejected'){
                $val['third_type'] = '拒绝打款';
                $val['third_no']   = '拒绝打款';
            }elseif($val['status'] == 'confiscate'){
                $val['third_type'] = '没收';
                $val['third_no']   = '没收';
            }

            if(!empty($val['third_name'])){
                $val['third_type']=$val['third_name'];
                $val['third_no']="=\"{$val['third_trade_no']}\"";
            }
//
//            //历史总充值,历史总兑换(取款)，历史总流水(投注)，历史累计赠送彩金，
//            $ls_rpt_user = DB::connection('slave')->table('rpt_user')->selectRaw('sum(deposit_user_amount) as ls_zcz,sum(withdrawal_user_amount) as ls_zqk,sum(bet_user_amount) as ls_ztz,'.
//                'sum(coupon_user_amount)+sum(promotion_user_winnings)+sum(turn_card_user_winnings) as ls_zcj')->where('user_id', $val['user_id'])->get()->toArray();
//            $val['total_cz'] = $ls_rpt_user[0]->ls_zcz ?? 0;
//            $val['total_dh'] = $ls_rpt_user[0]->ls_zqk ?? 0;
//            $val['total_tz'] = $ls_rpt_user[0]->ls_ztz ?? 0;
//            $val['total_cj'] = $ls_rpt_user[0]->ls_zcj ?? 0;
//
//
//            //今日流水(投注)，今日赠送彩金
//            $today = date("Y-m-d");
//            $today_rpt_user = DB::connection('slave')->table('rpt_user')->selectRaw('bet_user_amount as today_tz,'.
//                'coupon_user_amount+promotion_user_winnings+turn_card_user_winnings as today_cj')->whereRaw('count_date =? and user_id = ? ',[$today, $val['user_id']])->get()->toArray();
//            $val['today_tz'] = $today_rpt_user[0]->today_tz ?? 0;
//            $val['today_cj'] = $today_rpt_user[0]->today_cj ?? 0;

            switch ($val['status']) {
                case 'rejected':
                case 'refused':
                case 'failed':
                    $val['status'] = 'rejected';
                    break;
                case 'pending':
                case 'prepare':
                    $val['status'] = 'pending';
                    break;
                case 'paid':
                    $val['status'] = 'paid';
                    break;
            }
            if ($val['status'] == 'pending') {
                $daifu = \DB::connection('slave')->table('transfer_order')
                            ->where('status', '=', 'pending')
                            ->where('withdraw_order', '=', $val['trade_no'])
                            ->orderBy('id', 'DESC')
                            ->value('status');
                if ($daifu) {
                    $val['status'] = 'transfer';
                } else {
                    $ids = \DB::connection('slave')->table('transfer_order')
                              ->where('withdraw_order', '=', $val['trade_no'])
                              ->pluck('third_id')
                              ->toArray();
                    if ($ids) {
                        $dai = \DB::connection('slave')->table('transfer_config')
                                  ->whereIn('id', $ids)
                                  ->pluck('name')
                                  ->toArray();
                        $val['memo'] = implode(',', $dai) . '代付失败';
                    }
                }
            }
            if($val['status'] == 'canceled'){   //用户主动撤销   操作人为用户本人
                $val['admin_user'] = $val['user_name'];
            }
            $val['status'] = $this->status[$val['status']] ??'';
            $val['is_new'] = $this->isNew[$val['is_new']] ??'';
            $val['money'] = bcdiv($val['money'],100,2);
            $val['reality_money'] = bcdiv($val['reality_money'],100,2);
            $val['trade_no'] = "=\"{$val['trade_no']}\"";
            $val['type'] = $this->type[$val['type']];
        }
        foreach ($this->en_title as &$value){
            $value = $this->lang->text($value);
        }
        array_unshift($data,$this->en_title);
        //判断条数 超过5000条提示
//        if(count($data) > 5000){
//            return $this->lang->text('The current exported data volume exceeds 5,000 bytes, Please contact the DBA for processing or export in batches.');
//        }else{
            Utils\Utils::exportExcel('withdrawalReview',$this->title,$data);
//        }
    }

};
