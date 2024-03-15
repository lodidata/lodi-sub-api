<?php

use Logic\Admin\BaseController;

return new class() extends BaseController {
    const STATE = '';
    const TITLE = '新会员出款列表（提现审核，提现付款合并）';
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
    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run() {
        $trade_no   = $this->request->getParam('trade_no');
        $username   = $this->request->getParam('member_name');
        $status     = $this->request->getParam('status');
        $state      = $this->request->getParam('is_new');
        $stime      = $this->request->getParam('register_from', date('Y-m-d'));
        $etime      = $this->request->getParam('register_to', date('Y-m-d 23:59:59'));
        $type       = $this->request->getParam('type');
        $bank_id    = (int)$this->request->getParam('bank_id',0);
        $page       = $this->request->getParam('page') ?? 1;
        $size       = $this->request->getParam('page_size') ?? 1;
        $transfer_id= $this->request->getParam('transfer_id');
        $fee        = $this->request->getParam('fee');
        $transfer_no= $this->request->getParam('transfer_no');      // 提现订单号

        $statusWhere = '';
        if ($status) {
            switch ($status) {
                case 'rejected':
                    $status = 'rejected,refused,failed';
                    break;
                case 'pending':
                    $status = 'pending';
                    break;
                case 'paid':
                    $status = 'paid';
                    break;
                case 'lock':
                    $status = 'lock,oblock';
                    break;
            }
        }
        if ($page == 1) {
            $this->redis->set('admin:UnreadNum3', date('Y-m-d H:i:s'));
        }
        //历史总充值,历史总兑换(取款)，历史总流水(投注)，历史累计赠送彩金，
//        $ls_rpt_user = DB::table('rpt_user')->selectRaw('user_id,sum(deposit_user_amount) as ls_zcz,sum(withdrawal_user_amount) as ls_zqk,sum(bet_user_amount) as ls_ztz,'.
//            'sum(coupon_user_amount)+sum(promotion_user_winnings)+sum(turn_card_user_winnings) as ls_zcj')->groupBy(['user_id']);
        //今日流水(投注)，今日赠送彩金
//        $today = date("Y-m-d");
//        $today_rpt_user = DB::table('rpt_user')->selectRaw('user_id,sum(bet_user_amount) as today_tz,'.
//            'sum(coupon_user_amount)+sum(promotion_user_winnings)+sum(turn_card_user_winnings) as today_cj')->where('count_date',$today)->groupBy(['user_id']);

        // 如果条件中带有代付方式，并且状态为paid
        if ($status == 'paid' && !empty($transfer_id)) {
            $statusWhere = " and status='paid'";
        }

        $query = \DB::table('funds_withdraw as withdraw')
//            ->joinSub($ls_rpt_user, 'ls_rpt_user', 'withdraw.user_id', '=', 'ls_rpt_user.user_id', 'left')
//            ->joinSub($today_rpt_user, 'today_rpt_user', 'withdraw.user_id', '=', 'today_rpt_user.user_id', 'left')
                    ->leftJoin('user', 'withdraw.user_id', '=', 'user.id')
                    ->leftJoin('admin_user as admin', 'withdraw.process_uid', '=', 'admin.id')
                    ->leftJoin('label', 'user.tags', '=', 'label.id')
                    ->leftJoin(\DB::raw("(select t1.third_id,t1.withdraw_order,t1.trade_no from transfer_order as t1,(select max(id) id from transfer_order where created >= '{$stime}' and created <= '{$etime}' {$statusWhere} group by withdraw_order) as t2 where t1.id = t2.id) as tr"), 'withdraw.trade_no', '=', 'tr.withdraw_order')
                    ->leftJoin('transfer_config as tc', 'tr.third_id', '=', 'tc.id');


        $trade_no && $query->where('withdraw.trade_no', '=', $trade_no);
        $transfer_id && $query->where('tc.id', '=', $transfer_id);
        if ($transfer_id === self::TRANSFER_ID){
            $query->whereRaw('withdraw.trade_no not In'.\DB::raw("(select withdraw_order from transfer_order where withdraw_order > '1' and created >= ? and created <=  ? )"),[$stime, $etime]);
        }
        $username && $query->where('user.name', '=', $username);
        $type && $query->where('withdraw.type', '=', $type);
        $status && $query->whereRaw('FIND_IN_SET(withdraw.status,?)',[$status]);
        $stime && $query->where('withdraw.created', '>=', $stime);
        $etime && $query->where('withdraw.created', '<=', $etime);
        $fee && $query->where('withdraw.fee', '=', $fee);
        $transfer_no && $query->where('tr.trade_no', '=', $transfer_no);

        if($bank_id > 0){
            $query = $query->where('withdraw.bank_id', $bank_id);
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

        $data = $query->orderBy('withdraw.id','DESC')->forPage($page,$size)->get([
            'withdraw.id',
            'withdraw.type',
            'user.name as user_name',
            'withdraw.trade_no',
            'label.title as tags',
            'withdraw.money',
            'withdraw.receive_bank_info',
            'withdraw.created',
            'withdraw.ip',
            'withdraw.fee',
            'withdraw.process_time as updated',
            'withdraw.memo',
            'withdraw.confirm_time',
            \DB::raw('IF(FIND_IN_SET("new",withdraw.state),"yes","no") AS is_new'),
            'withdraw.status',
            'withdraw.origin',
            'admin.username as admin_user',
            'user.id as user_id',
            'tr.trade_no as third_trade_no',
//            \DB::raw('MAX(tr.trade_no) as third_trade_no'),
            'tc.name as third_name'
//            'ls_rpt_user.ls_zcz as total_cz',    //总充值
//            'ls_rpt_user.ls_zqk as total_dh',    //总兑换(总取款)
//            'ls_rpt_user.ls_ztz as total_tz',    //总流水(总投注)
//            'ls_rpt_user.ls_zcj as total_cj',    //总彩金
//            'today_rpt_user.today_tz as today_tz',    //今日流水
//            'today_rpt_user.today_cj as today_cj',    //今日彩金
        ])->toArray();

        $sum = \DB::table('funds_withdraw as withdraw');
        $username && $sum->leftJoin('user', 'withdraw.user_id', '=', 'user.id')->where('user.name', '=', $username);
        $trade_no && $sum->where('withdraw.trade_no', '=', $trade_no);
        $status   && $sum->whereRaw('FIND_IN_SET(withdraw.status,?)',[$status]);
        $type     && $sum->where('withdraw.type', '=', $type);
        $stime    && $sum->where('withdraw.created', '>=', $stime);
        $etime    && $sum->where('withdraw.created', '<=', $etime);
        $bank_id  && $sum->where('withdraw.bank_id', $bank_id);
        $fee      && $sum->where('withdraw.fee', $fee);

        switch ($state) {
            case 'yes' :
                $sum = $sum->whereRaw('find_in_set("new",withdraw.state)');
                break;

            case 'no' :
                $sum = $sum->whereRaw('!find_in_set("new",withdraw.state)');
                break;
            case '' :

                break;
        }

        // 代付方式
        if ($transfer_id === self::TRANSFER_ID){
            $sum->whereRaw('withdraw.trade_no not In'.\DB::raw("(select withdraw_order from transfer_order where withdraw_order > '1' and created >= ? and created <=  ? )"),[$stime, $etime]);
        }

        // 代付方式  如果条件中带有代付方式，统计提现成功金额的时候需要代付表中的状态为paid
        if (!empty($transfer_id)) {
            $transferSql = "(select t1.third_id,t1.withdraw_order,t1.trade_no from transfer_order as t1,(select max(id) id from transfer_order  where created >= '{$stime}' and created <= '{$etime}' {$statusWhere} group by withdraw_order) as t2 where t1.id = t2.id) as tr";
            $paidTransferSql = "(select t1.third_id,t1.withdraw_order,t1.trade_no from transfer_order as t1,(select max(id) id from transfer_order  where created >= '{$stime}' and created <= '{$etime}' and status='paid' group by withdraw_order) as t2 where t1.id = t2.id) as tr";

            $paidSum = clone $sum;
            $paidAmount = $paidSum->leftJoin(\DB::raw($paidTransferSql), 'withdraw.trade_no', '=', 'tr.withdraw_order')
                ->leftJoin('transfer_config as tc', 'tr.third_id', '=', 'tc.id')
                ->where('tc.id', '=', $transfer_id)
                ->where('withdraw.status', 'paid')->sum('withdraw.money');

            $sum->leftJoin(\DB::raw($transferSql), 'withdraw.trade_no', '=', 'tr.withdraw_order')
                ->leftJoin('transfer_config as tc', 'tr.third_id', '=', 'tc.id')
                ->where('tc.id', '=', $transfer_id);
        }

        $sum = $sum->groupBy('withdraw.status')
                   ->get([
                       \DB::raw('count(withdraw.id) as count'),
                       \DB::raw('sum(withdraw.money) as money'),
                       'withdraw.status',
                   ])
                   ->toArray();

        $attributes = [
            'total'          => 0, 'sum' => 0,
            'paid_count'     => 0, 'paid_sum' => 0,
            'pending_count'  => 0, 'pending_sum' => 0,
            'prepare_count'  => 0, 'prepare_sum' => 0,
            'refused_count'  => 0, 'refused_sum' => 0,
            'rejected_count' => 0, 'rejected_sum' => 0,
            'failed_count'   => 0, 'failed_sum' => 0,
            'confiscate_sum' => 0,
        ];

        $attributes['page_sum'] = 0;
        $origins = [0=>'',1=>'PC',2=>'H5',3=>'APP',4=>'APP'];
        foreach ($data as &$val) {
            $val = (array)$val;
            $val = \Utils\Utils::DepositPatch($val);
            $val['reality_money']  = $val['money'];
            $val['fee_money']      = 0;
            if ($val['fee']){
                $val['reality_money']  = bcsub($val['money'],bcmul($val['money'], $val['fee'] / 100,2),2);
                $val['fee_money']      = $val['money'] - $val['reality_money'];
            }
            $val['receive_bank_info'] = json_decode($val['receive_bank_info']);
            switch ($val['status']) {
                case 'rejected':
                case 'refused':
                case 'failed':
                    $val['status'] = 'rejected';
                    break;
                case 'confiscate':
                    $val['status'] = 'confiscate';
                break;
                case 'canceled':
                    $val['status'] = 'canceled';
                break;
                case 'pending':
                case 'prepare':
                    $val['status'] = 'pending';
                    break;
                case 'paid':
                    $val['status'] = 'paid';
                    break;
            }
            //历史总充值,历史总兑换(取款)，历史总流水(投注)，历史累计赠送彩金，
            $ls_rpt_user = DB::table('rpt_user')->selectRaw('sum(deposit_user_amount) as ls_zcz,sum(withdrawal_user_amount) as ls_zqk,sum(bet_user_amount) as ls_ztz,'.
                'sum(coupon_user_amount)+sum(promotion_user_winnings)+sum(turn_card_user_winnings) as ls_zcj')->where('user_id', $val['user_id'])->get()->toArray();
            $val['total_cz'] = $ls_rpt_user[0]->ls_zcz ?? 0;
            $val['total_dh'] = $ls_rpt_user[0]->ls_zqk ?? 0;
            $val['total_tz'] = $ls_rpt_user[0]->ls_ztz ?? 0;
            $val['total_cj'] = $ls_rpt_user[0]->ls_zcj ?? 0;
            //今日流水(投注)，今日赠送彩金
            $today = date("Y-m-d");
            $today_rpt_user = DB::table('rpt_user')->selectRaw('bet_user_amount as today_tz,'.
                'coupon_user_amount+promotion_user_winnings+turn_card_user_winnings as today_cj')->whereRaw('count_date =? and user_id = ? ',[$today, $val['user_id']])->get()->toArray();
            $val['today_tz'] = $today_rpt_user[0]->today_tz ?? 0;
            $val['today_cj'] = $today_rpt_user[0]->today_cj ?? 0;
            $val['third_type'] = '';
            $val['third_no'] = '';
            if ($val['status'] == 'obligation') {
                $daifu = \DB::table('transfer_order')
                            ->where('status', '=', 'pending')
                            ->where('withdraw_order', '=', $val['trade_no'])
                            ->orderBy('id', 'DESC')
                            ->value('status');
                if ($daifu) {
                    $val['status'] = 'transfer';
                } else {
                    $ids = \DB::table('transfer_order')
                              ->where('withdraw_order', '=', $val['trade_no'])
                              ->where('status','failed')
                              ->pluck('third_id')
                              ->toArray();
                    if ($ids) {
                        $dai = \DB::table('transfer_config')
                                  ->whereIn('id', $ids)
                                  ->pluck('name')
                                  ->toArray();
                        $val['memo'] = implode(',', $dai) . '代付失败';
                    }
                }
            }elseif($val['status'] == 'paid'){
                $val['third_type'] = '人工处理';
                $val['third_no']   = '人工处理';
                $daifu = \DB::table('transfer_order')
                    ->where('status', '=', 'paid')
                    ->where('withdraw_order', '=', $val['trade_no'])
                    ->orderBy('id', 'DESC')
                    ->first();
                if(!empty($daifu)){
                    $dai_config = \DB::table('transfer_config')
                        ->where('id', $daifu->third_id)
                        ->first();
                    if(!empty($dai_config)){
                        $val['third_type'] = $dai_config->name;
                        $val['third_no']   = $daifu->transfer_no;
                    }
                }else{
                    $val['third_name'] = '';
                    $val['third_trade_no'] = '';
                }
                if(empty($val['confirm_time'])){
                    $val['confirm_time'] = $val['updated'];
                }
            }elseif($val['status'] == 'rejected'){
                $val['third_type'] = '拒绝打款';
                $val['third_no']   = '拒绝打款';
            }elseif($val['status'] == 'confiscate'){
                $val['third_type'] = '没收';
                $val['third_no']   = '没收';
            }
            $val['origin_str'] = $origins[$val['origin']] ? : '';
            if($val['status'] == 'canceled'){   //用户主动撤销   操作人为用户本人
                $val['admin_user'] = $val['user_name'];
            }
            if(!empty($val['third_name'])){
                $val['third_type']=$val['third_name'];
                $val['third_no']=$val['third_trade_no'];
            }
        }

        foreach ($sum as $v) {
            $attributes[$v->status . '_count'] = (int)$v->count;
            $attributes[$v->status . '_sum'] = (int)$v->money;
            $attributes['total'] += $v->count;
            $attributes['sum'] += $v->money;
        }

        $resum = [
            'total'             => $attributes['total'],
            'total_money'       => $attributes['sum'] / 100 ,
            'paid_count'        => $attributes['paid_count'],
            'successful_money'  => isset($paidAmount) ? $paidAmount/100 : $attributes['paid_sum'] / 100,
            'pending_count'     => $attributes['pending_count'] + $attributes['prepare_count'],
            'pending_sum'       => ($attributes['pending_sum'] + $attributes['prepare_sum']) / 100,
            'confiscated_money' => $attributes['confiscate_sum'] / 100,
            'rejected_count'    => $attributes['rejected_count'] + $attributes['refused_count'] + $attributes['failed_count'],
            'failure_money'     => ($attributes['rejected_sum'] + $attributes['refused_sum'] + $attributes['failed_sum']) / 100,
//            'confiscated_money'   => $confiscated_money / 100,//没收金额
//            'failure_money'       => $failure_money / 100,//失败金额
//            'successful_money'    => $successful_money / 100,//成功金额
//            'total_money'         => $attributes['paid_sum'] / 100,
        ];

        $resum['size'] = $size;
        $resum['number'] = $page;

        return $this->lang->set(0, [], $data, $resum);
    }
};
