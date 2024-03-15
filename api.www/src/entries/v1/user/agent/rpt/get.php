<?php

use Utils\Www\Action;

return new class extends Action
{
    const TOKEN = true;
    const TITLE = "代理统计";
    const TAGS = "代理统计";
    const SCHEMAS = [ ];


    public function run() {
        $verify = $this->auth->verfiyToken();

        if (!$verify->allowNext()) {
            return $verify;
        }

        $uid = $this->auth->getUserId();
        $agent = new \Logic\User\Agent($this->ci);
        $user_agent = $agent->generalizeList($uid);

        $agent_info = [
            'all_agent'        => $user_agent['all_agent'],
            'direct_agent'     => 0,
            'next_agent'       => 0,
            'proportion'       => "--",
        ];
        $agent_info['direct_agent'] = \Model\UserAgent::where('uid_agent', $uid)->count();//直系下级代理人数
        $agent_info['next_agent'] = $agent_info['all_agent'] - $agent_info['direct_agent'];//下下级代理数

        if($agent_info['next_agent'] < 0){
            $agent_info['next_agent'] = 0;
        }

        //获取总利润
        $yesterday_time      = date('Y-m-d', (time() - 24 * 60 * 60));
        $agent_info['profits'] = \DB::table('unlimited_agent_bkge')
            ->where('user_id', '=', $uid)
            ->Where('date', '=', $yesterday_time)
            ->sum('bkge');
        //获取总流水
        $agent_info['bet_amount'] = \DB::table('unlimited_agent_bkge')
            ->where('user_id', '=', $uid)
            ->Where('date', '=', $yesterday_time)
            ->sum('bet_amount');
        //获取总流水
        $agent_info['fee_amount'] = \DB::table('unlimited_agent_bkge')
            ->where('user_id', '=', $uid)
            ->Where('date', '=', $yesterday_time)
            ->sum('fee_amount');

        $params = $this->request->getParams();
        $start_time = $params['start_time'] ?? '';
        if(empty($start_time)){
            $start_time = date('Y-m-d',time());
        }
        $end_time = $params['end_time'] ?? '';
        if(empty($end_time)){
            $end_time = date('Y-m-d',time());
        }

        //获取时间段数据统计
        $time_result = $agent->timeInfo($uid, $start_time, $end_time);
        if(!empty($time_result)){
            $time_info = [
                "total_bet_amount"      => $time_result['total_bet_amount'],//总流水
                "bet_amount"            => $time_result['bet_amount'],//自身流水
                "next_bet_amount"       => $time_result['next_bet_amount'],//下级流水
                "profits"               => $time_result['profits'],//净利润
                "valid_amount"          => $time_result['valid_amount'],//有效投注
                "new_register"          => $time_result['new_register'],//新注册用户
                "valid_user"            => $time_result['valid_user'],//有效用户
                "first_recharge_user"   => $time_result['first_recharge_user'],//首充人数
                "all_recharge_amount"   => $time_result['all_recharge_amount'],//总充值金额
                "first_recharge_amount" => $time_result['first_recharge_amount'],//首次充值金额
                "bet_user"              => $time_result['bet_user'],//投注人数
                "proportion"            => "--",//占成
                "fee_amount"            => $time_result['fee_amount'],//公司成本
            ];
        }else{
            $time_info = [
                "total_bet_amount"      => 0,
                "bet_amount"            => 0,
                "next_bet_amount"       => 0,
                "profits"               => 0,
                "valid_amount"          => 0,
                "new_register"          => 0,
                "valid_user"            => 0,
                "first_recharge_user"   => 0,
                "all_recharge_amount"   => 0,
                "first_recharge_amount" => 0,
                "bet_user"              => 0,
                "proportion"            => "--",//占成
                "fee_amount"            => 0,
            ];
        }

        //总流水统计
        $bet_list = $agent->betStatic($uid);
        if(empty($bet_list)){
            $bet_list = [];
        }

        //昨日游戏流水统计
        $yesterday_info      = [
            'fee_amount' => 0,//总成本
            'other_fee'  => [],//其他成本
        ];
        $game_list = [];
        $yesterday_bkge_info = \DB::table('unlimited_agent_bkge')->where('user_id', '=', $uid)->Where('date', '=', $yesterday_time)->first();
        if(!empty($yesterday_bkge_info)) {
            $yesterday_bkge_info          = (array)$yesterday_bkge_info;
            $bet_yes_list = [];
            $bkge_list = [];
            $proportion_list = [];
            $fee_list = [];
            $yesterday_info['fee_amount'] = $yesterday_bkge_info['fee_amount'];
            if(!empty($yesterday_bkge_info['bet_amount_list'])){
                $bet_array = json_decode($yesterday_bkge_info['bet_amount_list'], true);
                if(!empty($bet_array) && is_array($bet_array)){
                    $bet_yes_list = $bet_array;
                }
            }
            if(!empty($yesterday_bkge_info['bkge_list'])){
                $bkge_array = json_decode($yesterday_bkge_info['bkge_list'], true);
                if(!empty($bkge_array) && is_array($bkge_array)){
                    $bkge_list = $bkge_array;
                }
            }
            if(!empty($yesterday_bkge_info['proportion_list'])){
                $proportion_array = json_decode($yesterday_bkge_info['proportion_list'], true);
                if(!empty($proportion_array) && is_array($proportion_array)){
                    $proportion_list = $proportion_array;
                }
            }
            if(!empty($yesterday_bkge_info['fee_list'])){
                $fee_array = json_decode($yesterday_bkge_info['fee_list'], true);
                if(!empty($fee_array) && is_array($fee_array)){
                    $fee_list = $fee_array;
                }
            }
            $yesterday_info['other_fee']['coupon'] = 0;
            $yesterday_info['other_fee']['manual'] = 0;
            $yesterday_info['other_fee']['deposit'] = 0;
            $yesterday_info['other_fee']['revenue'] = 0;
            if(isset($fee_list['coupon_ratio_amount'])){
                $yesterday_info['other_fee']['coupon'] = $fee_list['coupon_ratio_amount'];
            }
            if(isset($fee_list['manual_ratio_amount'])){
                $yesterday_info['other_fee']['manual'] = $fee_list['manual_ratio_amount'];
            }
            if(isset($fee_list['deposit_ratio_amount'])){
                $yesterday_info['other_fee']['deposit'] = $fee_list['deposit_ratio_amount'];
            }
            if(isset($fee_list['revenue_ratio_amount'])){
                $yesterday_info['other_fee']['revenue'] = $fee_list['revenue_ratio_amount'];
            }
            foreach($bet_yes_list as $key => $val){
                $game_list[] = [
                    'game_name'  => $this->lang->text($key),
                    'bet'        => $val,
                    'bkge'       => $bkge_list[$key] ?? 0,
                    'proportion' => $proportion_list[$key] ?? 0,
                    'fee'        => $fee_list[$key] ?? 0,
                ];
            }
        }
        if(empty($yesterday_info['other_fee'])){
            $yesterday_info['other_fee'] = (object)[];
        }

        //检查开关
        $switch_list = \Model\Admin\SystemConfig::where('state','enabled')->where('module','admin_agent')->get();
        if(!$switch_list->isEmpty()){
            foreach($switch_list as $switch_k => $switch_v){
                foreach($time_info as $k => $v){
                    if($k == $switch_v->key && $switch_v->value == 0){
                        unset($time_info[$k]);
                    }
                }
            }
        }
        //兼容前端显示
        if(empty($time_info)){
            $time_info = (object)[];
        }

        $data = [
            'agent_info' => $agent_info,
            'time_info'  => $time_info,
            'bet_list'   => $bet_list,
            'game_list'  => $game_list,
            'yesterday_info' => $yesterday_info,
            'yesterday_time' => $yesterday_time,
        ];

        return $data;
    }

};