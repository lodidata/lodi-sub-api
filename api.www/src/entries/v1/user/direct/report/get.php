<?php

use Utils\Www\Action;
use Logic\Set\SystemConfig;
use Logic\Define\CacheKey;

return new class() extends Action {
    const TITLE = '直推界面数据统计';
    const DESCRIPTION = '直推界面数据统计';
    const QUERY = [

    ];
    const SCHEMAS = [];

    public function run()
    {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $user_id = $this->auth->getUserId();
        $params = $this->request->getParams();
        $dt_type = $params['dt_type'] ?? 1;          //查询数据时间范围：1全部，2今天，3本周，4上周，5上月

        //按时间范围查询：注册人数、充值人数
        if ($dt_type == 2) {       //今天
            $today = date("Y-m-d");
            $regData = DB::connection('slave')->table('direct_record')->selectRaw('count(distinct user_id) as reg_num')->whereIn('type',[1,3])
                ->whereRaw('user_id !=? and sup_uid=? and date=?',[$user_id,$user_id,$today])->get()->toArray();
            $rechData = DB::connection('slave')->table('direct_record')->selectRaw('count(distinct user_id) as rech_num')
                ->whereRaw('user_id !=? and sup_uid=? and type=? and date=?',[$user_id,$user_id,2,$today])->get()->toArray();
        } elseif ($dt_type == 3) {    //本周一至今
            $monday = date('Y-m-d', strtotime('Monday this week'));
            $regData = DB::connection('slave')->table('direct_record')->selectRaw('count(distinct user_id) as reg_num')->whereIn('type',[1,3])
                ->whereRaw('user_id !=? and sup_uid=? and date>=?',[$user_id,$user_id,$monday])->get()->toArray();
            $rechData = DB::connection('slave')->table('direct_record')->selectRaw('count(distinct user_id) as rech_num')
                ->whereRaw('user_id !=? and sup_uid=? and type=? and date>=?',[$user_id,$user_id,2,$monday])->get()->toArray();
        } elseif ($dt_type == 4) {   //上周一至周末
            $pre_monday = date("Y-m-d", strtotime('-2 monday'));
            $pre_sunday = date("Y-m-d", strtotime('-1 sunday'));
            $regData = DB::connection('slave')->table('direct_record')->selectRaw('count(distinct user_id) as reg_num')->whereIn('type',[1,3])
                ->whereRaw('user_id !=? and sup_uid=? and date>=? and date<=?',[$user_id,$user_id,$pre_monday,$pre_sunday])->get()->toArray();
            $rechData = DB::connection('slave')->table('direct_record')->selectRaw('count(distinct user_id) as rech_num')
                ->whereRaw('user_id !=? and sup_uid=? and type=? and date>=? and date<=?',[$user_id,$user_id,2,$pre_monday,$pre_sunday])->get()->toArray();
        } elseif ($dt_type == 5) {    //上月初至月底
            $start_month = date("Y-m-01", strtotime('-1 month'));
            $end_month = date('Y-m-d',strtotime(date('Y-m-01').' -1 day'));
            $regData = DB::connection('slave')->table('direct_record')->selectRaw('count(distinct user_id) as reg_num')->whereIn('type',[1,3])
                ->whereRaw('user_id !=? and sup_uid=? and date>=? and date<=?',[$user_id,$user_id,$start_month,$end_month])->get()->toArray();
            $rechData = DB::connection('slave')->table('direct_record')->selectRaw('count(distinct user_id) as rech_num')
                ->whereRaw('user_id !=? and sup_uid=? and type=? and date>=? and date<=?',[$user_id,$user_id,2,$start_month,$end_month])->get()->toArray();
        }else {
            $regData = DB::connection('slave')->table('direct_record')->selectRaw('count(distinct user_id) as reg_num')->whereIn('type',[1,3])
                ->whereRaw('user_id !=? and sup_uid=?',[$user_id,$user_id])->get()->toArray();
            $rechData = DB::connection('slave')->table('direct_record')->selectRaw('count(distinct user_id) as rech_num')
                ->whereRaw('user_id !=? and sup_uid=? and type=?',[$user_id,$user_id,2])->get()->toArray();
        }

        //已获奖励
//        $awards = DB::connection('slave')->table('direct_record')->selectRaw('sum(price) as price')->whereRaw('user_id=?',[$user_id])->get()->toArray();
        $awards = (array)DB::connection('slave')->table('user_data')->selectRaw('direct_award')->whereRaw('user_id=?',[$user_id])->first();

        //返水规则
        $banners = DB::connection('slave')
                     ->table('direct_imgs')
                     ->where('type','bkge_rule')
                     ->where('status', 'enabled')
                     ->select('desc','img')
                     ->get()
                     ->toArray();

        $banner = [];
        foreach($banners as $val) {
            $banner['img'] = showImageUrl($val->img);
            $banner['desc'] = $val->desc;
        }

        $attr = [];
        if (SystemConfig::getModuleSystemConfig('direct')['direct_switch']){
            $attr['return_ratio'] = $this->return_ratio($user_id);
        }

        $res = [
            'reg_num' => $regData[0]->reg_num,     //注册人数
            'rech_num' => $rechData[0]->rech_num,   //充值人数
            'amount' => empty($awards['direct_award']) ? 0 : bcdiv($awards['direct_award'], 100, 2),    //已获得奖励
            'rule' => (object)$banner
        ];

        return $this->lang->set(0, [], $res, $attr);
    }

    /**
     * 当前返水比例
     * @param int register_count 注册人数
     * @param int recharge_count 充值人数
     * @return int  返回当前返水比例
     */
    public function return_ratio($user_id){
        $key        = CacheKey::$perfix['direct_current_return_ratio'].$user_id;
        $redisData  = $this->redis->get($key);
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
