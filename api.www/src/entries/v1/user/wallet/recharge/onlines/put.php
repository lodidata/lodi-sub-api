<?php
use Utils\Www\Action;
return new class extends Action {
    const TOKEN = true;
    const TITLE = "线上充值 (提交)";
    const TAGS = "充值提现";
    const PARAMS = [
           "receipt_id" => "int(required) #支付通道ID",
           "money" => "string(required) #充值金额",
           "discount_active" => "string(required) #优惠活动id",
           "bank_data" => "string() #非必传参数， 某些第三方银行必须要银行则必传参数 ，取bank_data中的pay_code值"
   ];
    const SCHEMAS = [
           "url" => "string(required) #支付URL",
           "showType" => "string(required) #方式（code:二维码，url,jump跳转--jump是由于APP内部浏览器打不开原因所加）",
           "money" => "string(required) #支付金额"
   ];
//{"money":3000,"discount_active":"0","receipt_id":10298,"pay_type":"2","type":2}

    public function run()
    {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $user['id']      = $this->auth->getUserId();
        $req             = $this->request->getParams();
        $receipt_id      = $req['receipt_id'] ?? '';        //third_id
        $deposit_money   = floatval($req['money']) ?? 0;
        $pay_code        = $req['bank_data'] ?? '';
        $pay_type        = $req['pay_type'] ?? '';
        $coin_type       = $req['coin_type'] ?? '';
        $coin_amount     = $req['coin_amount'] ?? '';
        $config          = \Logic\Set\SystemConfig::getModuleSystemConfig('lottery');

        $typeId = [2,3,7,11];
        $channel = DB::table('pay_channel')->where('type', 'qr')->first('id');
        if (!empty($channel)) {
            $ids = DB::table('payment_channel')->where('pay_channel_id', $channel->id)->pluck('id')->toArray();
            // 如果充值渠道不在指定渠道内则跳过
            if(in_array($receipt_id, $ids)){
                $typeId = [2,3,7,11,17];
            }
        }

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
        
        if ($config['stop_deposit']) {
            return $this->lang->set(300);
        }

        if ($receipt_id && $deposit_money && $user['id']) {  // 线上充值
            //kpay验证Gcash
            $payment_name = DB::table('payment_channel')->where('id',$receipt_id)->value('name');
            if ($payment_name == 'KPAY') {
                $bank_id = DB::table('bank')->where(['code'=>'Gcash','status'=>'enabled'])->value('id');
                $has_user_gcash = DB::table('bank_user')
                    ->where(['user_id' => $user['id'], 'bank_id' => $bank_id,'state' => 'enabled', 'role' => 1])
                    ->count();
                if ($has_user_gcash == 0) {
                    return $this->lang->set(886,['Please add Gcash account']);
                }
            }

            $deposit_money = bcmul($deposit_money,100, 0); //充值金额分
            $ip            = \Utils\Client::getIp();
            $result        = (new Logic\Recharge\Recharge($this->ci))->onlinePayWebSite($deposit_money, $user['id'], $ip, $discount_active, $receipt_id, $pay_code, $pay_type,$coin_type,$coin_amount);

            if($result['code'] != 0){
                $res = json_encode($result);
                $this->logger->error("支付test：receipt_id:{$receipt_id},result:{$res}");
                return $this->lang->set(886,[$result['msg']]);
            }

            $is_html   = isset($result['is_html']) ? $result['is_html'] : 0;
            $html_data = isset($result['html_data']) ? $result['html_data'] : [];
            return ['url' => $result['str'], 'showType' => $result['way'],'money'=>$result['money'],'is_html'=>$is_html,'html_data'=>$html_data];//pay_info
        }else{
           return $this->lang->set(13);
       }

    }
};
