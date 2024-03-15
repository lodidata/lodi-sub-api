<?php

use Logic\Admin\Active as activeLogic;
//use Model\Admin\Active;
use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const TITLE       = '新代理返佣活动列表';
    const DESCRIPTION = '';
    
    const QUERY       = [
    ];
    const PARAMS      = [

    ];
    const SCHEMAS     = [
        [
            "id"=> 56,
            "name"=> "活动名称",
            "stime"=> '开始时间',
            "etime"=> '结束时间',
            "games"=> '返佣类目',
            "bkge_date"=> 'day-每日，week-每周，month-每月',
            "bkge_time"=> '返佣具体时间点',
            "condition_opt"=> '返佣条件（lottery-有效投注，deposit-充值，winloss-盈亏）',
            "data_opt"=> '返佣数据规则（lottery-有效投注，deposit-充值，winloss-盈亏）',
            "status"=> 'disabled 停用,enabled 启用',
            "created"=> '创建时间',
            "updated"=> '更新时间',
        ]
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];
    
    public function run()
    {
        $data = \Model\Admin\ActiveBkge::orderBy('id','desc')->get()->toArray();
        foreach ($data as &$val) {
            $new_bkge_set = json_decode($val['new_bkge_set'], true);
            $val['rule']  = $new_bkge_set['bkge_ratio_rule'];
            $val['deposit_withdraw_fee_ratio'] = $new_bkge_set['deposit_withdraw_fee_ratio'];
            $val['winloss_fee_ratio']          = $new_bkge_set['winloss_fee_ratio'];
            $val['valid_user_num']             = $new_bkge_set['valid_user_num'];
            $val['valid_user_deposit']         = $new_bkge_set['valid_user_deposit'];
            $val['valid_user_bet']             = $new_bkge_set['valid_user_bet'];
            unset($val['new_bkge_set']);
        }
        return $data;
    }
};
