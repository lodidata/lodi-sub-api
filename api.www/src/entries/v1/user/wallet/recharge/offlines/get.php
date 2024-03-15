<?php
use Utils\Www\Action;

return new class extends Action {
    const TOKEN = true;
    const TITLE = "GET 获取线下充值类型";
    const TAGS = "充值提现";
    const SCHEMAS = [
           'money'=>[
               "min_money" => "int #金额最小值",  //分
               "max_money" => "int #金额最大值"  //分
           ],
           'type'=>[
               "id" => "int #ID",
               "d_title" => "string #类型描述",
               "name" => "string #名称",
               "imgs" => "string #图片地址",
               "min_money" => "int #该类型金额支持最小值",  //分
               "max_money" => "int #该类型金额支持最大值"  //分
           ]
   ];

    public function run()
    {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $user = (new \Logic\User\User($this->ci))->getInfo($this->auth->getUserId());;
        $userLevel = $user['ranting'];//用户层级
        $pay = new \Logic\Recharge\Pay($this->ci);
        $recharge_offlines = $pay->getPayChannel($userLevel);
        return $recharge_offlines;
    }
};
