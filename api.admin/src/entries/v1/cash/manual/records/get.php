<?php

use Logic\Admin\BaseController;
use Logic\Funds\DealLog;

return new class() extends BaseController {
    const STATE = '';
    const TITLE = '手动存提记录';
    const DESCRIPTION = '';
    
    const QUERY = [
        'page'       => "int #第几页",
        'page_size'  => 'int #每页多少条',
        'username'   => 'string #用户名',
        'time_begin' => 'timestamp #开始时间',
        'time_end'   => 'timestamp #结束时间',
        'type'       => 'int #交易类型，从另一接口获取',
    ];
    
    const PARAMS = [];
    const SCHEMAS = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run() {
        $username = $this->request->getParam('username');
        $type = $this->request->getParam('type');
        $stime = $this->request->getParam('time_begin');
        $etime = $this->request->getParam('time_end');
        $page = $this->request->getParam('page') ?? 1;
        $size = $this->request->getParam('page_size') ?? 2;

        $manual_deal_type = DealLog::getManualDealTypes(2);

        $not_id = \DB::connection('slave')->table('label')
                     ->whereIn('title', ['试玩', '测试'])
                     ->pluck('id')
                     ->toArray();

        $query = \DB::connection('slave')->table('funds_deal_manual')
                    ->leftJoin('user', 'funds_deal_manual.user_id', '=', 'user.id')
                    ->leftJoin('admin_user', 'funds_deal_manual.admin_uid', '=', 'admin_user.id')
                    ->whereNotIn('user.tags', [4, 7]);

        $username && $query->where('funds_deal_manual.username', '=', $username);
        $type && $query->where('funds_deal_manual.type', '=', $type);
        $stime && $query->where('funds_deal_manual.created', '>=', strtotime($stime));
        $etime && $query->where('funds_deal_manual.created', '<=', strtotime($etime . ' 23:59:59'));
        $not_id && $query->whereNotIn('user.id', $not_id);
        $total_query = clone $query;

        $attributes['total'] = $query->count();
        $query = $query->orderBy('funds_deal_manual.id', 'DESC')
                       ->forPage($page, $size);

        $attributes['size'] = $size;
        $attributes['number'] = $page;
        $attributes['total_money'] = $total_query->sum('funds_deal_manual.money')??0;

        $data = $query->get([
            'user.id as user_id',
            'funds_deal_manual.id',
            'funds_deal_manual.username',
            'funds_deal_manual.front_money as balance_before',
            'funds_deal_manual.money as balance',
            'funds_deal_manual.balance as balance_after',
            'funds_deal_manual.type',
            'funds_deal_manual.operator_type',
            'funds_deal_manual.memo',
            'funds_deal_manual.valid_bet',
            'funds_deal_manual.wallet_type',
            'funds_deal_manual.withdraw_bet',
            'funds_deal_manual.updated as operation_time',
            'admin_user.username as admin_user',
        ])
                      ->toArray();

        foreach ($data as &$datum) {
            $datum->wallet_type_name = $datum->wallet_type == 1 ? '主钱包' : '子钱包';
            $datum->adjust_type = $manual_deal_type[$datum->type] ?? null;
            $datum->operation_time = date("Y-m-d H:i:s", $datum->operation_time);
        }

        return $this->lang->set(0, [], $data, $attributes);
    }
};
