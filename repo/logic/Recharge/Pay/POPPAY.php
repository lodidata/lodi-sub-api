<?php

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;

/**
 *
 * POPPAY
 * @author
 */
class POPPAY extends BASES {
    public $http_code;

    static function instantiation() {
        return new POPPAY();
    }

    //与第三方交互
    public function start() {
        $this->initParam();  // 数据初始化
        $this->formPost();  // 发送请求
        $this->parseRE();  // 处理结果
    }

    //组装数组
    public function initParam() {
        //请求参数 Request parameter
        $time = time();
        $data = array(
            'merchant_id'        => $this->partnerID,
            'merchant_trade_no'  => $this->orderID,
            'amount'             => bcdiv($this->money, 100,2),
            'description'        => 'payin',
            'customer_name'      => $this->userId,
            'customer_email'     => 'james@gmail.com',
            'customer_mobile'    => '12345670',
            'channel_id'         => $this->payType,
            'notify_url'         => $this->payCallbackDomain . '/pay/callback/poppay',
            'timestamp'          => date('Y-m-d H:i:s', $time),
        );

        //
        if($data['channel_id'] == '')
        {
            unset($data['channel_id']);
        }


        $data['sign']    = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl   .= '/trade/payin/create';
    }

    //生成签名
    public function sign($data) {
        unset($data['sign']);
        ksort($data);
        reset($data);

        $str = '';
        foreach ($data as $k => $v){
            if(is_null($v) || $v === '') continue;
            $str .= $k.'='.$v.'&';
        }
        $prikey = "-----BEGIN RSA PRIVATE KEY-----\n".wordwrap($this->key, 64, "\n", true)."\n-----END RSA PRIVATE KEY-----";
        $key = openssl_get_privatekey($prikey);
        openssl_sign($str, $sign, $key);
        openssl_free_key($key);
        return base64_encode($sign);
    }

    //验证回调签名
    public function verifySign($data) {
        $sign = base64_decode($data['sign']);
        unset($data['sign']);
        ksort($data);
        reset($data);

        $str = '';
        foreach ($data as $k => $v){
            if(is_null($v) || $v === '') continue;
            $str .= $k.'='.$v.'&';
        }
        $pubkey = "-----BEGIN PUBLIC KEY-----\n".wordwrap($this->pubKey, 64, "\n", true)."\n-----END PUBLIC KEY-----";
        $key = openssl_pkey_get_public($pubkey);
        if(openssl_verify($str, $sign, $key, OPENSSL_ALGO_SHA1) === 1){
            return true;
        }
        return false;
    }

    //处理结果
    public function parseRE() {
        $result     = json_decode($this->re, true);

        $code       = isset($result['ret_code']) ? $result['ret_code'] : 1;
        $message    = isset($result['ret_msg']) ? $result['ret_msg'] : 'errorMsg:'.(string)$this->re;

        if ($this->http_code  == 200) {
            //下单成功，跳转支付链接 The order is successfully placed, jump to the payment link
            if($code === '0000'){
                $targetUrl =  urldecode($result['pay_cashier_url']) ;
            }else{
                $targetUrl = '';
            }

            $this->return['code']    = $code;
            $this->return['msg']     = $message;
            $this->return['way']     = 'jump';
            $this->return['str']     = $targetUrl;
            $this->return['pay_no']  = $result['platform_trade_no'] ?? '';
        } else{
            $this->return['code'] = 886;
            $this->return['msg'] = 'http_code:' . $this->http_code;
            $this->return['way'] = 'jump';
            $this->return['str'] = $this->re;
        }

    }

    public function formPost() {
        $ch = curl_init();
        $params_data = json_encode($this->parameter, JSON_UNESCAPED_UNICODE);
        curl_setopt($ch, CURLOPT_URL, $this->payUrl);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params_data);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        $response = curl_exec($ch);
        $this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->re = $response;

    }


    //回调数据校验
    /*
     * $resArray 第三方通知数组
     * $deposit  系统流水信息
     * $vender   授权信息
     * */
    public function returnVerify($param = []) {
        $config       = Recharge::getThirdConfig('poppay');
        $this->pubKey = $config['pub_key'];

        $res = [
            'status'        => 0,
            'order_number'  => $param['merchant_trade_no'],//我们订单号
            'third_order'   => $param['platform_trade_no'],//第三方订单号
            'third_money'   => bcmul($param['actual_amount'] , 100),
            'third_fee'     => 0,
            'error'         => '',
        ];

        if ($this->verifySign($param)) {
            //订单状态：0-待支付，1-支付成功，2-支付失败
            if($param['trade_status'] == 1){
                $res['status'] = 1;
                return $res;
            }
            throw new \Exception('unpaid');

        } else {
            throw new \Exception('sign is wrong');
        }

    }

    /**
     * 补单
     * @param $order_number
     * @return mixed
     */
    public function supplyOrder($order_number, $payNo = '')
    {
        $config          = Recharge::getThirdConfig('poppay');
        $this->key       = $config['key'];
        $this->partnerID = $config['partner_id'];

        //请求参数 Request parameter
        $data = array(
            'merchant_id'       => $this->partnerID,
            'trade_type'        => 'payin',
            'merchant_trade_no' => $order_number,
            'timestamp'         => date('Y-m-d H:i:s'),
        );

        $data['sign']    = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl    = $config['payurl'].'/trade/query';

        $this->formPost();
        $this->addStartPayLog();

        $result  = json_decode($this->re, true);
        $code    = isset($result['ret_code']) ? $result['ret_code'] : 1;
        $message = isset($result['ret_msg']) ? $result['ret_msg'] : 'errorMsg:'.(string)$this->re;

        if ($this->http_code == 200) {
            if($code === '0000'){
                //订单状态：0-待支付，1-支付成功，2-支付失败 3-审核中 4-处理中 5-冲正退回
                if($result['trade_status'] != 1){
                    throw new \Exception($result['trade_status']);
                }
                $res = [
                    'status'       => $result['trade_status'],
                    'order_number' => $result['merchant_trade_no'],
                    'third_order'  => $result['platform_trade_no'],
                    'third_money'  => bcmul($result['actual_amount'] , 100),
                ];
                return $res;
            }
        }

        throw new \Exception('http_code:'.$this->http_code.' code:'.$code.' message:'.$message);
    }



}
