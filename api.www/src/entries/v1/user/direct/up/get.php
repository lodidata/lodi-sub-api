<?php

use Utils\Www\Action;

return new class() extends Action {
    const TITLE = '获取返水提升任务列表';
    const DESCRIPTION = '获取返水提升任务列表';
    const QUERY = [

    ];
    const SCHEMAS = [
        "serial_no"       => "int() #编号",
        "register_count"  => "int() #所需注册人数",
        "recharge_count"  => "int() #所需充值人数",
        "bkge_increase"   => "string() #日提升返水比例",
        "bkge_increase_week"   => "string() #周提升返水比例",
        "bkge_increase_month"   => "string() #月提升返水比例",
        "direct_register" => "int() #直推已注册人数",
        "direct_recharge" => "int() #直推已充值人数",
   ];

    public function run()
    {
        // 鉴权
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $user_id = $this->auth->getUserId();
        if (empty($user_id)) {
            return $this->lang->set(11, [], [], []);
        }

        // 直推返水奖励
        $res= DB::table('direct_bkge')
            ->select(['serial_no', 'register_count', 'recharge_count', 'bkge_increase', 'bkge_increase_week', 'bkge_increase_month'])
            ->orderBy('serial_no')
            ->get()
            ->toArray();

        // 已经完成人数
        $progress = DB::table('user_data')->where('user_id', $user_id)->first(['direct_register', 'direct_deposit']);
        foreach($res as &$val) {
            $val->direct_register = $progress->direct_register;
            $val->direct_recharge = $progress->direct_deposit;
        }

        return $res;

    }
};
