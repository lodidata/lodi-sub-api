<?php

use Logic\Set\SystemConfig;
use Utils\Www\Action;

return new class extends Action {
    const TOKEN = true;
    const TITLE = "返水总比例";
    const TAGS = "总比例";
    const QUERY = [];
    const SCHEMAS = [
    ];
    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        // 返回列
        $user_id    = $this->auth->getUserId();
        $key        = \Logic\Define\CacheKey::$perfix['backwater_num_'].$user_id;
        $redisData  = $this->redis->get($key);
        if(!empty($redisData)){
            return json_decode($redisData,true);
        }
        $total         =  $this->get_rebate_gid($user_id);
        $data['total'] = 0;
        if (!empty($total)){
            $data['total'] = max($total);
            if (SystemConfig::getModuleSystemConfig('direct')['direct_switch']){
                $data['return_ratio'] = $this->return_ratio($user_id);
                $data['total']        = bcadd($data['total'],$data['total'] * ($data['return_ratio'] / 100),2);
            }
        }
        $this->redis->setex($key, 60*60*24,json_encode($data));
        return $data;
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
    /**
     * 返水总比例
     * @param int user_id
     * @return array  返回最高比例返水
     */
    public function get_rebate_gid($user_id){
        $rebet = new \Logic\Lottery\UserBackWater($this->ci);
        $game  = \Model\GameMenu::getParentMenuIdType();
        $total = [];
        foreach ($game as $gid =>$value) {
            // 日返
            $res['day'] = $rebet->getUserRebetDetailByDailyAll($user_id, $gid);
            // 周返
            $res['week'] = $rebet->getUserRebetDetailByWeekOrMonthGid($user_id, $gid, 'week');
            // 月返
            $res['month'] = $rebet->getUserRebetDetailByWeekOrMonthGid($user_id, $gid, 'month');
            // 当前比例 包含直推任务提升后
            $total[$gid] = bcadd($res['day']['rate'] + $res['week']['rate'] + $res['month']['rate'], 0, 2);
        }
        return $total;
    }
};