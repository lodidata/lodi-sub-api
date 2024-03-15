<?php
namespace Logic\Recharge\Traits;
use Logic\Admin\Message;
use Utils\Utils;

trait RechargePay{
    /**
     * 获取支付状态 （补单时）
     * @param  $orderNumber 订单号
     * @param  $payNo       第三方订单号
     * @param  $payType     支付接口名
     */
    public function getPayStatus($orderNumber, $deposit){
        $pay_no     = $deposit->pay_no;
        $pay_type   = json_decode($deposit->receive_bank_info, true)['vender'];

        if($this->existThirdClass($pay_type)){
            $obj    = $this->getThirdClass($pay_type);
            $result = $obj->supplyOrder($orderNumber, $pay_no);
            //支付金额比本地记录小
            if($result['third_money'] < $deposit->money){
                throw new \Exception('金额不一致！实际支付金额:'.bcdiv($result['third_money'], 100, 2));
            }
            if($orderNumber != $result['order_number']){
                throw new \Exception('单号不一致，对方记录单号:'.$result['order_number']);
            }
            return;

        }
        throw new \Exception($pay_type.'类不存在');
    }

    /**
     * 回调修改入款单状态并且加入钱包
     *
     * @param string $ordernumber 入款单号
     */
    public function onlineCallBack($deposit, $adminId=0, $memo='')
    {
        if ($deposit == null || $deposit->status != 'pending') {
            return false;
        }

        $model     = [
            'valid_bet'     => 0,
            'process_time'  => date('Y-m-d H:i:s'),
            'recharge_time' => date('Y-m-d H:i:s'),
            'status'        => 'paid',
            'memo'          => $memo??$this->lang->text("Online recharge"),
            'process_uid'   => $adminId
        ];

        // 将充值金额转入钱包
//        $amount = $deposit->money + $deposit->coupon_money;// 充值金额=本金+优惠
        $user  = (new \Logic\User\User($this->ci))->getInfo($deposit->user_id);

        $resData = $this->rechargeMoney($model, $deposit, $user, $deposit->money);
        return $resData;

    }

    public function onlinePaySuccessMsg($order,$pay_channel, $pay_way= ""){
        $message = new Message($this->ci);
        $content  = ["Dear %s, Hello! You have successfully charged %s yuan", $order['name'], $order['money']];
        $insertId = $this->messageAddByMan("Recharge to account",$order['name'],$content);
        $message->messagePublish($insertId);

        if($order['coupon'] > 0) {
            $content  = ["Dear %s, Hello! %s yuan has arrived", $order['name'], $order['coupon']];
            $insertId = $this->messageAddByMan("Recharge gift", $order['name'], $content);
            $message->messagePublish($insertId);
        }
        if(empty($pay_way)){
            $pay_way = $this->lang->text("No payment method returned");
        }
        $this->noticeInfo($pay_channel,$pay_way,$order['order_no'],$order['user_id'],$order['trade_no'],$order['money']*100,$order['trade_time']);
    }

    public function messageAddByMan($title,$user,$content,$userId=0,$active_type=0,$active_id=0){
        $messageModel = new \Model\Admin\Message();
        $messageModel->send_type = 3;
        $messageModel->title = json_encode($title ?? "Message");
        $messageModel->admin_uid = 0;
        $messageModel->recipient = $user;
        $messageModel->type = '2';
        $messageModel->content = json_encode($content);
        $messageModel->admin_name = 0;
        $messageModel->active_type = $active_type;
        $messageModel->active_id = $active_id;
        if($userId > 0){
            $messageModel->user_id = $userId;
        }
        $messageModel->save();
        return $messageModel->id;
    }

    /**
     * 支付交易信息
     *
     * @param string $platform
     * @param string $pay_scene
     * @param string $trade_no
     * @param int $user_id
     * @param string $trans_id
     * @param int $money
     * @param string $pay_time
     */
    public function noticeInfo($platform, $pay_scene, $trade_no, $user_id, $trans_id, $money, $pay_time) {
        global $app;

        $data['platform'] = $platform;
        $data['pay_scene'] = $pay_scene;
        $data['user_id'] = $user_id;
        $data['trade_no'] = $trade_no;
        $data['trans_id'] = $trans_id;
        $data['money'] = $money;
        $data['pay_time'] = $pay_time ?? date('Y-m-d H:i:s');
        $data['created'] = date('Y-m-d H:i:s');

        $app->getContainer()->db->getConnection()
            ->table('funds_pay_callback')
            ->insert($data);
    }

