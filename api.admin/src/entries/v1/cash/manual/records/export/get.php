<?php

use Logic\Admin\BaseController;
use Logic\Funds\DealLog;

return new class() extends BaseController {
    const STATE = '';
    const TITLE = '人工存提列表导出';
    const DESCRIPTION = '获取人工存提列表';

    const QUERY = [

    ];

    const PARAMS = [];
    const SCHEMAS = [

    ];

    protected $title=[
        'username'=>'用户名','wallet_type_name'=>'钱包类型','adjust_type'=>'调整类型','balance'=>'调整金额',
        'balance_before'=>'调整前余额','balance_after'=>'余额','withdraw_bet'=>'交易打码量','admin_user'=>'操作人',
        'operation_time'=>'操作时间','memo'=>'备注'
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
        $username = $this->request->getParam('username');
        $type = $this->request->getParam('type');
        $stime = $this->request->getParam('time_begin');
        $etime = $this->request->getParam('time_end');
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

        $total = $query->count();
        if($total > 50000){
            return $this->lang->set(701);
        }

        $query = $query->orderBy('funds_deal_manual.id', 'DESC');

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
            $datum->balance = bcdiv($datum->balance,100,2);
            $datum->balance_after = bcdiv($datum->balance_after,100,2);
            $datum->balance_before = bcdiv($datum->balance_before,100,2);
            $datum->withdraw_bet = bcdiv($datum->withdraw_bet,100,2);
        }

        Utils\Utils::exportExcel('manualRecords',$this->title,$data);

    }

};
