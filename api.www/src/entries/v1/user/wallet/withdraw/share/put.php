<?php

use Utils\Www\Action;
use Respect\Validation\Validator as V;
use Model\UserLog;
use Logic\Set\SystemConfig;

return new class extends Action {
    const TOKEN = true;
    const TITLE = "股东钱包提现-会员线上提款申请";
    const TAGS = "充值提现";
    const PARAMS = [
        "withdraw_money" => "int(required) #取款金额",
        "withdraw_card"  => "int(required) #银行卡id",
        "memo"           => "string() #备注",
    ];
    const SCHEMAS = [
    ];

    public function run() {
        $verify = $this->auth->verfiyToken();

        if (!$verify->allowNext()) {
            return $verify;
        }

        $validator = $this->validator->validate($this->request, [
            'withdraw_money' => V::Positive()
                                 ->noWhitespace()
                                 ->setName($this->lang->text("withdraw_money")),
            'withdraw_card'  => V::noWhitespace()
                                 ->setName($this->lang->text("withdraw_card")),
            'memo'           => V::noWhitespace()
                                 ->setName($this->lang->text("memo")),
        ]);

        if (!$validator->isValid()) {
            return $validator;
        }
        $userId     = $this->auth->getUserId();
        $userModel=new Logic\User\User($this->ci);
        $isVerify=$userModel->withdrawVerify($userId);
        if($isVerify){
            return $this->lang->set(2224);
        }

        $withdrawMoney = $this->request->getParam('withdraw_money');
        $withdrawCard = $this->request->getParam('withdraw_card');
        $memo = $this->request->getParam('memo');


        $website    = $this->ci->get('settings')['website'];

        //在用户从返佣钱包转到银行卡时，要判断是否在提现范围
        $config     = SystemConfig::getModuleSystemConfig('withdraw');
        $withdrawBkgeMoney = $config['withdraw_bkge_money'];
        if ($withdrawMoney > $withdrawBkgeMoney['withdraw_max'] || $withdrawMoney < $withdrawBkgeMoney['withdraw_min']) {
            return $this->lang->set(198, [$withdrawBkgeMoney['withdraw_min']/100, $withdrawBkgeMoney['withdraw_max']/100]);
        }

        $user       = \Model\User::where('id', $userId)
                           ->whereNotIn('tags', $website['notInTags'])
                           ->first();

        if (empty($user)) {
            return $this->lang->set(156);
        }

        $authStatus = explode(',', $user['auth_status']);
        if (in_array('refuse_withdraw', $authStatus)) {
            return $this->lang->set(163);
        }

        $recharge = new \Logic\Recharge\Recharge($this->ci);

        return $recharge->handApply($userId, $withdrawMoney, $withdrawCard, $memo,2);
    }
};