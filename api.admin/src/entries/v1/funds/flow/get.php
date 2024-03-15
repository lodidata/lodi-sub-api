<?php

use Logic\Admin\BaseController;

return new class() extends BaseController {
    const STATE = '';

    const TITLE = '资金流水记录';

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
        $page = $this->request->getParam('page', 1);
        $size = $this->request->getParam('page_size', 10);
        $third_id = $this->request->getParam('third_id');
        $deduct_rebet = $this->request->getParam('deduct_rebet');

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
                       \DB::raw('IF(free_money > funds_deal_log.balance,funds_deal_log.balance,free_money) as free_money'),
//                       'free_money',
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
            if($third_id !=0){
                $sql="t2.third_id = {$third_id}";
            }else{
                $sql="t2.third_id is null";
            }
            $third_sql="SELECT t1.trade_no FROM `funds_withdraw` AS `t1` LEFT JOIN `transfer_order` AS `t2` ON `t1`.`trade_no` = `t2`.`withdraw_order` and t2.status='paid' WHERE t1.status='paid' and `t1`.`confirm_time` >= '{$stime}' AND `t1`.`confirm_time` <= '{$etime}' and $sql GROUP BY `t2`.`third_id`";
            $query->whereRaw("funds_deal_log.order_number in ($third_sql)");
        }

        $user_id && $query->where('funds_deal_log.user_id', '=', $user_id);
        $stime && $query->where('funds_deal_log.created', '>=', $stime);
        $etime && $query->where('funds_deal_log.created', '<=', $etime );
        //有的名字超过20个字符 在funds_deal_log表被截断了
        if($username){
            $user_id = DB::connection('slave')->table('user')->where('name',$username)->value('id');
            $query->where('funds_deal_log.user_id', $user_id);
        }
        $order_number && $query->where('funds_deal_log.order_number', '=', $order_number);
        $deal_type && $query->whereRaw("funds_deal_log.deal_type in  ({$deal_type})");
        $deal_category && $query->where('funds_deal_log.deal_category', '=', $deal_category);
        if (in_array($deal_type, [701, 702, 703])) {
            $deductQuery = DB::table('rebet_deduct')
                ->whereRaw('rebet_deduct.user_id = funds_deal_log.user_id')
                ->whereRaw('rebet_deduct.order_number = funds_deal_log.order_number')
                ->whereRaw('rebet_deduct.type = funds_deal_log.deal_type');
            $deductRebet = (clone $deductQuery)->selectRaw('sum(rebet_deduct.deduct_rebet)')->toSql();
            $rebet = (clone $deductQuery)->selectRaw('sum(rebet_deduct.rebet)')->toSql();
            $isDeduct = (clone $deductQuery)->selectRaw('sum(IF(rebet_deduct.deduct_rebet > 0,1,0))')->toSql();
            $query->addSelect([
                DB::raw("({$deductRebet}) as deduct_rebet"),
                DB::raw("IFNULL(({$rebet}),funds_deal_log.deal_money) as deal_money"),
                DB::raw("({$isDeduct}) as is_deduct"),
                DB::raw("funds_deal_log.deal_money as rebet"),
            ]);
            if (strlen($deduct_rebet)) {
                $deductQuery->selectRaw('1')
                    ->groupBy(['rebet_deduct.order_number'])
                    ->havingRaw('sum(IF(rebet_deduct.deduct_rebet > 0,1,0)) > 0');;
                if ($deduct_rebet > 0) {
                    $query->whereRaw("EXISTS({$deductQuery->toSql()})");
                } else {
                    $query->whereRaw("NOT EXISTS({$deductQuery->toSql()})");
                }
            }
        }

        $count = clone $query;
        $attributes['total'] = $count->count();

        $attributes['size'] = $size;
        $attributes['number'] = $page;

        //1交易金额 2交易打码量 3主钱包余额 4实际打码量 5应有打码量 6可提余额
        $field_id = $this->request->getParam('field_id', '');
        $sort_way = $this->request->getParam('sort_way', 'asc');
        if(!in_array($sort_way, ['asc', 'desc'])) $sort_way = 'asc';
        $str = '';
        switch ($field_id)
        {
            case 1:
                $field_id = 'funds_deal_log.deal_money' ;
                break;

            case 2:
                $field_id = 'withdraw_bet';
                break;

            case 3:
                $field_id = 'funds_deal_log.balance';
                break;

            case 4:
                $field_id = 'total_bet';
                break;

            case 5:
                $field_id = 'total_require_bet';
                break;

            case 6:
                $field_id = 'free_money';
                break;
        }

        if(!empty($field_id)){
            $query = $query->orderBy($field_id, $sort_way);
        }
        $data = $query->orderby('funds_deal_log.created', 'desc')
                      ->orderby('funds_deal_log.id', 'desc')
                      ->forPage($page, $size)
                      ->get()
                      ->toArray();

        // dd(DB::getQueryLog());exit;
        foreach ($data as &$datum) {
            $types = \Logic\Funds\DealLog::getDealLogTypeFlat();
            $datum->deal_type_name = $types[$datum->deal_type] ?? '';
            $datum->deal_number = (string) $datum->deal_number;
//            if ($datum->free_money > $datum->balance) {
//                $datum->free_money = $datum->balance;
//            }
        }

        return $this->lang->set(0, [], $data, $attributes);
    }
};
