<?php

namespace Logic\Transfer\ThirdParty;

use Logic\Wallet\Wallet;

/**
 *
 * KPAY代付
 */
class KPAY extends BASES
{
    private $httpCode = '';

    //回调，不处理逻辑
    public function callbackMsg()
    {
        return 'success';
    }

    //请求代付接口
    public function runTransfer()
    {
        $params          = [
            "mer_account"      => $this->partnerID, //商户号
            "order_no" => $this->orderID,   //订单号
            "order_amount"  => bcdiv($this->money, 100, 2),   //代付金额
            "account_no"    => $this->bankCard,
            "username" => $this->bankUserName,
            "currency"    => "PHP",            //币种代码
            "callback_url"       => $this->payCallbackDomain . '/thirdAdvance/callback/kpay',   //回调地址

        ];
        $this->parameter = $params;
        //判断 额外参数是否为空 Determine whether the extra parameter is empty
        $this->payUrl .= '/api/kpay/pay/apply';

        $this->initParam($params);
        $this->basePostNew();
        $result  = isset($this->re) ? json_decode($this->re, true) : '';
        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;
        if ($this->httpCode == 200) {
            //true成功,false失败
            if ($code == 0) {
                $this->return['code']    = 10500;
                $this->return['balance'] = $this->money;
                $this->return['msg']     = $message;
                $this->transferNo        = $result['data']['sys_order_no']; //第三方订单号
                //成功就直接返回了
                return;
            } else {
                //$message = 'http_code:' . $this->httpCode . 'errorMsg:' . json_encode($result, JSON_UNESCAPED_UNICODE);
                $this->updateTransferOrder($this->money, 0, '', '', 'failed', null, $message);
                $this->return['code']    = 886;
                $this->return['balance'] = 0;
                $this->return['msg']     = 'KPAY:' . $message ?? '代付失败';
                return;
            }
        }

        $this->return['code']    = 886;
        $this->return['balance'] = $this->money;
        $this->return['msg']     = $message;
        $this->transferNo        = ''; //第三方订单号
    }



