<?php

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;

/**
 *
 * EQPAY
 * @author 
 */
class EQPAY extends BASES {
    public $http_code;

    static function instantiation() {
        return new EQPAY();
    }

    //与第三方交互
    public function start() {
        $this->initParam();  // 数据初始化
        $this->formPost();  // 发送请求
        $this->parseRE();  // 处理结果
    }

    //组装数组
    public function initParam() {
        $data = array(
            'channel_code' => $this->rechargeType,
            'username' => $this->partnerID,
            'amount' => bcdiv($this->money, 100,2),
            'order_number' => $this->orderID,
            'client_ip' => $this->clientIp,
        );

        $data['notify_url'] = $this->payCallbackDomain . '/pay/callback/eqpay';

        $data['sign']    = $this->sign($data);

        $this->parameter = $data;
        $this->payUrl   .= '/api/v1/third-party/create-transactions';
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
        $code       = isset($result['http_status_code']) ? $result['http_status_code'] : 1;
        $message    = isset($result['message']) ? $result['message'] : 'errorMsg:'.(string)$this->re;
        
        if ($this->http_code  == 200) {
            //下单成功，跳转支付链接 The order is successfully placed, jump to the payment link
            if( in_array($code, ['200', '201']) ){
                $code = 0;
                $targetUrl = $result['data']['casher_url'];
            }else{
                $targetUrl = '';
            }

            $this->return['code']    = $code;
            $this->return['msg']     = $message;
            $this->return['way']     = 'jump';
            $this->return['str']     = $targetUrl;
            $this->return['pay_no']  = $result['data']['system_order_number'] ?? '';
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
        $config    = Recharge::getThirdConfig('eqpay');
        $this->pubKey = $config['pub_key'];

        $res = [
            'status'        => 0,
            'order_number'  => $param['order_number'],
            'third_order'   => $param['system_order_number'],
            'third_money'   => $param['amount'] * 100,
            'third_fee'     => 0,
            'error'         => '',
        ];

        //检验状态
        if ($this->signVerify($param)) {
            if( in_array( $param['status'], [4,5] )) {
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
        $data['secret_key'] = $this->key;
        $str = '';
        foreach ($data as $k => $v){
            if(is_null($v) || $v === '') continue;
            $str .= $k.'='.$v.'&';
        }
        $str = trim($str, '&');
        //$str .= 'secret_key='.$this->key;
        $sign = md5($str);
        return $sign;
    }


    //回调校验签名
    public function signVerify($data) {
        $data_sign = $data['sign'];
        unset($data['sign']);
        ksort($data);
        reset($data);
        $data['secret_key'] = $this->pubKey;
        $str = '';
        foreach ($data as $k => $v){
            if(is_null($v) || $v === '') continue;
            $str .= $k.'='.$v.'&';
        }

        $str = trim($str, '&');

        $sign = md5($str);

        if( $sign == $data_sign ){
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
        $config     = Recharge::getThirdConfig('EQPAY');
        $this->key  = $config['key'];
        $data = array(
            'username' => $config['partner_id'],//    是   string  商户号 business number
            'order_number' => $order_number,
        );

        $data['sign']    = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl    = $config['payurl'].'/api/v1/third-party/transaction-queries';

        $this->formPost();
        $this->addStartPayLog();

        $result  = json_decode($this->re, true);
        $code       = isset($result['http_status_code']) ? $result['http_status_code'] : 1;
        $message    = isset($result['message']) ? $result['message'] : 'errorMsg:'.(string)$this->re;

        if ($this->http_code == 200) {
            if( in_array($code, ['200', '201']) ){
                //未支付
                if( !in_array($result['data']['status'], [4,5]) ){
                    throw new \Exception($result['data']['status']);
                }
                $res = [
                    'status'       => $result['data']['status'],
                    'order_number' => $result['data']['order_number'],
                    'third_order'  => $result['data']['system_order_number'],
                    'third_money'  => $result['data']['amount'] * 100,
                ];
                return $res;
            }
        }

        throw new \Exception('http_code:'.$this->http_code.' code:'.$code.' message:'.$message);
    }
}