    //开始调用第三方支付
    public function runThirdPay(int $userId, int $pay_id, float $money, string $order_number, string $return_url = null, string $bank_code = null, string $client_ip = null, $pl, string $notify_url = null, string $pay_type = null, string $coin_type=null,$coin_amount=null){
        global $app;
        $ci = $app->getContainer();
        $pay_info = \DB::table('payment_channel')
            ->where('id', $pay_id)
//            ->where('status', 'enabled')
            ->first();

        if(empty($pay_info) || $pay_info->status == 0){
            $result['code'] = 889;
            $result['msg'] = $this->lang->text(889);
            return $result;
        }

        $limit_money = $money;
        if($coin_amount > 0){
            $limit_money = bcmul($coin_amount, 100,2);
        }
        if($pay_info->min_money >0 && $limit_money < $pay_info->min_money){
            $result['code'] = 890;
            $result['msg'] = $this->lang->text(890,[$pay_info->min_money /100]);
            return $result;
        }
        if($pay_info->max_money >0 && $limit_money >= $pay_info->max_money){
            $result['code'] = 891;
            $result['msg'] = $this->lang->text(891,[$pay_info->max_money /100]);
            return $result;
        }
        if($pay_info->money_day_stop >0) {
            $query =\DB::table('funds_deposit')->where('status','=','paid')->where('payment_id',$pay_id)->whereRaw('FIND_IN_SET("online",state)');
            $toDayMoney = $query->where('process_time','>=',date('Y-m-d 00:00:00',time()))->where('process_time','<=',date('Y-m-d 23:59:59',time()))->sum('money');
            if (bcadd($toDayMoney, $limit_money) >= $pay_info->money_day_stop) {
                $result['code'] = 903;
                $result['msg'] = $this->lang->text(903, [$pay_info->money_day_stop / 100]);
                return $result;
            }
        }

        if($pay_info->money_stop >0){
            $stopMoney = \DB::table('funds_deposit')->where('status','=','paid')->where('payment_id',$pay_id)->whereRaw('FIND_IN_SET("online",state)')->sum('money');
            if(bcadd($stopMoney,$limit_money) >= $pay_info->money_stop){
                $result['code'] = 904;
                $result['msg'] = $this->lang->text(904,[$pay_info->money_stop /100]);
                return $result;
            }
        }
        $data=\DB::table('pay_config')->where('id',$pay_info->pay_config_id)->where('status','=','enabled')->first();
//        $data = [];
//        if($pay_info){
//            if($pay_info->type == 'luckypay' && in_array($pay_type,['711_direct','grabpay','qr','UBPB','BPIA'])){
//                $data = $pay_info;
//            }else{
//                if($pay_info->status == 'enabled'){
//                    $data = $pay_info;
//                }
//            }
//        }
//        if(empty($pay_type) && in_array($pay_info->type,['711_direct','grabpay','qr','UBPB','BPIA'])){
         if($pay_info->currency_type == 2){
             $pay_type = $coin_type;
         }else{
             $pay_type=$pay_info->type;
         }

//        }

        if($data){
            $data = (array)$data;
            if ($this->verifyData($data) && $this->existThirdClass($data['type'])) {
                try {
                    $code = strtolower($data['type']);

                    $randStr = 'O'.Utils::randStr(12).time();
                    $ci->redis->setex('payAllowOrder:'.$order_number, 24*60*60, $randStr);
                    //增加返回地址
                    if(in_array($data['type'],['didipay','apay','cloudpay','htpay','shuntongpay','rpay'])){
                        $return_url = empty($data['link_data']) ? '11' : $data['link_data'];
                    }
                    $data['url_return']     = $return_url;
                    $data['url_notify']     = $notify_url;
                    $data['order_number']   = $order_number;
                    $data['money']          = $money;
                    $data['bank_code']      = $bank_code;
                    $data['client_ip']      = $client_ip;
                    $data['pl']             = $pl;
                    $data['pay_type']       = $pay_type;
                    $data['user_id']        = $userId;
                    $data['channel_params'] = $pay_info->params ?? '';
                    $data['coin_amount']    = $coin_amount;

                    $obj    = $this->getThirdClass($code);
                    $result = $obj->run($data);

                    if($result['code'] == 0) {
                        $result['id']       = $data['id'];
                        $result['pay_id']   = $data['id'];
                        //$result['active_rule'] = json_decode($data['active_rule'],true);
                        $result['payname']  = $data['name'];
                        $result['scene']    = $data['type'];
                    }
                    $this->next = true;
                } catch (\Exception $e) {
                    $this->logger->error('runThirdPay error ' . $e->getMessage());
                    $result['code'] = 893;
                    $result['msg'] = $this->lang->text(893);
                }
            }else{
                $result['code'] = 892;
                $result['msg'] = $this->lang->text(892).$data['type'].':'.$this->existThirdClass($data['type']);
            }
        }else{
            $result['code'] = 892;
            $result['msg'] = $this->lang->text(892);
        }
        return $result;
    }

    public function verifyData($data){
        $vefifys = ['payurl', 'partner_id', 'key', 'pub_key', 'type'];
        foreach ($vefifys as $val){
            if(!(isset($data[$val]) && $data[$val])){
                return false;
            }
        }
        return true;
    }
}