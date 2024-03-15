<?php

use Logic\Admin\BaseController;

return new class() extends BaseController {
    const TITLE = '直推反水奖励';
    const DESCRIPTION = '';

    const QUERY = [
        'id'        => 'int(required) #用户id',
    ];

    const PARAMS = [];
    const SCHEMAS = [
    ];

    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run($id) {
        $this->checkID($id);
        $progress = DB::table('user_data')
            ->where('user_id', $id)
            ->addSelect([
                'direct_deposit',
                'direct_register',
                'direct_bkge_increase',
                'direct_bkge_increase_week',
                'direct_bkge_increase_month',
            ])
            ->first();
        $res = DB::table('direct_bkge')
            ->select(['serial_no', 'register_count', 'recharge_count', 'bkge_increase', 'bkge_increase_week', 'bkge_increase_month'])
            ->orderBy('serial_no')
            ->get()
            ->toArray();
        $conf = new stdClass();
        if (!empty($progress)) {
            foreach ($res as $val) {
                if ($progress->direct_deposit >= $val->recharge_count && $progress->direct_register >= $val->register_count) {
                    $conf = $val;
                }
            }
            $conf->register_count = $progress->direct_register;
            $conf->recharge_count = $progress->direct_deposit;
            $conf->bkge_increase = max($progress->direct_bkge_increase ?? 0, $conf->bkge_increase ?? 0);
            $conf->bkge_increase_week = max($progress->direct_bkge_increase_week ?? 0, $conf->bkge_increase_week ?? 0);
            $conf->bkge_increase_month = max($progress->direct_bkge_increase_month ?? 0, $conf->bkge_increase_month ?? 0);
        }
        return (array)$conf;
    }
};
