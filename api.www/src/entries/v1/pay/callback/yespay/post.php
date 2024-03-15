<?php
use Utils\Www\Action;
use Respect\Validation\Validator as V;
return new class extends Action
{
    const TITLE = "yespay 支付异步回调";
    const TAGS = '支付';
    const PARAMS = [
    ];
    const SCHEMAS = [
    ];

    public function run() {
        $pay_type  = 'yespay';

        $params    = $this->request->getParams();
        $log       = json_encode($params, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        \Logic\Recharge\Recharge::addLogByTxt(['third' => $pay_type, 'date' => date('Y-m-d H:i:s'), 'json' => $log, 'response' => ''], 'pay_callback');

        if ($params) {
            $desc = '';
            $flag = 0;
            $pay = new Logic\Recharge\Recharge($this->ci);
            if (!$pay->existThirdClass($pay_type)) {
                $desc = '未有该第三方:' . $pay_type . '类，请技术核查';
            } else {
                try{
                    if($pay->isIPBlack($pay_type)){
                        $ip = \Utils\Client::getIp();
                        throw new \Exception('IP back ' . $ip);
                    }

                    $data = json_decode($params['params'], true);
                    if ($data['product'] == 'ThaiAutoBilling') {

                        $userId = $data['customerid'];
                        if (empty($userId)) {
                            throw new \Exception('用户不存在.special_suggestion');
                        }

                        // 判断用户是否存在
                        $userId = \DB::table('user')->where('id', $userId)->value('id');

                        if (empty($userId)) {
                            throw new \Exception('用户不存在.special_suggestion');
                        }

                        $depositMoney = bcmul($data['amount'],100, 0);//充值金额分

                        //判断交易号是否存在
                        $result = [];
                        $pay_no = $data['system_ref'];
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
                            $config = \DB::table('pay_config')->where('type', $pay_type)
                                ->where('status','=','enabled')->first();
                            if(empty($config)){
                                throw new \Exception('支付已关闭');
                            }

                            $result = $pay->autoTopUpPayWebSite($depositMoney, $userId, $discount_active, $config->id, $pay_no, 'ThaiAutoBilling');
                            if ($result['code'] !== 0) {
                                throw new \Exception($result['msg']);
                            }
                        }

                        if($result){
                            //查是否15分钟前有人工充值
                            $operation_time = \DB::table('funds_deal_manual')->where('user_id', $userId)
                                ->where('type', '1')
                                ->where('money', $depositMoney)
                                ->orderBy('id', 'desc')->limit(1)->value('updated');
                            if($operation_time){
                                $diff_time = time() - 15*60;
                                if($operation_time > $diff_time){
                                    \DB::table('funds_deposit')->where('trade_no', '=', $result['str'])->update(['memo' => '已人工充值']);
                                    $flag = 1;
                                    throw new \Exception('已人工充值');
                                }
                            }
                            $params['clearOrderNo'] = true;
                            $data['merchant_ref'] = $result['str'];
                            $params['params'] = str_replace('"merchant_ref": null', '"merchant_ref": "' . $result['str'] . '"', $params['params']);
                            $re = $pay->returnVerify($pay_type, $params);
                        }else{
                            throw new \Exception('生成支付单失败');
                        }
                    } else {
                        $re = $pay->returnVerify($pay_type, $params);
                    }
                    $flag = 1;
                }catch (\Throwable $e){
                    $desc = $e->getMessage();
                }
            }
            //写入回调日志表
            $logs = [
                'order_number' => $re['order_number'] ?? '',
                'desc'         => $desc,
                'pay_type'     => $pay_type,
                'content'      => $log,
                'ip'           => \Utils\Client::getIp(),
            ];

            \Logic\Recharge\Recharge::addLogBySql($logs, 'pay_callback');
            //进入队列失败   说明代码或配置有误 定时器定时跑
            if ($flag == 0) {
                $repeat = [
                    'pay_type'  => $pay_type,
                    'method'    => $this->request->getMethod(),
                    'content'   => $log,
                    'error'     => $desc
                ];
                \Logic\Recharge\Recharge::addLogBySql($repeat, 'pay_callback_failed');
                echo $desc;
            }else{
                echo 'SUCCESS';
            }

        }else{
            echo 'NO PARAMS';
        }
        die();
    }
};
