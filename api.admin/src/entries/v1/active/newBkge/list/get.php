<?php

use Logic\Admin\Active as activeLogic;
//use Model\Admin\Active;
use Logic\Admin\BaseController;
use Logic\User\Bkge;

return new class() extends BaseController
{
    const TITLE       = '新代理返佣列表';
    const DESCRIPTION = '';
    
    const QUERY       = [
        "page"          => 'int()',
        "page_size"     => "int()",
        "date"          => "date() #日期 202203",
        "agent_name"    => "string #代理名称",
    ];
    const SCHEMAS     = [
        [
            "agent_name"                 => "string(required) #代理名称",
            "date"                       => "string(required) #日期",
            "bkge_time"                  => "string(required) #返佣时间",
            "bkge"                       => "float(required) #返佣金额",
            "winloss"                    => "float(required) #盈亏金额",
            "valid_user_num"             => "int(required) #有效用户数",
            "bkge_ratio"                 => "float(required) #返佣比例",
            "deposit_amount"             => "float(required) #充值金额",
            "withdraw_amount"          => "float(required) #取款金额",
            "deposit_withdraw_fee_ratio" => "float(required) #充提费用",
            "bet_amount"                 => "float(required) #投注金额",
            "winloss_fee_ratio"          => "float(required) #盈亏费用",
            "active_amount"              => "float(required) #活动金额",
        ]
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];
    
    public function run()
    {
        $date_start = $this->request->getParam('start_time',date('Y-m-d'));
        $date_end = $this->request->getParam('end_time',date('Y-m-d'));
        if(!$date_start && !$date_end){
            return $this->lang->set(886,['日期不能为空']);
        }
        $page        = $this->request->getParam('page',1);
        $page_size   = $this->request->getParam('page_size',20);
        $agent_name  = $this->request->getParam('agent_name');

//        $query = \DB::table('new_bkge')
        $query = DB::connection("slave")->table('new_bkge')
            ->whereRaw("DATE_FORMAT(bkge_time,'%Y-%m-%d') >= '{$date_start}'")
            ->whereRaw("DATE_FORMAT(bkge_time,'%Y-%m-%d') <= '{$date_end}'");
        $agent_name && $query->where('user_name', $agent_name);
        $total = $query->count();
        $res = $query
                ->forPage($page, $page_size)
                ->get(['user_name as agent_name','date','bkge_time','bkge','winloss','valid_user_num','bkge_value as bkge_ratio','deposit_amount','withdraw_amount','deposit_withdraw_fee_ratio','bet_amount','winloss_fee_ratio','active_amount']);

        $attributes['total']  = $total;
        $attributes['number'] = $page;
        $attributes['size']   = $page_size;
        return $this->lang->set(0, [], $res, $attributes);
    }
};
