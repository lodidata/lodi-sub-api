<?php
use Utils\Www\Action;
use Respect\Validation\Validator as V;

return new class extends Action
{
    const TOKEN = true;
    const TITLE = "删除绑定的银行卡";
    const TAGS = "银行卡";
    const PARAMS = [
        "id"            => "int(required) #银行卡id",
        "withdraw_pwd"  => "int(required) #取款密码（1：有，0：没有）",
    ];
    const SCHEMAS = [
    ];



    public function run($id = 0) {
        $id = $id == 0 ? $this->request->getParam('id') : $id;
        $validator = $this->validator->validate(['id' => $id], [
            'id' => V::intVal()->noWhitespace()->setName('id'),
        ]);
        /*$validator = $this->validator->validate(['id' => $id, 'withdraw_pwd' => $this->request->getParam('withdraw_pwd')], [
            'id' => V::intVal()->noWhitespace()->setName('id'),
            'withdraw_pwd' => V::intVal()->noWhitespace()->length(4)->setName($this->lang->text("Withdrawal password")),
        ]);*/

        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        if (!$validator->isValid()) {
            return $validator;
        }

        //$withdrawPwd = $this->request->getParam('withdraw_pwd');
        $userId = $this->auth->getUserId();
        //$user = \Model\User::where('id', $userId)->first();
        //$funds = \Model\Funds::where('id', $user['wallet_id'])->first();
        /*if (\Model\User::getPasword($withdrawPwd, $funds['salt']) != $funds['password']) {
            return $this->lang->set(154);
        }*/

        \Model\BankUser::where('id', $id)->where('user_id', $userId)->update(['state' => 'delete']);
        return $this->lang->set(0);
    }
};
