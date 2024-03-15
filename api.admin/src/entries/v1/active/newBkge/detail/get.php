<?php

use Logic\Admin\Active as activeLogic;
//use Model\Admin\Active;
use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const TITLE       = '返佣活动详情';
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
    
    public function run($id)
    {
        $data = \Model\Admin\ActiveBkge::find($id)->toArray();
        if(!$data){
            $this->lang->set(10015);
        }

        $data['rule'] = \DB::table('active_bkge_rule')->where('active_bkge_id',$id)->orderBy('min_lottery')->get([
            'rule_name',
            'min_lottery',
            'max_lottery',
            'min_deposit',
            'max_deposit',
            'min_winloss',
            'max_winloss',
            'bkge_scale',
        ]);

        $data['rule'] = $data['rule'] ? $data['rule']->toArray(): [];
        return $data;
    }
};
