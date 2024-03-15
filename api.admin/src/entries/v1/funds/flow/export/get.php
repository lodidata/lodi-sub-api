<?php

use Logic\Admin\BaseController;

return new class() extends BaseController {
    const STATE = '';

    const TITLE = '资金流水记录导出';

    //交易流水标题
    protected $title = [
        'username'=>'用户名','created'=>'交易时间','order_number'=>'订单号','deal_type_name'=>'交易类型',
        'deal_category'=>'交易类别','deal_money'=>'交易金额','withdraw_bet'=>'交易打码量','balance'=>'主钱包余额',
        'total_bet'=>'实际打码量','total_require_bet'=>'应有打码量','free_money'=>'可提余额','admin_user'=>'操作者',
        'memo'=>'备注'
    ];
    protected $enTitle = [
        'username'=>'Username','created'=>'TransactionTime','order_number'=>'OrderNo','deal_type_name'=>'TransactionType',
        'deal_category'=>'TransactionCategory','deal_money'=>'TransactionAmount','withdraw_bet'=>'BetTurnover','balance'=>'MainWalletBal',
        'total_bet'=>'ActualBet','total_require_bet'=>'RequiredBet','free_money'=>'WithdrawalBal.','admin_user'=>'Operator',
        'memo'=>'Remarks'
    ];

    //优惠彩金标题
    protected $bonusTitle = [
        'username'=>'用户名','created'=>'交易时间','order_number'=>'订单号','deal_type_name'=>'交易类型',
        'deal_category'=>'交易类别','deal_money'=>'交易金额','balance'=>'余额','memo'=>'备注'
    ];
    protected $bonusEnTitle = [
        'username'=>'Username','created'=>'TransactionTime','order_number'=>'OrderNo','deal_type_name'=>'TransactionType',
        'deal_category'=>'TransactionCategory','deal_money'=>'TransactionAmount','balance'=>'Balance','memo'=>'Remarks'
    ];

    protected $deal_category = [
        1   => '收入',
        2   => '支出',
        3   => '额度转换',
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run() {
        $username = $this->request->getParam('username');
        $user_id = (int)$this->request->getParam('user_id', 0);
        $deal_type = $this->request->getParam('deal_type');
        $deal_category = $this->request->getParam('deal_category');
        $order_number = $this->request->getParam('order_number');
        $stime = $this->request->getParam('start_time');
        $etime = $this->request->getParam('end_time');
        $third_id = $this->request->getParam('third_id');

        $query = DB::connection('slave')->table('funds_deal_log')
                   ->select([
                       'funds_deal_log.balance',
                       'funds_deal_log.created',
                       'funds_deal_log.admin_id',
                       'funds_deal_log.admin_user',
                       'user.tags',
                       /*'funds_deal_log.admin_id', 'funds_deal_log.admin_user',*/
                       'funds_deal_log.deal_category',
                       'funds_deal_log.deal_money',
                       \DB::raw('CONCAT(funds_deal_log.order_number,"") AS order_number'),
                       'funds_deal_log.deal_number',
                       'funds_deal_log.deal_type',
                       'funds_deal_log.id',
                       'funds_deal_log.memo',
                       'funds_deal_log.username',
                       'withdraw_bet',
                       'total_bet',
                       'total_require_bet',
                       'free_money',
                       'user.id as user_id'
                   ])
                   ->leftJoin('user', 'funds_deal_log.user_id', '=', 'user.id')
                   ->where('user.tags', '!=', 7);

        $str_stime = strtotime($stime);
        $str_etime = strtotime($etime);
        if(date('H', $str_stime) == '00'){
            $stime =  date('Y-m-d', $str_stime) . ' 00:00:00';
        }
        if(date('H',$str_etime ) == '00'){
            $etime =  date('Y-m-d', $str_etime) . ' 23:59:59';
        }
        if(isset($third_id)){
            $third_query=DB::connection('slave')
                           ->table('funds_withdraw as t1')
                           ->leftJoin('transfer_order as t2','t1.trade_no','=','t2.withdraw_order')
                           ->where('t1.created','>=',$stime)
                           ->where('t1.created','<=',$etime);
            if($third_id !=0){
                $third_query->where('t2.third_id',$third_id);
            }else{
                $third_query->whereRaw('t2.third_id is null');
            }
            $query->whereIn("funds_deal_log.order_number",$third_query->pluck('t1.trade_no')->toArray());
        }

        $user_id && $query->where('funds_deal_log.user_id', '=', $user_id);
        $stime && $query->where('funds_deal_log.created', '>=', $stime);
        $etime && $query->where('funds_deal_log.created', '<=', $etime );
        $username && $query->where('funds_deal_log.username', '=', $username);
        $order_number && $query->where('funds_deal_log.order_number', '=', $order_number);
        $deal_type && $query->whereRaw("funds_deal_log.deal_type in  ({$deal_type})");
        $deal_category && $query->where('funds_deal_log.deal_category', '=', $deal_category);


        $data = $query->orderby('funds_deal_log.created', 'desc')
                      ->orderby('funds_deal_log.id', 'desc')
                      ->get()
                      ->toArray();

        // dd(DB::getQueryLog());exit;
        foreach ($data as &$datum) {
            $types = \Logic\Funds\DealLog::getDealLogTypeFlat();
            $datum->deal_type_name = $types[$datum->deal_type] ?? '';
            if ($datum->free_money > $datum->balance) {
                $datum->free_money = $datum->balance;
            }
            $datum->deal_category   = $this->deal_category[$datum->deal_category];
            $datum->deal_money      = bcdiv($datum->deal_money,100,2);
            $datum->withdraw_bet    = bcdiv($datum->withdraw_bet,100,2);
            $datum->balance         = bcdiv($datum->balance,100,2);
            $datum->total_bet       = bcdiv($datum->total_bet,100,2);
            $datum->total_require_bet = bcdiv($datum->total_require_bet,100,2);
            $datum->free_money = bcdiv($datum->free_money,100,2);
//            $datum->order_number = "=\"{$datum->order_number}\"";
        }

        if (in_array($deal_type, [105, 114])) {
            //优惠彩金
            \Utils\Utils::exportExcel('BonusFlow',$this->bonusTitle,$data);
        } else {
            foreach ($this->enTitle as &$value){
                $value = $this->lang->text($value);
            }
            array_unshift($data,$this->enTitle);
            //交易流水
            \Utils\Utils::exportExcel('TransactionFlow',$this->title,$data);
        }
        foreach ($this->enTitle as &$value){
            $value = $this->lang->text($value);
        }
        array_unshift($data,$this->enTitle);

        \Utils\Utils::exportExcel('TransactionFlow',$this->title,$data);
    }
};
