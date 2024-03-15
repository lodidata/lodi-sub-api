<?php
use Utils\Www\Action;
use Model\Admin\ActiveBkge as ActiveBkgeModel;

return new class extends Action
{
    const TOKEN = true;
    const TITLE = "返佣历史";
    const DESCRIPTION = "";
    const TAGS = "代理返佣";
    const QUERY = [
        "date"    => "date() #日期",
    ];

    const SCHEMAS = [
        [
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

    public function run(){
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $uid = $this->auth->getUserId();
        $date = $this->request->getParam('date');
        
        //如果传了指定时间，则查询指定时间的数据【注：这个时间不是指返佣时间】
        if (isset($date) && !empty($date)) {
            $res = \DB::table('new_bkge')
                ->where('user_id',$uid)
                ->whereRaw("DATE_FORMAT(bkge_time,'%Y-%m-%d') = '{$date}'")
                ->first(['bkge','winloss','valid_user_num','bkge_value as bkge_ratio','deposit_amount','withdraw_amount','deposit_withdraw_fee_ratio','bet_amount','winloss_fee_ratio','active_amount']);
        } else {
            //默认展示最近一次返佣时间的数据
            $res = \DB::table('new_bkge')
                ->where('user_id',$uid)
                ->orderBy('bkge_time', 'desc')
                ->first(['bkge','winloss','valid_user_num','bkge_value as bkge_ratio','deposit_amount','withdraw_amount','deposit_withdraw_fee_ratio','bet_amount','winloss_fee_ratio','active_amount']);
        }
//        $res = \DB::table('new_bkge')
//            ->where('user_id',$uid)
//            ->where('date', $date)
//            ->first(['bkge','winloss','valid_user_num','bkge_value as bkge_ratio','deposit_amount','withdraw_amount','deposit_withdraw_fee_ratio','bet_amount','winloss_fee_ratio','active_amount']);

        //查询用户所有返佣时间列表
        $bkge_time_list = DB::table('new_bkge')->where('user_id',$uid)->select('bkge_time')->get()->toArray();
        $res_time_list = [];
        foreach ($bkge_time_list as $value) {
            array_push($res_time_list, date("Y-m-d", strtotime($value->bkge_time)));
        }

        return $this->lang->set(0, [], ['bkge_list'=>$res, 'time_list'=>$res_time_list]);
    }
};