<?php
use Utils\Www\Action;

return new class extends Action {
    const TOKEN = true;
    const TITLE = "用户基本现金账户信息";
    const TAGS = "个人中心";
    const QUERY = [
    ];
    const SCHEMAS = [
       "id"     => "int(required) #id",
       "uuid"   => "string(required) #对外id",
       "name" => "string() #钱包名称",
       "true_name" => "string() #真实姓名",
       "user_name" => "string() #账号名",
       "transfer" => "int() #额度转换(1:开启,0:关闭)",
       "balance" => "int(required) #主钱包余额",
       "all_balance" => "int(required) #总余额",
       "balance_before" => "int(required) #上次余额",
       "freeze_withdraw" => "int(required) #提款冻结金额",
       "freeze_append" => "int(required) #彩票追号冻结",
       "currency" => "int(required) #钱包币种",
       "updated" => "int() #最近登录时间",
       "comment" => "string() #对此账户的描述",
       "children" => [
           [
               "id" => "int(required) #id",
               "uuid" => "string(required) #对外id",
               "name" => "string() #账户名称（此账户在系统中的名称）",
               "game_type" => "BBIN",
               "balance" => "0",
               "last_updated" => "int(required) #上次登录时间"
           ]
       ]
    ];
    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $wallet = new \Logic\Wallet\Wallet($this->ci);
        if($this->auth->getTrialStatus()){//试玩用户
            return $this->lang->set(139,[],$wallet->getTrialWalletInfo($this->auth->getUserId()));
        }else{
            return $this->lang->set(139,[],$wallet->getWalletInfo($this->auth->getUserId()));
        }
    }
};