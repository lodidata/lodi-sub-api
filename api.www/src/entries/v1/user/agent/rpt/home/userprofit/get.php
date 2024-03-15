<?php

use Utils\Www\Action;

return new class extends Action
{


    public function run($id=null)
    {
        $verify = $this->auth->verfiyToken();

        if (!$verify->allowNext()) {
            return $verify;
        }

        $uid = $this->auth->getUserId();

        $res =(array)DB::table("user_agent")
            ->select('profit_loss_value')
            ->where('uid_agent',$uid)
            ->where("user_id",$id)
            ->first();

        if(empty($res)){
            return $this->lang->set(-2);
        }

        return $res;

    }


};
