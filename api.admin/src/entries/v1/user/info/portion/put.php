<?php


use \Logic\Admin\BaseController;
use Logic\Admin\Log;

return new class() extends BaseController{


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
        //判断是否有该用户
        $check = DB::table('user_agent')->where('user_id','=',$userId)->first();
        if (!$check) {
            return $this->lang->set(10014);
        }
        $data=array(
            'proportion_type'=>$params['proportion_type']
        );

        if(isset($params['proportion_value'])){
            $data['proportion_value']=$params['proportion_value'];
        }
        $res=DB::table("user_agent")->where('user_id','=',$userId)->update($data);

        if(!$res){
            return $this->lang->set(-2);
        }
        $str="原占比类型:".$check->proportion_type.",修改占比类型:".$data['proportion_type'].',原占比:'.$check->proportion_value.',修改占比:'.$data['proportion_value'];
        (new Log($this->ci))->create($userId, $check->uid_agent_name, Log::MODULE_USER, '代理占成', '代理占成', '修改', 1, $str);
        return $this->lang->set(0);
    }
};