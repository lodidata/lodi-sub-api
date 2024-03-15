<?php
use Utils\Www\Action;

return new class extends Action
{
    const HIDDEN = true;
    const TITLE = "停止追号";
    const TAGS = "彩票";
    const SCHEMAS = [

   ];


    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $chaseNumber = $this->request->getParam('chase_number', 0);
        if (empty($chaseNumber)) {
            return $this->lang->set(88);
        }
        
        $chase = new \Logic\Lottery\ChaseOrder($this->ci);
        if($this->auth->getTrialStatus()){
            $chase->trialCancel($chaseNumber, $this->auth->getUserId());
        }else {
            $chase->cancel($chaseNumber, $this->auth->getUserId());
        }
        return $this->lang->set(139);
    }

};