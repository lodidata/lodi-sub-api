<?php
use Utils\Www\Action;
use Model\FundsDealLog;
return new class extends Action {
    const TOKEN = true;
    const TITLE = "生成游戏打码量";
    const TAGS = "打码量";


    public function run() {

        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $status = DB::table('system_config')->where('module','xima')->where('key','switch')->value('value');
        if(!$status)
            return $this->lang->set(2400);
        $userId = $this->auth->getUserId();
        $user = \Model\User::where('id', $userId)->first();
//        $level = DB::table('user')->where('id',$userId)->value('ranting');
        $xima = DB::table('xima_config as xc')
                ->leftJoin('game_menu as gm','gm.id','=','xc.game_type_id')
                ->where('xc.level',$user['ranting'])
                ->selectRaw('xc.game_type_id,gm.name,gm.type,percent')
                ->get([])->toArray();
        try{

//            DB::beginTransaction();
            $user_dml = DB::table('user_dml')->where('user_id',$userId)->lockForUpdate()->first();

            $total_dml = 0;
            $amount = 0;
            $transferType = [];
            $orderDetails = [];
            foreach ($xima as &$value){
                if(isset($user_dml->{$value->type}) && $user_dml->{$value->type} > 0){
                    $value->dml = $user_dml->{$value->type};
                    $value->value = ($value->dml * $value->percent)/10000;
                    $total_dml += $value->dml;
                    $amount += $value->value;
                    $transferType[$value->type] = 0;
                    array_push($orderDetails,['game_type_id'=>$value->game_type_id,'dml'=>$value->dml,'tmp_percent'=>$value->percent,'amount'=>$value->value]);
                }
            }
            $amount = ceil($amount);
            if($amount < 100 ){
                DB::rollback();
                return $this->lang->set(2401);
            }
            $ximaAmountDmlPercent = DB::table('system_config')->where('module','xima')->where('key','percent')->value('value');
            $dml = ($amount * $ximaAmountDmlPercent)/10000 ;
            $oldFunds = DB::table('funds')->where('id', $user['wallet_id'])->lockForUpdate()->first();
            $oldFunds = (array)$oldFunds;
            DB::table('funds')->where('id',$user['wallet_id'])
                ->update([
                    'balance_before' => $oldFunds['balance'],
                    'balance' => DB::raw("{$oldFunds['balance']} + $amount"),
                ]);
            DB::table('dml')->insert(['user_id'=>$userId,'money'=>$amount,'withdraw_bet'=>$dml,'memo'=>$this->lang->text("Amount of code washing activity added")]);
            DB::table('user_data')->increment('total_require_bet',$dml);
            $transferType['last_settle'] = date('Y-m-d H:i:s');
            DB::table('user_dml')->where('user_id',$userId)->update($transferType);
            $order_number = $this->create_order_no();
            $ximaOrderId = DB::table('xima_order')->insertGetId(['user_id'=>$userId,'user_name'=>$user['name'],'dml_total'=>$total_dml,'amount_total'=>$amount,'order_number'=>$order_number]);

            foreach ($orderDetails as $orderDetail){
                $orderDetail['order_id'] = $ximaOrderId;
                DB::table('xima_order_detail')->insert($orderDetail);
            }

            $dmlData = (new \Logic\Wallet\Dml($this->ci))->getUserDmlData($userId);

            $dealData = [
                'user_id' => $userId,
                'user_type' => 1,
                'username' => $user['name'],
                'order_number' => $order_number,
                'deal_type' => FundsDealLog::TYPE_TRANSFER_XIMA,
                'deal_category' => FundsDealLog::CATEGORY_INCOME,
                'deal_money' => $amount,
                'balance' => $oldFunds['balance'] + $amount,
                'total_bet' => $dmlData->total_bet,
                'withdraw_bet' => $dml,
                'total_require_bet' => $dmlData->total_require_bet + $dml,
                'free_money' => $dmlData->free_money,
                'memo' => $this->lang->text("Xima"),
                'wallet_type' => 1,
            ];
            FundsDealLog::create($dealData);

            DB::commit();
            return $this->lang->set(0, [], $amount);
        }catch (\Exception $e){
            DB::rollback();
            throw $e;
        }

        return $this->lang->set(0);

    }

    public function create_order_no() {
        $order_no = date('YmdHis') . substr(microtime(), 2, 5);
        return 'XM'.$order_no;
    }
};