<?php

use Logic\Admin\Log;
use Utils\Www\Action;

return new class extends Action{

    const TITLE = '首页代理-设置占成';
    const DESCRIPTION = '';

    const QUERY = [
        'id' => 'int #用户id'
    ];


    public function run($userId){
        $verify = $this->auth->verfiyToken();

        if (!$verify->allowNext()) {
            return $verify;
        }
        $self_uid = $this->auth->getUserId();
        $params = $this->request->getParams();
        if (empty($params)) {
            return $this->lang->set(10010);
        }
        $redisKey=\Logic\Define\CacheKey::$perfix['resetWeek'].$userId;

        $checkReset=$this->redis->get($redisKey);
        if(!empty($checkReset)){
            return $this->lang->set(191);
        }
        
        $checkParam=json_decode($params['profit_loss_value'],true);
        foreach($checkParam as $value){
            if($value <0){
                return $this->lang->set(10420);
            }
        }
        //判断是否有该用户
        $check = DB::table('user_agent')->where('uid_agent',$self_uid)->where('user_id','=',$userId)->first();
        if (!$check) {
            return $this->lang->set(10014);
        }

        if( $this->auth->getUserId() != $check->uid_agent){
            return $this->lang->set(192);
        }

        $agent=DB::table('user_agent')->where('user_id',$check->uid_agent)->first();
        if($check->uid_agent !=0 && empty($agent->profit_loss_value)){
            return $this->lang->set(10034);
        }
        if($check->uid_agent != 0){
            $profitValue=json_decode($params['profit_loss_value'],true);
            $agentValue=json_decode($agent->profit_loss_value,true);
            $agentModel=new \Logic\User\Agent($this->ci);
            $agentRes=$agentModel->compareProfit($agentValue,$profitValue);
            if($agentRes !== true){
                return $agentRes;
            }
        }
        $data=array(
            'profit_loss_value'=>$params['profit_loss_value']
        );

        $res=DB::table("user_agent")->where('user_id','=',$userId)->update($data);

        if(!$res){
            return $this->lang->set(-2);
        }

        $this->redis->setex($redisKey,7 * 24 * 3600,1);

        $str='原盈亏:'.$check->profit_loss_value.',修改占比:'.$data['profit_loss_value'];
        (new Log($this->ci))->create($userId, $check->uid_agent_name, Log::MODULE_USER, '首页代理-设置占成', '首页代理-设置占成', '修改', 1, $str);
        return $this->lang->set(0);
    }
};