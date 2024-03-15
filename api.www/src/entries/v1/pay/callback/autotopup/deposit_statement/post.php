<?php

use Logic\Recharge\Recharge;
use Utils\Www\Action;
use Respect\Validation\Validator as V;
return new class extends Action
{
    const TITLE = "AutoTopup 支付异步回调";
    const TAGS = '支付';
    const PARAMS = [
    ];
    const SCHEMAS = [
    ];

    public function run() {
        /**
         * {
        "datetime": "2022-02-21T08:05:00.000Z",
        "username": "ts1150",
        "amount": 6,
        "bonus": 0,
        "bonus_id": 104,
        "bonus_name": "ไม่รับโบนัส",
        "before_credit": 0,
        "after_credit": 0,
        "point": 80,
        "withdraw_fix": 0,
        "channel": "SCB_NETBANK",
        "detail": "กสิกรไทย (KBANK) /X219016/0784015623",
        "hash": "a96d6849f7b667ab8174ae8585f4357be400e963",
         "tx_id:"sf34t4wtgsr4w23423455",
        "status": 1,
        "comment": "",
        "last_bonus": 0,
        "last_bonus_name": "",
        "bank_number": "0784015623",
        "bank_code": "SCB",
        "bank_name": "Mr.Test Demo"
        }
         */
        $pay_type  = 'autotopup';
        $params    = $this->request->getParams();
        $log       = json_encode($params, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $rechargePay = new Logic\Recharge\Recharge($this->ci);
        $rechargePay->addLogByTxt(['third' => $pay_type, 'date' => date('Y-m-d H:i:s'), 'json' => $log, 'response' => ''], 'pay_callback');

        if ($params) {
            $desc = '';
            $flag = 0;
            $deposit_money = bcmul($params['amount'],100, 0);//充值金额分
            $pay_no = $params['tx_id'];
            //回调请求频率限制
            $lock_key = 'autotopup_callback_'.$pay_no;
            if ($this->redis->incr($lock_key) > 1) {
                return $this->response->withStatus(200)
                    ->withHeader('Content-Type', 'application/json')
                    ->withJson([
                        'status' => 'ERROR',
                        'message' => 'asking for repetition',
                    ]);
            }

            $this->redis->expire($lock_key, 4);

            if (!$rechargePay->existThirdClass($pay_type)) {
                $desc = '未有该第三方:' . $pay_type . '类，请技术核查';
            } else {
                try {
                    //IP白名单
                    if($rechargePay->isIPBlack($pay_type)){
                        $ip = \Utils\Client::getIp();
                        throw new \Exception('IP back ' . $ip);
                    }

                    $username = trim($params['username']);
                    if (empty($username)) {
                        throw new \Exception('用户不存在');
                    }
                    $user_id = \DB::table('user')->where('name', $username)->value('id');
                    if ($user_id == 0) {
                        throw new \Exception('用户不存在');
                    }

                    //判断交易号是否存在
                    $result = [];
                    $order_number = \DB::table('funds_deposit')->where('pay_no', '=', $pay_no)->value('trade_no');
                    if ($order_number) {
                        $result['str'] = $order_number;
                    }else{
                        //第一步生成支付单
                        $discount_active = \DB::table('active')
                            ->selectRaw('id')
                            ->where('status', '=', 'enabled')
                            ->whereIn('type_id', [2, 3, 7])
                            ->where('begin_time', '<', date('Y-m-d H:i:s'))
                            ->where('end_time', '>', date('Y-m-d H:i:s'))
                            ->get()
                            ->toArray();
                        $discount_active = array_column($discount_active, 'id');
                        $discount_active = empty($discount_active) ? 0 : implode(',', $discount_active);
                        //获取auto类型
                        $pay_config_id = \DB::table('pay_config')->where('type','autotopup')->where('status','enabled')->value('id');
                        if(empty($pay_config_id)){
                            throw new \Exception('支付已关闭');
                        }
                        $result = $rechargePay->autoTopUpPayWebSite($deposit_money, $user_id, $discount_active, $pay_config_id, $pay_no);
                        if ($result['code'] !== 0) {
                            throw new \Exception($result['msg']);
                        }
                    }
                    if($result){
                        //查是否15分钟前有人工充值
                        $operation_time = \DB::table('funds_deal_manual')->where('user_id', $user_id)
                            ->where('type', '1')
                            ->where('money', $deposit_money)
                            ->orderBy('id', 'desc')->limit(1)->value('updated');
                        if($operation_time){
                            $diff_time = time() - 15*60;
                            if($operation_time > $diff_time){
                                \DB::table('funds_deposit')->where('trade_no', '=', $result['str'])->update(['memo' => '已人工充值']);
                                $flag = 1;
                                throw new \Exception('已人工充值');
                            }
                        }
                        $params['order_number'] = $result['str'];
                        $re = $rechargePay->returnVerify($pay_type, $params);
                    }else{
                        throw new \Exception('生成支付单失败');
                    }
                    $flag = 1;
                } catch (\Throwable $e) {
                    $desc = $e->getMessage();
                }

                //写入回调日志表
                $logs = [
                    'order_number' => $re['order_number'] ?? '',
                    'desc'         => $desc,
                    'pay_type'     => $pay_type,
                    'content'      => $log,
                    'ip'           => \Utils\Client::getIp(),
                ];

                $rechargePay->addLogBySql($logs, 'pay_callback');
                //进入队列失败   说明代码或配置有误 定时器定时跑
                if ($flag == 0) {
                    $repeat = [
                        'pay_type'  => $pay_type,
                        'method'    => $this->request->getMethod(),
                        'content'   => $log,
                        'error'     => $desc
                    ];
                    $rechargePay->addLogBySql($repeat, 'pay_callback_failed');
                    return $this->response->withStatus(200)
                        ->withHeader('Content-Type', 'application/json')
                        ->withJson([
                            'status' => 'ERROR',
                            'message' => $desc,
                        ]);
                }else{
                    return $this->response->withStatus(200)
                        ->withHeader('Content-Type', 'application/json')
                        ->withJson([
                            'status' => 'OK',
                        ]);
                }
            }
        }else{
            return $this->response->withStatus(200)
                ->withHeader('Content-Type', 'application/json')
                ->withJson([
                    'status' => 'ERROR',
                    'message' => 'NO PARAMS',
                ]);
        }
    }
};
