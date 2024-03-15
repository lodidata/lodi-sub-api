<?php

use Logic\Admin\BaseController;

return new class() extends BaseController {
    const TITLE = '获取报表最后更新时间';
    const DESCRIPTION = '获取报表最后更新时间';
    
    const QUERY = [
    ];

    const PARAMS = [
        'type' => 'string #报表类型 main_day:总报表 - 按日查询  main_month:总报表 - 按月查询  main_week:总报表 - 按周查询  agent:代理报表 user:用户报表  out_in:出入款 lottery:彩种  profit:盈亏报表',
    ];
    const SCHEMAS = [
            'updated' => 'string #最后更新时间',
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run() {

        $type = $this->request->getParam('type', false);

        //传入参数对应报表
        $maps = [
            'main_day'   => 'rpt_plat_earnlose_day',  //总报表 - 按日查询
            'main_month' => 'rpt_plat_earnlose_month',  //总报表 - 按月查询
            'main_week'  => 'rpt_plat_earnlose_week',  //总报表 - 按周查询

            'agent' => 'rpt_userreport',  //代理报表
            'user'  => 'rpt_userreport',  //用户报表

            'out_in' => 'rpt_funds_outAndIncome_day',  //出入款

            'lottery' => 'rpt_lottery_earnlose',  //彩种

            'profit' => 'rpt_userlottery_earnlose',  //盈亏报表
        ];

        if (!isset($maps[$type])) {
            return $this->lang->set(-2);
        }

        $table = $maps[$type];

        $result = \DB::table($table)
                     ->max('create_time');

        return $this->lang->set(0, [], [
            'updated' => $result,
        ]);
    }

};