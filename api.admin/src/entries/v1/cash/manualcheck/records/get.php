<?php

use Logic\Admin\BaseController;
use Logic\Funds\DealLog;

return new class() extends BaseController {
    const STATE = '';
    const TITLE = '大额加款记录';
    const DESCRIPTION = '';
    
    const QUERY = [
        'page'          => "int #第几页",
        'page_size'     => 'int #每页多少条',
        'username'      => 'string #用户名',
        'apply_admin'   => 'string #加款人',
        'money_start'   => 'string #加款金额',
        'money_end'     => 'string #加款金额',
        'time_begin'    => 'date #提交时间  开始时间(2022-12-24)',
        'time_end'      => 'date #提交时间  结束时间(2022-12-24)',
        'type'          => 'int #加款类型',
        'status'        => 'int # 0:待处理，1：同意，2：拒绝',
    ];
    
    const PARAMS = [];
    const SCHEMAS = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run() {
        $username    = $this->request->getParam('username');
        $status      = $this->request->getParam('status',null);
        $apply_admin = $this->request->getParam('apply_admin');
        $money_start = $this->request->getParam('money_start');
        $money_end   = $this->request->getParam('money_end');
        $type        = $this->request->getParam('type');
        $stime       = $this->request->getParam('time_begin',date('Y-m-d'));
        $etime       = $this->request->getParam('time_end',date('Y-m-d'));
        $page        = $this->request->getParam('page') ?? 1;
        $size        = $this->request->getParam('page_size') ?? 20;

        $manual_deal_type = DealLog::getManualDealTypes(2);

        $not_id = \DB::connection('slave')->table('label')
                     ->whereIn('title', ['试玩', '测试'])
                     ->pluck('id')
                     ->toArray();

        $query = \DB::connection('slave')->table('funds_manual_check as fmc')
                    ->leftJoin('user', 'fmc.user_id', '=', 'user.id')
                    ->leftJoin('admin_user', 'fmc.apply_admin_uid', '=', 'admin_user.id')
                    ->whereNotIn('user.tags', [4, 7]);

        $username && $query->where('fmc.username', '=', $username);
        $apply_admin && $query->where('admin_user.username', '=', $apply_admin);
        $money_start && $query->where('fmc.money', '>=', $money_start);
        $money_end && $query->where('fmc.money', '<=', $money_end);
        $type && $query->where('fmc.type', '=', $type);
        $stime && $query->where('fmc.created', '>=', $stime);
        $etime && $query->where('fmc.created', '<=', $etime . ' 23:59:59');
        $not_id && $query->whereNotIn('user.id', $not_id);
        isset($status) && $query->where('fmc.status',$status);
        $total_query = clone $query;
        $attributes['total'] = $query->count();
        $query = $query->orderBy('fmc.id', 'DESC')
                       ->forPage($page, $size);

        $attributes['size'] = $size;
        $attributes['number'] = $page;
        $attributes['total_money'] = $total_query->where('fmc.status',1)->sum(\DB::raw('fmc.money + fmc.coupon'))??0;
        $attributes['type_list']   = DealLog::getManualCheckDealTypes();

        $data = $query->get([
            'fmc.created',
            'admin_user.username as admin_user',
            'fmc.memo',
            'fmc.username',
            'fmc.id',
            'fmc.type',
            'fmc.money',
            'fmc.balance',
            'fmc.coupon',
            'fmc.status',
        ])
                      ->toArray();

        foreach ($data as &$datum) {
            $datum->adjust_type = $this->lang->text($manual_deal_type[$datum->type]) ?? null;
        }

        return $this->lang->set(0, [], $data, $attributes);
    }
};
