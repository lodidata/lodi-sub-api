<?php
use Utils\Www\Action;

return new class extends Action {
    const TOKEN = true;
    const TITLE = "用户充值活动信息";
    const DESCRIPTION = "返回包括可参加活动";
    const TAGS = "充值提现";
    const SCHEMAS = [
        "list" => [

        ],
        "canGetBothActive" => "bool(required) #是否允许同时参与多个充值活动"
    ];

    public function run()
    {
        //2，新人首充，3，每日首充
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $global = \Logic\Set\SystemConfig::getModuleSystemConfig('activity');
        $canGetBothActive=true;
        //判断是否允许同时参与多个充值活动
        if(isset($global['canGetBothActivity']) && $global['canGetBothActivity'] == false ) {
            $canGetBothActive = false;
        }
        $condition = ['language_id' => 1, 'type_id' => [2, 3], 'status' => 'enabled'];//type_id = 2 新人首充，type_id = 3 每日首充
        $activeData = \Model\Active::whereRaw("FIND_IN_SET('enabled',status)")
            ->whereIn('type_id', $condition['type_id'])
            ->where('begin_time', '<', date('Y-m-d H:i:s'))
            ->where('end_time', '>', date('Y-m-d H:i:s'))
            ->selectRaw('id,title,vender_type,type_id')
            ->orderBy('type_id')
            ->get()->toArray();
        //判断充值活动是否为空
        if(empty($activeData)){
//            $activeData = [["id" => 0, "title" => "不参加优惠活动！！"]];
            return ['list' => $activeData,'canGetBothActive'=>$canGetBothActive];
        }
        foreach ($activeData as &$active){
            if($active['vender_type'] == 2)
                $active['title'] = $active['title'] . $this->lang->text("online recharge only");
            if($active['vender_type'] == 3)
                $active['title'] = $active['title'] . $this->lang->text("offline recharge only");
            unset($active['vender_type']);
        }
        //判断今日是否有过充值记录
        $userId = $this->auth->getUserId();
        if( \Model\FundsDeposit::whereRaw("FIND_IN_SET('paid',status)")->where('money','>',0)->where('user_id', $userId)->where('created', '>', date('Y-m-d'))->first()){
//            $ativeData = [["id" => 0, "title" => "不参加优惠活动！！"]];
            return ['list' => [],'canGetBothActive'=>$canGetBothActive];
        }

        $activeData = DB::resultToArray($activeData);//格式兼容
        //判断是否新人
        if( \Model\FundsDeposit::whereRaw("FIND_IN_SET('paid',status)")->where('money','>',0)->where('user_id', $userId)->first()){
            foreach($activeData as $k=>$val){
                if($val['type_id'] == 2){//新人首充
                    unset($activeData[$k]);
                }
            }
//            array_push($activeData,["id" => 0, "title" => "不参加优惠活动！"]);
//            return ['list' => $activeData,'canGetBothActive'=>$canGetBothActive];
        }
//        array_push($activeData,["id" => 0, "title" => "不参加优惠活动！"]);
        $activeData = $activeData ? array_values($activeData) : [];
        return ['list' => $activeData,'canGetBothActive'=>$canGetBothActive];


    }

};