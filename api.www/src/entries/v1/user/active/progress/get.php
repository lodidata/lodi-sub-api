<?php

use Logic\Set\SystemConfig;
use Utils\Www\Action;

return new class extends Action {
    const TOKEN = true;
    const TITLE = "流水进度条";
    const TAGS = "进度条";
    const QUERY = [];
    const SCHEMAS = [
        [
            'id'     => 'int(required) #ID',
            "status" => "string() #状态值(pass:通过, rejected:拒绝, pending:待处理, undetermined:未解决的)",
            "state"  => "string() #设置状态,有apply就显示,没有就不显示(apply:可申请, auto:自动参与,manual:手动的)",
        ]
    ];

    public function run() {
        $verify = $this->auth->verfiyToken();
        if(!$verify->allowNext()) {
            return $verify;
        }
        $gameId     = $this->request->getParam('gameId');
        $user_id    = $this->auth->getUserId();
        $key        = \Logic\Define\CacheKey::$perfix['progress'].$user_id.':'.$gameId;
        $redisData  =  $this->redis->get($key);
        $attr       = [];
        if (SystemConfig::getModuleSystemConfig('direct')['direct_switch']){
            $attr['return_ratio'] = $this->return_ratio($user_id);
            $return_ratio         = $attr['return_ratio'] / 100;

        }
        if(!empty($redisData)){
            return $this->lang->set(0, [], json_decode($redisData,true), $attr);
        }
        $gameMenuId = DB::table('game_menu')->where('pid', $gameId)->pluck('id')->toArray();
        $data       = [
            'day'   => [
                'completeAmount' => 0,        //完成金额
                'targetAmount'   => 0,          //目标金额
                'rewardAmount'   => 0,        //派奖金额
                'sendTime'       => '',          //派送时间
            ],
            'week'  => [
                'completeAmount' => 0,
                'targetAmount'   => 0,
                'rewardAmount'   => 0,
                'sendTime'       => '',
            ],
            'month' => [
                'completeAmount' => 0,
                'targetAmount'   => 0,
                'rewardAmount'   => 0,
                'sendTime'       => '',
            ]
        ];
        $day        = $this->dayActive($user_id, $gameMenuId);
        $week       = $this->getActive('week', $user_id, $gameId);
        $month      = $this->getActive('month', $user_id, $gameId);

        if($day) {
            $sendTime=$this->redis->get(\Logic\Define\CacheKey::$perfix['rebot_time']);
            $rebot_time_minute = $this->redis->get(\Logic\Define\CacheKey::$perfix['rebot_time_minute']);
            if ($attr['return_ratio']){
                $day['rewardAmount']               = sprintf('%.0f', $day['rewardAmount']  * (1 + $return_ratio)); //元
            }
            $data['day'] = [
                'completeAmount' => $day['betAmount'],          //完成金额
                'targetAmount'   => $day['completeAmount'],       //目标金额
                'rewardAmount'   => $day['rewardAmount'],        //派奖金额
                'sendTime'       => !empty($sendTime) ? date("Y-m-d {$sendTime}:{$rebot_time_minute}:00",strtotime('+1 day')):'',          //派送时间
            ];
        }
        if($week) {
            if ($attr['return_ratio']){
                $week['rewardAmount']       = sprintf('%.0f', $week['rewardAmount']  * (1 + $return_ratio)); //元
            }
            $data['week'] = [
                'completeAmount' => $week['betAmount'],          //完成金额
                'targetAmount'   => $week['completeAmount'],       //目标金额
                'rewardAmount'   => $week['rewardAmount'],        //派奖金额
                'sendTime'       => !empty($week['sendTime']) ? date("Y-m-d {$week['sendTime']}",strtotime('+1 week last monday')):'',          //派送时间
            ];
        }
        if($month) {
            if ($attr['return_ratio']){
                $month['rewardAmount']             = sprintf('%.0f', $month['rewardAmount']  * (1 + $return_ratio)); //元
            }
            //28日过了12点则为下个月
            if(date('d') > 28 || (date('d') == 28) && date('H') > 12){
                $yearTime=date('Y', strtotime('+1 month'));
                $monthTime = date('n',time());
                if($monthTime >= 12){
                    $monthTime = 1;
                }else{
                    $monthTime += 1;
                }
                if($monthTime < 10){
                    $monthTime = '0'.$monthTime;
                }
                $monthTime = $yearTime.'-'.$monthTime;
            }else{
                $monthTime=date('Y-m');
            }
            $data['month'] = [
                'completeAmount' => $month['betAmount'],          //完成金额
                'targetAmount'   => $month['completeAmount'],       //目标金额
                'rewardAmount'   => $month['rewardAmount'],        //派奖金额
                'sendTime'       => !empty($month['sendTime']) ? date("{$monthTime}-28 H:i:s",strtotime($month['sendTime'])): '',          //派送时间
            ];
        }

        $this->redis->setex($key,600,json_encode($data));
        return $this->lang->set(0, [], $data, $attr);
    }

    public function dayActive($user_id, $gameMenuId) {
        $day  = date('Y-m-d', time());
        $user = DB::table('user')->where('id', $user_id)->first();
        if(empty($user)) {
            $this->logger->debug('进度条:用户id:' . $user . '不存在');
            return false;
        }
        $rebetConfig = DB::table('rebet_config')->where('user_level_id', $user->ranting)->where('status_switch', 1)->whereIn('game3th_id', $gameMenuId)->get()->toArray();
        if(empty($rebetConfig)) {
            $this->logger->debug('用户id:' . $user_id . ',用户等级:' . $user->ranting . ',暂无日返水规则');
            return false;
        }
        $betAmount      = 0;   //投注金额
        $completeAmount = 0; //目标金额
        $rewardAmount   = 0;   //派奖金额
        foreach($rebetConfig as $value) {
            $orderAmount = DB::table('order_game_user_middle')->where('user_id', $user_id)->where('play_id', $value->game3th_id)->where('date', $day)->sum('bet');
            $this->logger->debug('日活动,用户id:' . $user_id . ',游戏id:' . $value->game3th_id . ',流水:' . $orderAmount);
            if(!empty($value->rebot_way)) {
                $rebot_way = json_decode($value->rebot_way, true);
                if($rebot_way['type'] == 'betting') {
                    foreach($rebot_way['data']['value'] as $config) {
                        $v            = explode(';', $config);
                        $v1           = explode(',', $v[0]);
                        $min          = bcmul($v1[0], 100);
                        $max          = bcmul($v1[1], 100);
                        $configAmount = bcmul($v[1], 100);
                        if($orderAmount >= $min && $orderAmount < $max) {
                            if($rebot_way['data']['status'] == 'percentage') {
                                //按流水百分比
                                $money = bcmul($orderAmount, bcdiv($configAmount, 10000, 4), 2);
                                if($value->rebet_ceiling) {
                                    $rebet_ceiling=bcmul($value->rebet_ceiling, 100);
                                    $money = ($money >= $rebet_ceiling) ? $rebet_ceiling : $money;
                                }
                            } else {
                                //按固定金额
                                $money = $configAmount;
                            }
                            $this->logger->debug('日活动,用户id:' . $user_id . ',游戏id:' . $value->game3th_id . ',流水:' . $orderAmount.',派奖金额'.$money);
                            $completeAmount = bcadd($completeAmount, $max);
                            $rewardAmount   = bcadd($rewardAmount, $money);
                        }
                    }
                }
            }
            $betAmount = bcadd($betAmount, $orderAmount);
        }
        return compact('betAmount', 'completeAmount', 'rewardAmount');
    }

    public function getActive($type, $user_id, $gameId) {
        switch($type) {
            case 'week':
                $start        = date('Y-m-d', (time() - ((date('w') == 0 ? 7 : date('w')) - 1) * 24 * 3600));
                $end          = date('Y-m-d', time());
                $activityType = 8;
                $table        = 'order_game_user_week';
                break;
            case 'month':
                $start        = date("Y-m-28", strtotime(date('Y-m-01')) - 86400);
                $end          = date('Y-m-27');
                $activityType = 9;
                $table        = 'order_game_user_day';
                break;
        }
        $date     = date('Y-m-d H:i:s', time());
        $activity = \DB::table("active")->where('type_id', '=', $activityType)->where('status', '=', "enabled")->where('begin_time', '<', $date)->where('end_time', '>', $date)->first(['id', 'name', 'type_id']);
        if(empty($activity)) {
            $this->logger->debug("暂无{$type}返水活动");
            return false;
        }
        $rule = \DB::table("active_rule")->where("template_id", '=', $activity->type_id)->where("active_id", '=', $activity->id)->first(['id', 'issue_time', 'issue_cycle', 'issue_mode', 'rule']);
        if(empty($rule) || empty($rule->rule)) {
            $this->logger->debug("{$activity->name}活动暂未配置规则");
            return false;
        }
        $ruleData = json_decode($rule->rule, true);
        if(empty($ruleData)) {
            $this->logger->debug("{$activity->name}活动暂未配置规则");
            return false;
        }
        $betAmount      = 0;   //投注金额
        $completeAmount = 0; //目标金额
        $rewardAmount   = 0;   //派奖金额
        $sendTime       = $rule->issue_time;

        foreach($ruleData as $val) {
            $val = (array)$val;
            if($val['game_menu_id'] == $gameId) {
                $gameType=DB::table('game_menu')->where('id',$gameId)->value('type');
                $orderAmount = DB::table($table)->where('user_id', $user_id)->where('date', '>=', $start)->where('date', '<=', $end)->where('game', $gameType)->sum('bet');
                $this->logger->debug("{$type}活动," . '用户id:' . $user_id . ',游戏id:' . $val['game_menu_id'] . ',流水:' . $orderAmount);
                if($val['type'] == 'betting') {
                    foreach($val['data']['value'] as $config) {
                        $v            = explode(';', $config);
                        $v1           = explode(',', $v[0]);
                        $min          = bcmul($v1[0], 100);
                        $max          = bcmul($v1[1], 100);
                        $configAmount = bcmul($v[1], 100);
                        if($orderAmount >= $min && $orderAmount < $max) {
                            if($val['data']['status'] == 'percentage') {
                                //按流水百分比
                                $money = bcmul($orderAmount, bcdiv($configAmount, 10000, 4), 2);
                            } else {
                                //按固定金额
                                $money = $configAmount;
                            }
                            $this->logger->debug('日活动,用户id:' . $user_id . ',游戏id:' . $val['game_menu_id'] . ',流水:' . $orderAmount.',派奖金额'.$money);

                            $rewardAmount   = bcadd($rewardAmount, $money);
                        }
                        $completeAmount = $max;
                    }
                }
                $betAmount = bcadd($betAmount, $orderAmount);
            }
        }
        return compact('betAmount', 'completeAmount', 'rewardAmount', 'sendTime');
    }

    /**
     * 当前返水比例
     * @param int register_count 注册人数
     * @param int recharge_count 充值人数
     * @return int  返回当前返水比例
     */
    public function return_ratio($user_id){
        $key        = \Logic\Define\CacheKey::$perfix['direct_current_return_ratio'].$user_id;
        $redisData  =  $this->redis->get($key);
        if(!empty($redisData)){
            return $redisData;
        }

        $direct_bkge   = \DB::table('user_data')->where('user_id','=',$user_id)->first(['direct_deposit','direct_register']);
        if ($direct_bkge){
            $current        = DB::table('direct_bkge')
                ->where('register_count','<=',$direct_bkge->direct_register ?? 0)
                ->where('recharge_count','<=',$direct_bkge->direct_deposit  ?? 0)
                ->orderByDesc('bkge_increase')
                ->first('bkge_increase');
            $currentBkgeIncrease = $current->bkge_increase ?? 0;
            $this->redis->setex($key,5,$currentBkgeIncrease);
        }
        return $currentBkgeIncrease ?? 0;
    }
};