<?php
/**
 * Created by PhpStorm.
 * User: ben
 * Date: 2019/3/13
 * Time: 18:47
 */
use Utils\Www\Action;
use Model\FundsDealLog;
use Respect\Validation\Validator as V;
return new class extends Action {
    const TOKEN = true;
    const TITLE = "主钱包转入保险箱";
    const TAGS = "钱包";
    const PARAMS = [
        "amount" => "int(required) #账户金额"
    ];
    const SCHEMAS = [
    ];

    public function run() {

        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $validator = $this->validator->validate($this->request, [
            'amount' => V::intVal()->noWhitespace()->length(1,11)->setName($this->lang->text('amount of money')),
        ]);

        if (!$validator->isValid()) {
            return $validator;
        }

        $amount = (int)$this->request->getParam('amount');
        $userId = $this->auth->getUserId();
        $user = \Model\User::where('id', $userId)->first();
        if($amount <= 0) {
            return $this->lang->set(0);
        }
        //  回收所有钱包以供存包险箱
        $tmp_game = \Model\FundsChild::where('pid',$user['wallet_id'])->where('balance','>',0)->pluck('game_type')->toArray();
        foreach ($tmp_game as $val) {
            $gameClass = \Logic\GameApi\GameApi::getApi($val, $userId);
            $gameClass->rollOutThird();
        }
        try{
            DB::beginTransaction();

            $oldFunds = DB::table('funds')->where('id', $user['wallet_id'])->lockForUpdate()->first();
            $oldFunds = (array)$oldFunds;
            if($oldFunds['balance'] < $amount){
                DB::rollback();
                return $this->lang->set(2302);
            }
            DB::table('funds')->where('id',$user['wallet_id'])
                ->update([
                    'balance_before' => $oldFunds['balance'],
                    'balance' => DB::raw("{$oldFunds['balance']} - $amount"),
                    'freeze_money' => DB::raw("{$oldFunds['freeze_money']} + $amount")
            ]);


            $dealData = ([
                'user_id' => $userId,
                'user_type' => 1,
                'username' => $user['name'],
                'deal_type' => FundsDealLog::TYPE_MFOC_MANUAL,
                'deal_category' => FundsDealLog::CATEGORY_TRANS,
                'deal_money' => $amount,
                'balance' => $oldFunds['balance'] - $amount,
                'memo' => $this->lang->text('Main wallet to safe'),
                'wallet_type' => 1,
            ]);
            FundsDealLog::create($dealData);

            DB::commit();
            return $this->lang->set(0);
        }catch (\Exception $e){
            DB::rollback();
            return $this->lang->set(-2);
        }

    }
};
