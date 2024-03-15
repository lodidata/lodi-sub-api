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
        $agent=new \Logic\User\Agent($this->ci);
        return $agent->rangProfit($uid);

    }


};
