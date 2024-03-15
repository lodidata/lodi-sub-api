<?php

use Utils\Www\Action;

return new class extends Action
{


    public function run()
    {
        $verify = $this->auth->verfiyToken();

        if (!$verify->allowNext()) {
            return $verify;
        }
        $uid=$this->auth->getUserId();

        $res=(array)DB::table("user_agent")
                      ->where("user_id",$uid)
                      ->get('default_profit_loss_value')[0];
        return $res;

    }


};
