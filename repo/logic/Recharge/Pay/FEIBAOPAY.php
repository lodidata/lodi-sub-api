<?php

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;

/**
 *
 * FEIBAOPAY
 * @author
 */
class FEIBAOPAY extends BASES {
    public $http_code;

    static function instantiation() {
        return new FEIBAOPAY();
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
        list($usec, $sec) = explode(" ", microtime());
        $time = ((float)$usec + (float)$sec);
        $data = array(
            'gateway'               => 'gcash',
            'device'                => 'desktop',
            'merchant_order_num'    => $this->orderID,
            'amount'                => bcdiv($this->money, 100, 2),
            'callback_url'          => $this->payCallbackDomain . '/pay/callback/feibaopay',
            'merchant_order_time'   => $time,
            'merchant_order_remark' => 'recharge',
            'uid'                   => $this->userId,
            'user_ip'               => \Utils\Client::getIp(),
        );
        if(!is_null($this->rechargeType) && $this->rechargeType != ''){
            $data['gateway'] = $this->rechargeType;
            if($data['gateway'] == 'bank'){
                $data['bank_code'] = 'UBP';
            }
        }

        $data['sign']    = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl   .= '/v3/deposit';
    }

    //生成签名
    public function sign($data) {
        unset($data['sign']);
        ksort($data);

        $sign = sha1(json_encode($data));
        return $sign;
    }
    //生成签名
    public function return_sign($data) {
        unset($data['sign']);
        ksort($data);

        $sign = sha1(json_encode($data, JSON_UNESCAPED_SLASHES));
        return $sign;
    }

    //处理结果
    public function parseRE() {
        $result     = json_decode($this->re, true);
        $code       = isset($result['code']) ? $result['code'] : 1;
        $message    = isset($result['msg']) ? $result['msg'] : 'errorMsg:'.(string)$this->re;

        if ($this->http_code  == 200) {
            //下单成功，跳转支付链接 The order is successfully placed, jump to the payment link
            $return_code = 886;
            $targetUrl = '';
            if($code == 0){
                $order = $this->des3Decrypt($result['order'],$this->key,$this->pubKey);
                $order = json_decode($order, true);
                if(!empty($order) && $order['status'] == 'waiting'){
                    $return_code = 0;
                    $targetUrl = $order['navigate_url'];
                }
            }

            $this->return['code']    = $return_code;
            $this->return['msg']     = $message;
            $this->return['way']     = 'jump';
            $this->return['str']     = $targetUrl;
            $this->return['pay_no']  = '';
        } else{
            $this->return['code'] = 886;
            $this->return['msg'] = 'http_code:' . $this->http_code;
            $this->return['way'] = 'jump';
            $this->return['str'] = $this->re;
        }

    }

    function getParamsBody($data, $key, $vi){
        $res = $this->des3Encrypt(json_encode($data),$key, $vi);
        return $res;
    }

    public function formPost() {
        $ch = curl_init();
        $params_body = $this->getParamsBody($this->parameter, $this->key, $this->pubKey);
        $params_data = [
            'merchant_slug' => $this->partnerID,
            'data'  => $params_body
        ];
        $params_data = json_encode($params_data, JSON_UNESCAPED_UNICODE);
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
        $config    = Recharge::getThirdConfig('feibaopay');
        $this->key = $config['key'];
        $this->pubKey = $config['pub_key'];

        if($param['merchant'] != $config['partner_id']){
            throw new \Exception("Incorrect partner_id: {$param['merchant_slug']}");
        }
        if($param['code'] != 0){
            throw new \Exception("Incorrect message: {$param['msg']}");
        }
        $order = $param['order'];
        $order = $this->des3Decrypt($order,$this->key,$this->pubKey);
        $arr_order = json_decode($order, true);

        $res = [
            'status'        => 0,
            'order_number'  => $arr_order['merchant_order_num'],
            'third_order'   => $arr_order['merchant_order_num'],
            'third_money'   => $arr_order['amount'] * 100,
            'third_fee'     => 0,
            'error'         => '',
        ];

        if ($arr_order['sign'] == $this->return_sign($arr_order)) {
            if($arr_order['status'] == 'success'){
                $res['status'] = 1;
                return $res;
            }else{
                throw new \Exception('unpaid');
            }
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
        $config     = Recharge::getThirdConfig('feibaopay');
        $this->key  = $config['key'];

        //请求参数 Request parameter
        $data = array(
            'business_type'   => '10004',
            'mer_order_no'    => $order_number,
            'timestamp'       => time(),
        );

        $data['sign']    = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl    = $config['payurl'];
        $this->partnerID = $config['partner_id'];

        $this->formPost();
        //json_decode之后 会把trade_price 500.00 变成 500 如果我们验证签名就会通不过
        $this->addStartPayLog();

        $result  = json_decode($this->re, true);
        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:'.(string)$this->re;

        if ($this->http_code == 200) {
            if($code == 0){
                //未支付
                if($result['pay_status'] !== 2){
                    throw new \Exception($result['pay_status']);
                }
                $res = [
                    'status'       => $result['pay_status'],
                    'order_number' => $result['orderNo'],
                    'third_order'  => $result['tradeNo'],
                    'third_money'  => bcmul($result['trade_price'] , 100),
                ];
                return $res;
            }
        }

        throw new \Exception('http_code:'.$this->http_code.' code:'.$code.' message:'.$message);
    }

    /**
     * 加密
     * @param $str
     * @param string $des_key
     * @param string $des_iv
     * @return string
     */
    function des3Encrypt($str, $des_key="", $des_iv = '')
    {
        $res = base64_encode(openssl_encrypt($str, 'AES-256-CBC', $des_key, OPENSSL_RAW_DATA, $des_iv));
        return $res;
    }

    /**
     * 解密
     * @param $str
     * @param string $des_key
     * @param string $des_iv
     * @return false|string
     */
    function des3Decrypt($str, $des_key="", $des_iv= '')
    {
        $str = base64_decode($str);
        $res = openssl_decrypt($str, 'AES-256-CBC', $des_key, OPENSSL_RAW_DATA, $des_iv);
        return $res;
    }
}
