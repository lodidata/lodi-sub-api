<?php


use \Logic\Admin\BaseController;
use Logic\Admin\Log;

return new class() extends BaseController{

    const TITLE = '用户盈亏占成设置';
    const DESCRIPTION = '';

    const QUERY = [
        'id' => 'int #用户id'
    ];

    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run($userId){
       $this->checkID($userId);

        $params = $this->request->getParams();
        if (empty($params)) {
            return $this->lang->set(10010);
        }
        $checkParam=json_decode($params['profit_loss_value'],true);
        foreach($checkParam as $value){
            if($value <0){
                return $this->lang->set(10420);
            }
        }
        //判断是否有该用户
        $check = DB::table('user_agent')->where('user_id','=',$userId)->first();
        if (!$check) {
            return $this->lang->set(10014);
        }
        $agent=DB::table('user_agent')->where('user_id',$check->uid_agent)->first();
        if($check->uid_agent !=0 && empty($agent->profit_loss_value)){
            return $this->lang->set(10034);
        }
        if($check->uid_agent != 0){
            $profitValue=json_decode($params['profit_loss_value'],true);
            $agentValue=json_decode($agent->profit_loss_value,true);
            foreach($agentValue as $key=>$value){
                if(isset($profitValue[$key]) && $profitValue[$key] >0){
                    if(isset($profitValue[$key])  && ( bcsub($value,$profitValue[$key]  ,2) < 1)){
                        return $this->lang->set(10037,[$this->lang->text($key)]);
                    }
                }

            }
        }
        $data=array(
            'profit_loss_value'=>$params['profit_loss_value']
        );

        $res=DB::table("user_agent")->where('user_id','=',$userId)->update($data);

        if(!$res){
            return $this->lang->set(-2);
        }
        $str='原盈亏:'.$check->profit_loss_value.',修改占比:'.$data['profit_loss_value'];
        (new Log($this->ci))->create($userId, $check->uid_agent_name, Log::MODULE_USER, '代理盈亏', '代理盈亏', '修改', 1, $str);
        return $this->lang->set(0);
    }
};