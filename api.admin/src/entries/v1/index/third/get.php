<?php

use Logic\Admin\BaseController;
use Logic\Define\CacheKey;

return new class() extends BaseController
{
    const TITLE = '首页统计第三部分';
    const DESCRIPTION = '';
    const TAGS = '首页统计';
    const PARAMS = [
        'start_date' => 'date #开始日期 默认为前8天',
        'end_date' => 'date(required) #结束日期 默认为前1天'
    ];
    const SCHEMAS = [
        [
            'register_new' => 'int #新注册数',
            'game_user_count' => 'int #活跃用户',
            'recharge_total' => 'float #总充值',
            'deposit_user_num' => 'int #总充值人数',
            'withdraw_total' => 'float #总兑换',
            'recharge_witchdraw' => 'float #充兑差',
            'dml_total' => 'float #总打码量',
            'inversion_rate' => 'float #转化率',
            'bet_today_kill_rate' => 'float #流水杀率',
            'revenue_today_kill_rate' => 'float #营收杀率',
            'old_user_deposit_num' => 'int #老充人数',
            'old_user_deposit_amount' => 'int #老充金额',
            'old_user_deposit_avg' => 'float #老用户平均付费',
            'arppu' => 'float #ARPPU',
            'next_day_extant' => 'float #次日留存',
            'day' => 'date #日期 2022-05-13'
        ]
    ];

    //前置方法
    protected $beforeActionList = [
       'verifyToken', 'authorize',
    ];

    public function run()
    {
        $params = $this->request->getParams();
        $start_date = isset($params['start_date']) ? $params['start_date'] : date('Y-m-d', strtotime('-8 day'));
        $end_date = isset($params['end_date']) ? $params['end_date'] : date('Y-m-d', strtotime('-1 day'));

        $index = new \Logic\Admin\AdminIndex($this->ci);
        $fields = '*';
        return $data = $index->third($start_date, $end_date, $fields);
    }

};
