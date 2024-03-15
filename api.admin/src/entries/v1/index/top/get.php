<?php

use Logic\Admin\BaseController;
use Logic\Define\CacheKey;

return new class() extends BaseController
{
    const TITLE = '首页顶部统计';
    const DESCRIPTION = '';

    const SCHEMAS = [
        'register_total' => "int #注册总数",
        'online_total' => "int #当前在线人数",
        'register_today_total' => 'int #今日新增注册数',
        'register_yesterday_parent' => 'float #昨天注册人数（同期）单位%',
        'day30_login_parent' => 'float #近30活跃留存 %',
        'day30_yesterday_login_parent' => 'float #近30活跃留存（昨日同期）%',
        'day15_login_parent' => 'float #近15活跃留存 %',
        'day15_yesterday_login_parent' => 'float #近15活跃留存（昨日同期）%',
        'revenue_today_total' => 'float #总营收',
        'revenue_yesterday_total_parent' => 'float #总营收(昨天同期)%',
        'withdraw_today_total' => 'float #总兑换',
        'withdraw_yesterday_total_parent' => 'float #总兑换(昨天同期)%',
        'recharge_today_total' => 'float #总充值',
        'recharge_yesterday_total_parent' => 'float #总充值(昨天同期) %',
        'new_recharge_today_total' => 'float #今天新增充值',
        'recharge_yesterday_parent' => 'float #今天充值总数(昨天同期) %',
        'day3_recharge_parent' => 'float #近3天充值留存',
        'day3_yesterday_recharge_parent' => 'float #近3天充值留存(昨天同期) %',
        'day15_recharge_parent' => 'float #近15天充值留存',
        'day15_yesterday_recharge_parent' => 'float #近15天充值留存(昨天同期) %',
        'bet_total' => 'float #总投注',
        'yesterday_bet_total_parent' => 'float #总投注(昨天同期) %',
        'revenue_today_kill_rate' => 'float #营收杀率',
        'revenue_yesterday_kill_rate_parent' => 'float #营收杀率(昨天同期) %',
        'bet_today_kill_rate' => 'float #流水杀率',
        'bet_yesterday_kill_rate_parent' => 'float #流水杀率(昨天同期) %',
        'arppu_today' => 'float #ARPPU',
        'arppu_yesterday_parent' => 'float #ARPPU(昨天同期) %',
        'active_total' => 'float #总活动赠送',
        'active_yesterday_parent' => 'float #总活动赠送(昨天同期) %',
        'rebet_total' => 'float #总活返奖',
        'rebet_yesterday_parent' => 'float #总活返奖(昨天同期) %',
        'bkge_amount_today' => 'float #返佣金额',
        'bkge_amount_yesterday_parent' => 'float #返佣金额(昨日同期) %',

    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];

    public function run()
    {
        $index = new \Logic\Admin\AdminIndex($this->ci);
        return $data = $index->first();
    }

};
