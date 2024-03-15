<?php

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;

/**
 *
 * XPAY
 * @author 
 */
class XPAY extends BASES {
    public $http_code;

    static function instantiation() {
        return new XPAY();
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
            'mer_no' => $this->partnerID,
            'mer_order_no' => $this->orderID,
            'pname' => 'test',
            'pemail' => 'test@gmail.com',
            'phone' => '01234567899',
            'order_amount' => bcdiv($this->money, 100,2),
            'ccy_no' => 'PHP',
            'busi_code' => $this->rechargeType,
            'pageUrl' => '',

        );
        $config_params = !empty($this->data['params']) ? json_decode($this->data['params'],true) : [];
        if(!empty($config_params) && isset($config_params['countryCode'])){
            $data['countryCode'] = $config_params['countryCode'];
        }
        if(!empty($config_params) && isset($config_params['currencyCode'])){
            $data['currencyCode'] = $config_params['currencyCode'];
        }
        if(!empty($config_params) && isset($config_params['paymentType'])){
            $data['paymentType'] = $this->rechargeType ?? $config_params['paymentType'];
        }
        
        $data['notifyUrl'] = $this->payCallbackDomain . '/pay/callback/xpay';

        $data['sign']    = $this->sign($data);

        $this->parameter = $data;
        $this->payUrl   .= '/ty/orderPay';
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
        $status       = isset($result['status']) ? $result['status'] : 'FAIL';
        $message    = isset($result['err_msg']) ? $result['err_msg'] : 'errorMsg:'.(string)$this->re;
        if ($this->http_code  == 200) {
            //下单成功，跳转支付链接 The order is successfully placed, jump to the payment link
            if($status == 'SUCCESS'){
                $code = 0;
                $targetUrl = $result['order_data'];
            }else{
                $code = 1;
                $targetUrl = '';
            }

            $this->return['code']    = $code;
            $this->return['msg']     = $message;
            $this->return['way']     = 'jump';
            $this->return['str']     = $targetUrl;
            $this->return['pay_no']  = $result['order_no'] ?? '';
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
        $config    = Recharge::getThirdConfig('xpay');
        $this->pubKey = $config['pub_key'];


        $params = [
            'busi_code' => $param['busi_code'],
            'err_code' => $param['err_code'],
            'err_msg' => $param['err_msg'],
            'mer_no' => $param['mer_no'],
            'mer_order_no' => $param['mer_order_no'],//商户唯一订单号
            'order_amount' => $param['order_amount'],//订单金额 2位小数 元
            'order_no' => $param['order_no'],//第三方订单号
            'order_time' => $param['order_time'],
            'pay_amount' => $param['pay_amount'],//支付金额
            'pay_time' => $param['pay_time'],
            'status' => $param['status'],//SUCCESS：成功 FAIL:失败
            'sign' => $param['sign'],
        ];

        $res = [
            'status'        => 0,
            'order_number'  => $param['mer_order_no'],
            'third_order'   => $param['order_no'],
            'third_money'   => bcmul($param['pay_amount'],100),
            'third_fee'     => 0,
            'error'         => '',
        ];

        //检验状态
        if ($this->signVerify($params)) {
            if($param['status'] === 'SUCCESS')
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
            $str .= $k.'='.$v.'&';
        }
        $str = trim($str, '&');
        $prikey = "-----BEGIN RSA PRIVATE KEY-----\n".wordwrap($this->key, 64, "\n", true)."\n-----END RSA PRIVATE KEY-----";
        $sign='';
        $key = openssl_pkey_get_private($prikey);
        foreach (str_split($str, 117) as $temp) {
            openssl_private_encrypt($temp, $encrypted, $key);
            $sign .= $encrypted;
        }

        $sign = base64_encode($sign) ;
        $sign = urlencode($sign) ;
        return $sign;
    }


    //回调校验签名
    public function signVerify($data) {
        $sign = $data['sign'];
        unset($data['sign']);
        ksort($data);
        reset($data);

        $str = '';
        foreach ($data as $k => $v){
            if(is_null($v) || $v === '') continue;
            $str .= $k.'='.$v.'&';
        }

        $str .= 'key='.$this->pubKey;

        if($sign == md5($str)){
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
        $config     = Recharge::getThirdConfig('XPAY');
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
