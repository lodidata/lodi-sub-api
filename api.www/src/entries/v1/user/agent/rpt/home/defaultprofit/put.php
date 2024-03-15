<?php

use Logic\Admin\Log;
use Utils\Www\Action;

return new class extends Action{

    const TITLE = '首页代理-设置占成';
    const DESCRIPTION = '';

    const QUERY = [
        'id' => 'int #用户id'
    ];


    public function run(){
        $verify = $this->auth->verfiyToken();

        if (!$verify->allowNext()) {
            return $verify;
        }
        $params = $this->request->getParams();
        if (empty($params)) {
            return $this->lang->set(10010);
        }

        $checkParam=json_decode($params['default_profit_loss_value'],true);
        foreach($checkParam as $value){
            if($value <0){
                return $this->lang->set(10420);
            }
        }
        $userId=$this->auth->getUserId();
        //判断是否有该用户
        $check = DB::table('user_agent')->where('user_id','=',$userId)->first();
        if (!$check) {
            return $this->lang->set(10014);
        }

        if($check->uid_agent !=0 && empty($check->profit_loss_value)){
            return $this->lang->set(10034);
        }
        if($check->uid_agent != 0){
            $profitValue=json_decode($params['default_profit_loss_value'],true);
            $agentValue=json_decode($check->profit_loss_value,true);

            $agentModel=new \Logic\User\Agent($this->ci);
            $agentRes=$agentModel->compareProfit($agentValue,$profitValue);
            if($agentRes !== true){
                return $agentRes;
            }
        }


        $data=array(
            'default_profit_loss_value'=>$params['default_profit_loss_value']
        );

        $res=DB::table("user_agent")->where('user_id','=',$userId)->update($data);

        if(!$res){
            return $this->lang->set(-2);
        }

        $str='原盈亏:'.$check->default_profit_loss_value.',修改占比:'.$data['default_profit_loss_value'];
        (new Log($this->ci))->create($userId, $check->uid_agent_name, Log::MODULE_USER, '首页代理-设置默认占成', '首页代理-设置默认占成', '修改', 1, $str);
        return $this->lang->set(0);
    }
};