    //代付订单查询
    public function getTransferResult()
    {
        $this->return = ['code' => 886, 'transfer' => '', 'msg' => ''];
        return;
        $params = [
            'mer_account'      => $this->partnerID,
            'order_no' => $this->orderID
        ];
        $this->payUrl .= "/api/kpay/pay/apply_check";
        $this->initParam($params);
        $this->basePostNew();
        $result     = json_decode($this->re, true);
        $code       = isset($result['code']) ? $result['code'] : 1;
        $message    = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;

        if ($this->httpCode == 200) {
            if ($code == 0) {
                $resultData = $result['data'];
                $this->transferNo = isset($resultData['sys_order_no']) ? $resultData['sys_order_no'] : '';
                //订单状态：PROCESSING 处理中FAILED 失败SUCCESS 成功
                if ($resultData['result_status'] == 'success') {
                    $status = 'paid';
                    $this->return = ['code' => 1,  'msg' => $message];
                } elseif ($resultData['result_status'] == 'fail') {
                    $status = 'failed';
                    $this->return = ['code' => 0,  'msg' => $message];
                } elseif ($resultData['result_status'] == 'waiting') {
                    $status = "pending";
                    $this->return = ['code' => 0,  'msg' => $message];
                } else {
                    $this->return = ['code' => 0,  'msg' => $message];
                    return;
                }

                $real_money = bcmul($resultData['amount'], 100);
                $fee        = $this->money - $real_money;
                $this->updateTransferOrder(
                    $this->money,
                    $real_money,
                    $resultData['sys_order_no'],
                    '',
                    $status,
                    $fee,
                    $message
                );
                return;
            } else if ($code === 1500) {
                //804没有单号改为失败
                $this->updateTransferOrder($this->money, 0, '', '', 'failed', null, $message);
                return;
            }
        }
        $this->return = ['code' => 886, 'transfer' => '', 'msg' => 'http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message];
    }

    //组装数组
    public function initParam($params = [])
    {
        $data            = $params;
        $data['sign']    = $this->sign($params);  //校验码
        $this->parameter = $data;
    }

    //签名
    public function sign($data)
    {
        if (isset($data['sign'])) {
            unset($data['sign']);
        }
        ksort($data);
        reset($data);

        $str = '';
        foreach ($data as $k => $v) {
            if (is_null($v) || $v === '') continue;
            $str .= $k . '=' . $v . '&';
        }
        $str = trim($str, '&');

        $prikey = "-----BEGIN PRIVATE KEY-----\n" . wordwrap($this->key, 64, "\n", true) . "\n-----END PRIVATE KEY-----";

        $key = openssl_get_privatekey($prikey);
        openssl_sign($str, $sign, $key, OPENSSL_ALGO_SHA256);

        $sign = base64_encode($sign);
        return $sign;
    }


    //验证回调签名
    public function verifySign($data)
    {
        if (isset($data['sign']) && !empty($data['sign'])) {
            $sign = base64_decode($data['sign']);
            unset($data['sign']);
            ksort($data);
            reset($data);

            $str = '';
            foreach ($data as $k => $v) {
                if (is_null($v) || $v === '') continue;
                $str .= $k . '=' . $v . '&';
            }
            $str = trim($str, '&');
            $pay_public_key = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($this->pubKey, 64, "\n", true) . "\n-----END PUBLIC KEY-----";

            $key = openssl_get_publickey($pay_public_key);
            if (openssl_verify($str, $sign, $key, OPENSSL_ALGO_SHA256) === 1) {
                return true;
            }
        }
        return false;
    }

    public function basePostNew($referer = null)
    {
        $this->payRequestUrl = $this->payUrl;
        $params_data = json_encode($this->parameter, JSON_UNESCAPED_UNICODE);
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->payUrl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length:' . strlen($params_data),
            'Accept-Api-Version:v1'
        ]);
        if ($referer) {
            curl_setopt($ch, CURLOPT_REFERER, $referer);
        }
        $response        = curl_exec($ch);
        //$this->curlError = curl_error($ch);
        $this->httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->re        = $response;
        curl_close($ch);
    }

    /**
     * 代付异步回调
     * @param array $params 异步参数
     * @return mixed
     */
    public function callbackResult($params)
    {
        $this->parameter = $params;
        //var_dump($params);
        if (!$this->verifySign($params)) {
            throw new \Exception('Sign error');
        }
        //订单状态已经成功 或者 消息与订单状态一致
//         if ($this->order['status'] == 'paid') {
//             return;
//         }

        //订单状态付款状态：(//订单状态：PROCESSING 处理中FAILED 失败SUCCESS 成功)
        if ($params['order_status'] == 'success') {
            $status       = 'paid';
            $this->return = ['code' => 1, 'msg' => ''];
        } elseif ($params['order_status'] == 'waiting') {
            $status       = 'pending';
            $this->return = ['code' => 0, 'msg' => '代付处理中'];
        } elseif ($params['order_status'] == 'fail') {
            $status       = 'failed';
            $this->return = ['code' => 0, 'msg' => '代付失败'];
        } else {
            $this->return = ['code' => 0, 'msg' => 'error'];
            return;
        }
        $this->re = $this->return;
        $real_money     = bcmul($params['amount'], 100); //以分为单位
        \DB::table("transfer_order")->where('trade_no', $params['order_sn'])->where('status', 'pending')
            ->update([
                'money' => $this->money,
                'real_money' => $real_money,
                'confirm_time' => date('Y-m-d H:i:s'),
                'status' => $status,
                'fee' => 0,
            ]);
        if ($params['order_sn'] && $status == 'paid') {
            \Logic\Transfer\ThirdTransfer::updateWithdrawOrder($this->order['withdraw_order'], $status);
        }

        $info = json_decode($params['info'], true);
        if (count($info) <= 0) return;
        foreach ($info as $k => $v) {
            $status = $v['order_status'];
            if ($status == 'waiting' && $v['status'] == 4) {
                $status = 'confirming';
                $withdraw = \DB::table('funds_withdraw')
                    ->where('trade_no', $this->order['withdraw_order'])
                    ->first(['user_id']);
                if ($withdraw) {
                    //存redis消息
                    global $app;
                    $redisKey = 'kpay:withdraw_message:'.$withdraw->user_id;
                    $transfer_no = $params['mer_no'];
                    $app->getContainer()->redis->lrem($redisKey,0,$transfer_no);
                    $app->getContainer()->redis->rpush($redisKey,$transfer_no);
                }

            }
            \DB::table("transfer_no_sub")
                ->updateOrInsert(
                    [
                        'transfer_no' => $params['mer_no'],
                        'sub_order' => $v['mer_no']
                    ],
                    [
                        'order_type' => $v['order_type'],
                        'amount' => bcmul($v['amount'], 100),
                        'currency' => $v['currency'],
                        'user_account' => $v['user_account'],
                        'created_at' => $v['create_time'],
                        'status' => $status,
                        'updated_at' => $v['update_time']
                    ],
                );
        }
        return;
    }

    //查询余额
    public function getThirdBalance()
    {
        $params       = [
            'mer_account' => $this->partnerID
        ];

        $this->payUrl .= "/api/kpay/pay/balance_query";

        $this->initParam($params);
        $this->basePostNew();
        $result  = json_decode($this->re, true);
        $code       = isset($result['code']) ? $result['code'] : 1;
        $message    = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;

        $config_params = !empty($this->thirdConfig['params']) ? json_decode($this->thirdConfig['params'],true) : [];

        if($this->httpCode == 200) {
            if($code === 0) {
                $balance = 0;
                $tmp=[];
                if(!empty($result['data']['currency'])) {
                    foreach($result['data']['currency'] as $k => $v) {
                        if($k == $config_params['currencyCode']){
                            $balance = $v;
                            $tmp=$v;
                        }
                    }
                }
                $this->return['code']    = 10509;
                $this->return['balance'] = bcmul($balance ,100);
                $this->return['msg']     = json_encode($tmp);
                return;
            }
        }
        $this->_end();
        throw new \Exception('http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message);
    }

    /**
     * 订单确认
     */
    public function submitOrderStatus($transfer_no_sub, $status_type, $user_id)
    {
        $this->payUrl .= "/api/kpay/pay/confirm_status";
        $params = [
            'mer_account' => $this->partnerID,
            'orders_pay_sn' => $this->transferNo,
            'sub_orders_pay_sn' => $transfer_no_sub,
            'status' => $status_type
        ];
        $trade_no = $this->orderID;
        $this->parameter = $params;
        $this->initParam($params);
        $this->basePostNew();
        $result  = isset($this->re) ? json_decode($this->re, true) : '';
        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;
        if ($this->httpCode == 200) {
            //true成功,false失败
            if ($code === 0) {
                //未到账订单
                $this->return['code']    = 10517;
                $this->return['msg']     = $message;

                if ($status_type == 0) {
                    \DB::table("transfer_no_sub")->where(['sub_order' => $transfer_no_sub, 'transfer_no' => $this->transferNo, 'order_type' => 1,])
                        ->update(['status' => 'dispute']);
                }else{
                    \DB::table("transfer_no_sub")->where(['sub_order' => $transfer_no_sub, 'transfer_no' => $this->transferNo, 'order_type' => 1])
                        ->whereIn('status',['waiting','confirming'])->update(['status' => 'success']);
                }

                $all_sub = \DB::table("transfer_no_sub")->where(['transfer_no' => $this->transferNo])->get(['status','amount'])->toArray();
                if (count($all_sub) > 0) {
                    $update_amount = 0;
                    foreach ($all_sub as $k => $v) {
                        if ($v->status == 'success') {
                            $update_amount += $v->amount;
                        }
                    }

                    $get_transfer_order = \DB::table("transfer_order")->where('transfer_no', $this->transferNo)->first(['money']);
                    if ($update_amount == $get_transfer_order->money) {
                        \DB::table("transfer_order")->where('transfer_no', $this->transferNo)->update(['status' => 'paid', 'updated' => date('Y-m-d H:i:s')]);
                    }
                }
                //查询是否奖励
                $transfer_no_sub_data = \DB::table("transfer_no_sub")->where([
                            'sub_order' => $transfer_no_sub,
                            'transfer_no' => $this->transferNo,
                            'status' => 'success',
                            'order_type' => 1
                        ])->first(['is_reward','amount','created_at']);
                if (!empty($transfer_no_sub_data) && $transfer_no_sub_data->is_reward == 0) {
                    $diff_time = strtotime($transfer_no_sub_data->created_at) + 17*60 - time();
                    //发放奖励,创建时间 17 分钟内
                    if ($diff_time >= 0 && $diff_time < 17*60) {
                        $reward = floor($transfer_no_sub_data->amount * 0.05);
                        global $app;
                        $ci = $app->getContainer();
                        (new \Logic\Recharge\Recharge($ci))->innerPaySendCoupon($user_id, $reward, $reward, 'Confirm receipt rewards', \Utils\Client::getIp(), 0);

                        \DB::table("transfer_no_sub")->where([
                            'sub_order' => $transfer_no_sub,
                            'transfer_no' => $this->transferNo,
                            'status' => 'success',
                            'order_type' => 1,
                            'is_reward' => 0
                        ])->update(['is_reward' => 1, 'reward' => $reward]);
                    }
                }
                //成功就直接返回了
                return;
            } else {
                //$message = 'http_code:' . $this->httpCode . 'errorMsg:' . json_encode($result, JSON_UNESCAPED_UNICODE);
                $this->return['code']    = 886;
                $this->return['msg']     = 'KPAY:' . $message ?? '代付确认失败';
                return;
            }
        }
    }

    public function uploadSms($user_id,$message)
    {
        $params       = [
            'mer_account' => $this->partnerID,
            'message' => $message,
        ];

        //查询gcash
        $bank_id = \DB::table('bank')->where(['code'=>'Gcash','status'=>'enabled'])->value('id');
        $user_bank = (array)\DB::table('bank_user')
            ->where(['user_id' => $user_id, 'bank_id' => $bank_id,'state' => 'enabled', 'role' => 1])
            ->first(['card']);
        if (count($user_bank) > 0) {
            $user_card = \Utils\Utils::RSADecrypt($user_bank['card']);
        } else {
            $user_card = '111111111';
        }
        $params['account_no'] = $user_card;

        $this->payUrl .= "/api/kpay/pay/upload_sms";

        $this->initParam($params);
        $this->basePostNew();
        $result  = json_decode($this->re, true);
        $code       = isset($result['code']) ? $result['code'] : 1;
        $message    = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;

        if($this->httpCode == 200) {
            if($code === 0) {
                return;
            }
        }
        throw new \Exception('http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message);
    }
}
