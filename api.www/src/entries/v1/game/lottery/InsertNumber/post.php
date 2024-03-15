<?php

use Utils\Www\Action;
use Model\User;
use Logic\Lottery\Order;

return new class extends Action
{
    const TOKEN = true;
    const TITLE = '输入彩票猜奖号';
    const DESCRIPTION = '输入彩票猜奖号';
    const TAGS = "彩票";
    const QUERY = [
        'lottery_id'     => 'int(required) #彩票id',
        "lottery_number" => "string(required) #彩票期号",
        'number'         => 'string(required) #猜奖号',
    ];
    const SCHEMAS = [];


    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $lottery_id = $this->request->getParam('lottery_id');
        $number = $this->request->getParam('number');
        $lottery_number = $this->request->getParam('lottery_number');
        if(!is_numeric($lottery_id) || empty($lottery_number) || empty($number)){
            return $this->lang->set(10);
        }

        $numbers = str_split($number);
        if(count($numbers) != 5){
            return $this->lang->set(10);
        }
        foreach ($numbers as $val){
            if(!is_numeric($val)){
                return $this->lang->set(10);
            }
        }

        $user_id = $this->auth->getUserId();
        $user_account = User::where('id', $user_id)->value('name');
        //加入redis
        return (new Order($this->ci))->addLotteryNumber($user_id, $user_account, $lottery_id, $lottery_number, $number);
    }
};
