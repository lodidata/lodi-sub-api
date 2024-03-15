<?php
use Utils\Www\Action;
/**
 * 返回包括可参加活动、支持的银行列表、最低、最高充值额度、提示等信息
 */
return new class extends Action {
    const HIDDEN = true;
    const TITLE = "获取用户充值信息";
    const TAGS = "充值信息";
    const SCHEMAS = [];

    public function run()
    {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $userId = $this->auth->getUserId();

        //一个用户只上报一次
        $redis_key = "upload_deposit:{$userId}";
        if($this->redis->get($redis_key)){
            return [];
        }

        //获取用户最近2分钟充值成功的信息
        $time = date('Y-m-d H:i:s', (time() - 2*60));
        $info = \DB::table('funds_deposit')
            ->select('user_id','trade_no','money')
            ->where('user_id', $userId)
            ->where('status','paid')
            ->where('created','>=',$time)
            ->orderBy('id','desc')
            ->first();

        $data = [];
        if(!empty($info)){

            //把这个用户记录到缓存里 缓存时间6个月
            $this->redis->setex($redis_key,15552000,1);

            $data = (array)$info;
            //获取是否首充
            $count = \DB::table('funds_deposit')->where('user_id', $userId)->where('status','paid')->count();
            if($count > 1){
                $data['is_first'] = 0;
            }else{
                $data['is_first'] = 1;
            }
        }

        return $data;
    }
};
