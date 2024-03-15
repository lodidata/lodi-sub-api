<?php

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;

/**
 *
 * LINKPAY
 * @author 
 */
class LINKPAY extends BASES {
    public $http_code;

    static function instantiation() {
        return new LINKPAY();
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
        if(empty($this->rechargeType)){
            $this->rechargeType = 'gcash';
        }
        $data = array(
            'merchantNo'      => $this->partnerID,
            'merchantOrderNo' => $this->orderID,
            'payAmount'       => bcdiv($this->money, 100,2),
            'description'     => 'goods',
            'method'          => $this->rechargeType,
            'name'            => 'recharge',
            'mobile'          => '0123456789',
            'email'           => 'xxx@gmail.com',
            'notifyUrl'       => $this->payCallbackDomain . '/pay/callback/linkpay',
        );

        $data['sign']    = $this->sign($data);

        $this->parameter = $data;
        $this->payUrl   .= '/payment-gateway/pay/code';
    }



    public function formPost() 
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($curl, CURLOPT_TIMEOUT, 15);
        curl_setopt($curl, CURLOPT_URL, $this->payUrl);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        $post_data = json_encode($this->parameter);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
            'Content-Length:' . strlen($post_data) ,
            'Cache-Control: no-cache',
            'Pragma: no-cache'
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($curl);
        
        $this->http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $this->re = $response;

        curl_close($curl);
    }




    //处理结果
    public function parseRE() {
        $result     = json_decode($this->re, true);
        $status       = isset($result['status']) ? $result['status'] : 1;
        $message    = isset($result['message']) ? $result['message'] : 'errorMsg:'.(string)$this->re;
        
        if ($this->http_code  == 200) {
            //下单成功，跳转支付链接 The order is successfully placed, jump to the payment link
            if($status == 200){
                $code = 0;
                $targetUrl = $result['data']['paymentLink'];
            }else{
                $targetUrl = '';
                $code = 1;
            }

            $this->return['code']    = $code;
            $this->return['msg']     = $message;
            $this->return['way']     = 'jump';
            $this->return['str']     = $targetUrl;
            $this->return['pay_no']  = $result['data']['platOrderNo'] ?? '';
        } else{
            $this->return['code'] = 886;
            $this->return['msg'] = 'http_code:' . $this->http_code;
            $this->return['way'] = 'jump';
            $this->return['str'] = $this->re;
        }
    }




    //回调数据校验
    /*
     * $resArray 第三方通知数组
     * $deposit  系统流水信息
     * $vender   授权信息
     * */
    public function returnVerify($param = []) 
    {
        $config    = Recharge::getThirdConfig('linkpay');
        $this->pubKey = $config['pub_key'];
        $params = $param;

        $res = [
            'status'        => 0,
            'order_number'  => $param['merchantOrderNo'],
            'third_order'   => $param['platOrderNo'],
            'third_money'   => bcmul($param['amount'], 100),
            'third_fee'     => 0,
            'error'         => '',
        ];

        //检验状态
        if ($this->signVerify($params)) {
            if(in_array($param['orderStatus'], ['SUCCESS','ARRIVED','CLEARED']))
            {
                $res['status'] = 1;
            }else{
                throw new \Exception('unpaid');
            }
        } else {
            throw new \Exception('sign is wrong');
        }

        return $res;
    }



    //生成签名
    public function sign($data) {
        unset($data['sign']);
        ksort($data);
        reset($data);

        $str = '';
        foreach ($data as $k => $v){
            if(is_null($v) || $v === '') continue;
            $str .= $v;
        }

        $prikey = "-----BEGIN RSA PRIVATE KEY-----\n".wordwrap($this->key, 64, "\n", true)."\n-----END RSA PRIVATE KEY-----";

        $content = '';
        foreach (str_split($str, 117) as $str1) {
            openssl_private_encrypt($str1, $crypted, $prikey);
            $content .= $crypted;
        }
        $sign = base64_encode($content);

        return $sign;
    }


    //回调校验签名
    public function signVerify($data) {
        $sign = $data['sign'];
        $sign = str_replace('-','+',$sign);
        $sign = str_replace('_','/',$sign);

        $pay_public_key = "-----BEGIN PUBLIC KEY-----\n".wordwrap($this->pubKey, 64, "\n", true)."\n-----END PUBLIC KEY-----";
        $res = openssl_pkey_get_public($pay_public_key);

        $crypto = '';
        foreach (str_split(base64_decode($sign), 128) as $chunk) {
            openssl_public_decrypt($chunk, $decryptData, $res);
            $crypto .= $decryptData;
        }

        if(!empty($crypto)){
            return true;
        }
        return false;
    }



    /**
     * 补单
     * @param $order_number
     * @return mixed
     */
    public function supplyOrder($order_number, $payNo = '')
    {
        $config     = Recharge::getThirdConfig('BPAY');
        $this->key  = $config['key'];

        //请求参数 Request parameter
        $data = array(
            'merchantNo' => $config['partner_id'],//    是   string  商户号 business number
            'merchantOrderNo'    => $order_number,
        );

        $data['sign']    = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl    = $config['payurl'].'/payment/order/query';

        $this->formPost();
        $this->addStartPayLog();

        $result  = json_decode($this->re, true);
        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:'.(string)$this->re;

        if ($this->http_code == 200) {
            if($code == 200){
                //未支付
                if($result['data']['paymentStatus'] != 'SUCCESS'){
                    throw new \Exception($result['data']['paymentStatus']);
                }
                $res = [
                    'status'       => $result['data']['paymentStatus'],
                    'order_number' => $result['data']['merchantOrderNo'],
                    'third_order'  => $result['data']['orderNo'],
                    'third_money'  => $result['data']['orderAmount'] * 100,
                ];
                return $res;
            }
        }

        throw new \Exception('http_code:'.$this->http_code.' code:'.$code.' message:'.$message);
    }



}
