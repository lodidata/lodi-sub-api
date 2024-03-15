<?php

use Model\FundsDealLog;
use Utils\Www\Action;
use Respect\Validation\Validator as V;

return new class extends Action{
    const TOKEN = true;
    const TITLE = "股东钱包转入主钱包";
    const TAGS = "转入";
    const PARAMS = [
        "money" => "string(required) #金额",
    ];
    const SCHEMAS = [
    ];

    public function run(){
        $verify = $this->auth->verfiyToken();

        if (!$verify->allowNext()) {
            return $verify;
        }

        $validator = $this->validator->validate($this->request, [
            'money' => V::Positive()
                                 ->noWhitespace()
                                 ->setName($this->lang->text("money")),
        ]);

        if (!$validator->isValid()) {
            return $validator;
        }

        $money=$this->request->getParam('money');
        if($money <= 0){
            return $this->lang->set(193);
        }

        $userId=$this->auth->getUserId();
        $user = \Model\User::where('id', $userId)
                           ->first()
                           ->toArray();


        try {
            $this->db->getConnection()->beginTransaction();
            $oldFunds = \Model\Funds::where('id', $user['wallet_id'])
                                    ->lockForUpdate()
                                    ->first();
            if($oldFunds['share_balance'] < $money){
                return $this->lang->set(161);
            }
            $wallet = new \Logic\Wallet\Wallet($this->ci);

            $share=$wallet->crease($user['wallet_id'],-$money,2);
            $res=$wallet->crease($user['wallet_id'],$money);

            if(!$share || !$res){
                $this->db->getConnection()->rollback();
            }

            $funds = \Model\Funds::where('id', $user['wallet_id'])->first();

            //流水里面添加打码量可提余额等信息
            $dml = new \Logic\Wallet\Dml($this->ci);
            $dmlData = $dml->getUserDmlData((int)$userId);
            // 写入流水
            \Model\FundsDealLog::create(
                [
                    "user_id"           => $userId,
                    "username"          => $user['name'],
                    "deal_type"         => \Model\FundsDealLog::TYPE_CTOM_SHARE,
                    "deal_category"     => \Model\FundsDealLog::CATEGORY_INCOME,
                    "order_number"      => FundsDealLog::generateDealNumber(),
                    "deal_money"        => $money,
                    "balance"           => intval($funds['balance']),
                    "memo"              => $this->lang->text('股东转入主钱包'),
                    "wallet_type"       => \Model\FundsDealLog::WALLET_TYPE_PRIMARY,
                    'total_bet'         => $dmlData->total_bet,
                    'withdraw_bet'      => 0,
                    'total_require_bet' => $dmlData->total_require_bet,
                    'free_money'        => $dmlData->free_money,
                ]
            );
            $this->db->getConnection()->commit();

            $exchange = 'user_message_send';
            \Utils\MQServer::send($exchange,[
                'user_id'   => $userId,
                'user_name' => $user['name'],
                'title'     => $this->lang->text('Transfer to main Wallet title'),
                'content'   => $this->lang->text('Transfer to main Wallet content',[bcdiv($money,100,2)]),
            ]);

        }catch(\Exception $e){
            $this->db->getConnection()->rollback();
            $this->logger->error("股东转入主钱包异常:" .$e->getMessage());
            return $this->lang->set(-1);
        }
        return $this->lang->set(0);
    }
};