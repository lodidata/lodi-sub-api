<?php

use Logic\Admin\BaseController;
use Logic\Define\CacheKey;

return new class() extends BaseController
{
    const TITLE = '首页统计第四部分';
    const DESCRIPTION = '';
    const TAGS = '首页统计';
    const PARAMS = [
        'start_date' => 'date #开始日期 默认为前8天',
        'end_date' => 'date(required) #结束日期 默认为前1天'
    ];
    const SCHEMAS = [
        [
            'register_new' => 'int #新注册数',
            'recharge_first_count' => 'int #首充用户数',
            'user_agent' => 'int #新增代理数',
            'agent_new_user_count' => '新代理新首充会员数',
            'recharge_first_money' => 'float #新增充值金额',
            'recharge_first_avg' => 'float #首充会员平均金额',
            'new_register_withdraw_amount' => 'float #新增取款金额',
            'no_agent_user_num' => 'int #主渠道新增注册',
            'inversion_rate' => 'float #新充转化率',
            'new_deposit_user_dml' => 'float #新充打码量',
            'new_deposit_retention' => 'float #次日付费留存',
            'new_deposit_bet_retention' => 'float #次日活跃留存',
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
