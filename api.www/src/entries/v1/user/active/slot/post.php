<?php

use Model\FundsDeposit;
use Utils\Www\Action;
use Model\Active;
use Logic\Activity\Activity;

return new class extends Action {
    const TOKEN = true;
    const TITLE = '首次充值送300%';
    const TAGS =  "首次充值送300%";
    const QUERY = [
        'id' => 'int(required) #活动ID'
    ];
    const SCHEMAS     = [];

    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $userId   = $this->auth->getUserId();

        try{
            //参与活动
            Activity::joinSlotActive($userId);
        }catch (\Exception $e){
            return $this->lang->set(886, [$this->lang->text($e->getMessage())]);
        }

        return $this->lang->set(0);
    }
};