<?php
use Utils\Www\Action;
use Respect\Validation\Validator as V;
return new class extends Action
{
    const TOKEN = true;
    const TITLE = "取消充值";
    const TAGS = "充值提现";
    const QUERY = [
        "id" => "int(required) #充值ID号"
   ];
    const SCHEMAS = [
   ];


    public function run($id) {

        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $userId = $this->auth->getUserId();
        $fundsDeposit['status'] = 'canceled';
        $fundsDeposit['process_uid'] = $userId;
        $fundsDeposit['memo'] = $this->lang->text("User cancels recharge");
        $re = \Model\FundsDeposit::where('id', $id)->where('user_id', $userId)->where('status','pending')->update($fundsDeposit);
        if($re)
            return $this->lang->set(0);
        return $this->lang->set(-2);
    }
};