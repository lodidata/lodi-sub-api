<?php

use Utils\Www\Action;
use Logic\Lottery\Order;
use Model\User as UserModel;

return new class extends Action
{
    const HIDDEN = true;
    const TITLE = "POST 会员注单";
    const DESCRIPTION = "会员注单";
    const TAGS = "彩票";
    const PARAMS = [
            "lottery_id"     => "int() #彩票id",
            "lottery_number" => "string() #彩票期号",
            "number" => "string() #插入的5位数字",
    ];
    const SCHEMAS = [
    ];


    protected $state = 13;


    public function run() {
        $verify = $this->auth->verfiyToken($this->ci);

        if (!$verify->allowNext()) {
            return $verify;
        }
        $lottery_id     = $this->request->getParam('lottery_id');
        $lottery_number = $this->request->getParam('lottery_number');
        $number         = $this->request->getParam('number');

        $userId       = $this->auth->getUserId();
        $user_account = UserModel::getAccount($userId);

        return (new Order($this->ci))->addLotteryNumber($userId, $user_account, $lottery_id, $lottery_number, $number);

    }
};