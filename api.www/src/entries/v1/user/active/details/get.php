<?php

use Logic\Set\SystemConfig;
use Utils\Www\Action;

return new class extends Action {
    const TOKEN   = true;
    const TITLE   = "直推奖励--活动页面反水展示及计算";
    const TAGS    = "直推奖励详情";
    const QUERY   = [];
    const TYPE   = [
        'bkge_type'=>'bkge_rule' //返水规则类型
    ];
    const SCHEMAS = [
        [

        ]
    ];

    public function run() {
        $verify     = $this->auth->verfiyToken();
        if(!$verify->allowNext()) {
            return $verify;
        }
        $user_id    = $this->auth->getUserId();
        $key        = \Logic\Define\CacheKey::$perfix['direct_return_ratio'].$user_id;
        $redisData  =  $this->redis->get($key);
        if(!empty($redisData)){
            return json_decode($redisData,true);
        }
        $data['advanced_condition']  = $this->advanced($user_id);
        $data['direct_list']         = $this->condition();
        $this->redis->setex($key,3,json_encode($data));

        return $data;
    }
    /**
     * 进阶条件
     * @param int $user_id  上级代理用户id
     * @return array  当前所需进阶条件
     */
    public function advanced($user_id){
        //获取推广记录
        $direct_bkge   = \DB::table('user_data')->where('user_id','=',$user_id)->first(['direct_deposit','direct_register']);

        $register_count = $direct_bkge->direct_register;
        $recharge_count = $direct_bkge->direct_deposit;
        //获取未满足条件预设值
        $role      = DB::table('direct_bkge')
            ->orwhere('register_count','>',$register_count)
            ->orwhere('recharge_count','>',$recharge_count)
            ->orderBy('serial_no')
            ->first();

        //先去缓存获取返水比例
        $data['return_ratio']       = $this->redis->get(\Logic\Define\CacheKey::$perfix['direct_current_return_ratio'].$user_id);

        if (!$data['return_ratio']) {
            $data['return_ratio']   = $this->return_ratio($register_count,$recharge_count) ;
        }

        //下阶没有显示当前最高
        $data['increase']                 = $data['return_ratio'];
        $data['register_count']           = 0;
        $data['recharge_count']           = 0;
        if ($role) {
            //计算是否满足条件 （人数=预设值-当前值）
            $data['register_count']    = $role->register_count - $register_count;
            $data['register_count']    = $data['register_count'] <= 0 ? '已达标' : $data['register_count'];
            $data['recharge_count']    = $role->recharge_count - $recharge_count;
            $data['recharge_count']    = $data['recharge_count'] <= 0 ? '已达标' : $data['recharge_count'];
            $data['increase']          = $role->bkge_increase;
        };

        return $data;
    }
    /**
     * 返回规则
     * @return array  所有规则列表
     */
    public function condition(){
        $direct_list = DB::table('direct_imgs')
                         ->where('type',self::TYPE['bkge_type'])
                         ->where('status', 'enabled')
                         ->get()
                         ->toArray();
        foreach ($direct_list as $item){
            $item->img = showImageUrl($item->img);
        }
        return $direct_list;
    }
    /**
     * 当前返水比例
     * @param int register_count 注册人数
     * @param int recharge_count 充值人数
     * @return int  返回当前返水比例
     */
    public function return_ratio($register_count,$recharge_count){

        $current    = DB::table('direct_bkge')
            ->where('register_count','<=',$register_count)
            ->where('recharge_count','<=',$recharge_count)
            ->orderByDesc('bkge_increase')
            ->first('bkge_increase');
        return $current->bkge_increase ?? 0;
    }

};