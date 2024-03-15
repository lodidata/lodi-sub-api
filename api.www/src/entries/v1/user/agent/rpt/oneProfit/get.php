<?php

use Utils\Www\Action;

return new class extends Action {
    const TOKEN = true;
    const TITLE = "盈亏统计";
    const TAGS = "盈亏统计";
    const SCHEMAS = [];

    public function run() {
        $verify = $this->auth->verfiyToken();

        if(!$verify->allowNext()) {
            return $verify;
        }

        $uid        = $this->auth->getUserId();

        //获取盈亏统计
        $earn_amount = 0;

        $params = $this->request->getParams();
        $time = $params['time'] ?? '';
        if(empty($time)){
            $month = date('Y-m',time());
        }else{
            $month = date('Y-m',strtotime($time));
        }

        $loseearn_list = \DB::table('agent_loseearn_month_bkge')
            ->where('user_id', '=', $uid)
            ->Where('date', '=', $month)
            ->get()->toArray();

        foreach($loseearn_list as $key => $val){
            $earn_amount = bcadd($val->bkge, $earn_amount, 2);
        }

        $data = [
            'earn_amount'   => $earn_amount,
        ];

        return $data;
    }
};