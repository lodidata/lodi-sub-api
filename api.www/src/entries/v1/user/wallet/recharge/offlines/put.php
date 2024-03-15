<?php
use Utils\Www\Action;
return new class extends Action {
    const TOKEN = true;
    const TITLE = "PUT 线下充值  (提交)";
    const TAGS = "充值提现";
    const PARAMS = [
       [
           "bank"               => "int(required)    #存款银行卡id",
           "deposit_name"       => "string(required) #存款人",
           "receipt_id"         => "int(required)    #收款银行卡id",
           "money"              => "int(required)    #存款金额 (分)",
           "deposit_time"       => "string(required) #存款时间  (2022-01-22 13:46:24)",
       ]
   ];
    const SCHEMAS = [
   ];


    public function run()
    {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $user['id'] = $this->auth->getUserId();
        $req        = $this->request->getParams();

        //未配置收款账号
        if(!isset($req['receipt_id']) || $req['receipt_id'] == 0){
            return  $this->lang->set(889);
        }

        $global     = \Logic\Set\SystemConfig::getModuleSystemConfig('recharge');

        if(isset($global['canNotDepositOnPending']) && $global['canNotDepositOnPending'] == false ) {
            $sql = "select count(1) as cnt from funds_deposit where user_id={$user['id']} and  status='pending' and pay_type !=0 and !FIND_IN_SET('online',state)";
            $depositData = DB::select($sql);

            if ($depositData && isset($depositData[0]) && isset($depositData[0]->cnt) && $depositData[0]->cnt > 0) {
              return  $this->lang->set(895);
            }
        }

        $recharge_repeat = $this->redis->get('recharge_repeat'.$user['id']);
        if($recharge_repeat && $recharge_repeat == json_encode($req)){
            return $this->lang->set(885);
        }else{
            $this->redis->setex('recharge_repeat'.$user['id'], 5, json_encode($req));
        }

        //判断通道限额
        $channel=DB::table('pay_channel')->where('type','localbank')->first(['min_money','max_money','money_day_stop','money_stop']);
        if($channel->min_money >0 && $req['money'] < $channel->min_money){
            return $this->lang->set(890,[$channel->min_money /100]);
        }
        if($channel->max_money >0 && $req['money'] >= $channel->max_money){
            return $this->lang->set(891,[$channel->max_money /100]);
        }
        $query=DB::table('funds_deposit')->where('status','=','paid')->whereRaw('FIND_IN_SET("offline",state)');
        $stopQuery= clone $query;
        $toDayMoney=$query->where('process_time','>=',date('Y-m-d 00:00:00',time()))->where('process_time','<=',date('Y-m-d 23:59:59',time()))->sum('money');

        if($channel->money_day_stop >0 && bcadd($toDayMoney,$req['money']) >= $channel->money_day_stop){
            return $this->lang->set(903,[$channel->money_day_stop/100]);
        }
        $stopMoney=$stopQuery->sum('money');
        if($channel->money_stop >0 && bcadd($stopMoney,$req['money']) >= $channel->money_stop){
            return $this->lang->set(904,[$channel->money_stop/100]);
        }
        $type = $req['type'] ?? 1 ;   // 1银行   3微信   2支付宝   5京东
        //银行
        if($type == 1) {
            $customer_bank = $req['bank'];  //  付款银行ID
            $customer_card = '';
        }else{
            $customer_bank = 0;  // 付款账号
            $customer_card = $req['bank'];
        }

        //如果是银行卡，判断是否为用户所属的卡
        $bankUserInfo = DB::table('bank_user')->where('id',$req['bank'])->first(['user_id']);
        if($bankUserInfo->user_id !== $user['id']) {
            return $this->lang->set(197);
        }

        //        $deposit_type = $req['deposit_type'] ;  //存款方式 银行转账才有该参数
        $deposit_type       = null ;  //三端统一去掉该参数
        $deposit_name       = $req['deposit_name'];  //付款人名
        $boss_bank          = $req['receipt_id'];   //收款ID
        $deposit_money      = $req['money'];  // 存款金额
        $deposit_time       = $req['deposit_time'] ?? date("Y-m-d H:i:s");
        //$discount_active    = empty($req['discount_active'])? 0 : $req['discount_active'];   // 活动ID

        $typeId = [2,3,7,11];
        //  APP充值赠送活动
        $platform = \Utils\Client::getHeader('HTTP_PL') ? current(\Utils\Client::getHeader('HTTP_PL')) : NULL;
        if ($platform) {
            if(in_array($platform, ['ios', 'android'])) {
                array_push($typeId, 18);
            }   
        }

        $discount_active    = DB::table('active')
            ->selectRaw('id')
            ->where('status', '=', 'enabled')
            ->whereIn('type_id', $typeId)
            ->where('begin_time', '<', date('Y-m-d H:i:s'))
            ->where('end_time', '>', date('Y-m-d H:i:s'))
            ->get()
            ->toArray();
        $discount_active = array_column($discount_active,'id');
        $discount_active = empty($discount_active)? 0 : implode(',',$discount_active);

        $config = \Logic\Set\SystemConfig::getModuleSystemConfig('lottery');
        if ($config['stop_deposit']) {
            return $this->lang->set(300);
        }

        if($req['bank'] && $deposit_name && $boss_bank && $deposit_money) {
            $ip       = Utils\Client::getIp();
            $recharge = new Logic\Recharge\Recharge($this->ci);
            try{
                $recharge->handDeposit(
                    $user['id'],
                    $deposit_money,
                    $deposit_name,
                    $deposit_time,
                    (int)$deposit_type,
                    (int)$customer_bank,
                    (int)$boss_bank,
                    $ip,
                    $discount_active,
                    $customer_card,
                    $type);
            }catch (\Exception $e){
                var_dump($e->getMessage());die;
            }

            return $this->lang->set(884);
        }else{
            return $this->lang->set(10);
        }
    }

    public function limitMoney($pass_id,$money){
        // 按分存储的
        $base = \Logic\Set\SystemConfig::getModuleSystemConfig('recharge')['recharge_money'];
        $pass_money = \DB::table('bank_account')->where('id',$pass_id)->first(['limit_once_min','limit_once_max']);
        if( $base['recharge_min'] == 0 ) {
            $min = $pass_money->limit_once_min;
        }else {
            $min = max($base['recharge_min'] , $pass_money->limit_once_min);
        }

        if( $base['recharge_max'] == 0 ) {
            $max = $pass_money->limit_once_max;
        }else {
            $max = min($base['recharge_max'] , $pass_money->limit_once_max);
        }
        if($max != 0) {
            if ($money >= $min && $money <= $max) {
                return true;
            }else {
                return false;
            }
        }else if ($money >= $min) {
            return true;
        }else {
            return false;
        }
    }
};
