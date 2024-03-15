<?php

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;

/**
 *
 * TOPPAY
 * @author
 */
class TOPPAY extends BASES {
    public $http_code;

    static function instantiation() {
        return new TOPPAY();
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
        $data = array(
            'merchant_no'        => $this->partnerID,
            'out_trade_no'  => $this->orderID,
            'description'        => 'payin',
            'pay_amount'             => bcdiv($this->money, 100,2),
            'name'      => 'Andy',
            'email'     => 'james@gmail.com',
            'contact_phone'    => '1234567000',
            'pay_type'         => 0,
            'notify_url'         => $this->payCallbackDomain . '/pay/callback/toppay',
        );

        if(!is_null($this->rechargeType) && $this->rechargeType != ''){
            $data['pay_type'] = $this->rechargeType;
        }

        $data['sign']    = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl   .= '/api/trade/payin';
    }

    //生成签名
    public function sign($data) {
        unset($data['sign']);
        ksort($data);
        $str = [];
        foreach ($data as $k => $v) {
            if ($v === '') {
                continue;
            }
            $str[] = $k . '=' . $v;
        }
        $send = implode('&', $str) . '&';
        // 获取用户公钥，并格式化
        $privateKey = "-----BEGIN PRIVATE KEY-----\n"
            . wordwrap(trim($this->key), 64, "\n", true)
            . "\n-----END PRIVATE KEY-----";
        $content = '';
        $privateKey = openssl_pkey_get_private($privateKey);
        foreach (str_split($send, 117) as $temp) {
            openssl_private_encrypt($temp, $encrypted, $privateKey);
            $content .= $encrypted;
        }
        return base64_encode($content);
    }


    //验证回调签名
    public function verifySign($data, $publicKey) {
        if (isset($data['sign'])) {
            $sign = base64_decode($data['sign']);
            unset($data['sign']);
        } else {
            return false;
        }
        ksort($data);
        $str = [];
        foreach ($data as $k => $v) {
            if ($v === '') {
                continue;
            }
            $str[] = $k . '=' . $v;
        }
        $send = implode('&', $str) . '&';
        // 获取用户公钥，并格式化
        $publicKey = "-----BEGIN PUBLIC KEY-----\n"
            . wordwrap(trim($publicKey), 64, "\n", true)
            . "\n-----END PUBLIC KEY-----";
        $publicKey = openssl_pkey_get_public($publicKey);
        $result = '';
        foreach (str_split($sign, 128) as $value) {
            openssl_public_decrypt($value, $decrypted, $publicKey);
            $result .= $decrypted;
        }
        return $result === $send;
    }


    //处理结果
    public function parseRE() {
        $result     = json_decode($this->re, true);
        $code       = isset($result['code']) ? $result['code'] : 1;
        $message    = isset($result['msg']) ? $result['msg'] : 'errorMsg:'.(string)$this->re;

        if ($this->http_code  == 200) {
            //下单成功，跳转支付链接 The order is successfully placed, jump to the payment link
            if($code == 0){
                $targetUrl =  $result['data']['payment_link'];
            }else{
                $targetUrl = '';
            }

            $this->return['code']    = $code;
            $this->return['msg']     = $message;
            $this->return['way']     = 'jump';
            $this->return['str']     = $targetUrl;
            $this->return['pay_no']  = $result['data']['trade_no'] ?? '';
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
        $config       = Recharge::getThirdConfig('toppay');
        $this->pubKey = $config['pub_key'];
        $this->partnerID = $config['partner_id'];

        $params = [
            'merchant_no' => $this->partnerID,
            'out_trade_no' => $param['out_trade_no'],
            'trade_no' => $param['trade_no'],
            'pay_amount' => $param['pay_amount'],
            'status' => $param['status'],
            'fee_amount' => $param['fee_amount'],
            'sign' => $param['sign'],
        ];

        $res = [
            'status'        => 0,
            'order_number'  => $param['out_trade_no'],//我们订单号
            'third_order'   => $param['trade_no'],//第三方订单号
            'third_money'   => bcmul($param['pay_amount'] , 100),
            'third_fee'     => bcmul($param['fee_amount'] , 100),
            'error'         => '',
        ];

        if ($this->verifySign($params, $config['pub_key'])) {
            //订单状态
            if($param['status'] == 1){
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
        $config          = Recharge::getThirdConfig('toppay');
        $this->key       = $config['key'];
        $this->partnerID = $config['partner_id'];

        //请求参数 Request parameter
        $data = array(
            'merchant_no'       => $this->partnerID,
            'out_trade_no' => $order_number,
        );

        $data['sign']    = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl    = $config['payurl'].'/api/trade/query';

        $this->formPost();
        $this->addStartPayLog();

        $result  = json_decode($this->re, true);
        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:'.(string)$this->re;

        if ($this->http_code == 200) {
            if($code == 0){
                //订单状态：
                if($result['data']['status'] != 1){
                    throw new \Exception($result['data']['status']);
                }
                $res = [
                    'status'       => $result['data']['status'],
                    'order_number' => $result['data']['out_trade_no'],
                    'third_order'  => $result['data']['trade_no'],
                    'third_money'  => bcmul($result['data']['amount'] , 100),
                ];
                return $res;
            }
        }

        throw new \Exception('http_code:'.$this->http_code.' code:'.$code.' message:'.$message);
    }



}
