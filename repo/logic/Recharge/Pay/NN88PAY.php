<?php

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;

/**
 *
 * NN88PAY
 * @author
 */
class NN88PAY extends BASES {
    public $http_code;

    static function instantiation() {
        return new NN88PAY();
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
            'business_type'     => '20011',
            'mer_order_no'      => $this->orderID,
            'order_price'       => bcdiv($this->money, 100),
            'notify_url'        => $this->payCallbackDomain . '/pay/callback/nn88pay',
            'user_id'           => $this->userId,
            'datetime'          => date('Y-m-d H:i:s', $time),
            'timestamp'         => $time,
            'bank_id'           => 'GCash',
        );
        if(!is_null($this->rechargeType) && $this->rechargeType != ''){
            $data['pay_type'] = $this->rechargeType;
        }

        $data['sign']    = $this->sign($data);
        $this->parameter = $data;
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

        $sign_str       = $str .'&key='. $this->key;
        $sign           = strtolower(md5($sign_str));
        return $sign;

    }

    //处理结果
    public function parseRE() {
        $result     = json_decode($this->re, true);
        $code       = isset($result['code']) ? $result['code'] : 1;
        $message    = isset($result['msg']) ? $result['msg'] : 'errorMsg:'.(string)$this->re;

        if ($this->http_code  == 200) {
            //下单成功，跳转支付链接 The order is successfully placed, jump to the payment link
            if($code == 0){
                $targetUrl =  urldecode($result['pay_url']) ;
            }else{
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

    function getParamsBody($data, $key){
        $res = $this->des3Encrypt(json_encode($data),$key);
        return base64_encode($res);
    }

    public function formPost() {
        $ch = curl_init();
        $params_body = $this->getParamsBody($this->parameter, $this->key);
        $params_data = [
            'mcode' => $this->partnerID,
            'body'  => $params_body
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
        $config    = Recharge::getThirdConfig('nn88pay');
        $this->key = $config['key'];
        $body      = $param['body'];

        if($param['mcode'] != $config['partner_id']){
            throw new \Exception("Incorrect mcode: {$param['mcode']}");
        }

        $data = $this->des3Decrypt($body,$this->key);
        $param = json_decode($data, true);
        $params = [
            "order_no"        => $param['order_no'],//第三方订单号
            "trade_price"     => bcmul($param['trade_price'],1,2),//实际付款金额
            "mer_order_no"    => $param['mer_order_no'],//我们订单号
            "order_price"     => bcmul($param['order_price'],1,2),//订单金额
            "pay_status"      => $param['pay_status'],
            "notify_status"   => $param['notify_status'],//1、 未通知 2、 通知成功 3、 通知失败
            "notify_url"      => $param['notify_url'],
            "pay_time"        => $param['pay_time'],
            "timestamp"       => $param['timestamp'],
            "business_type"   => $param['business_type'],
            "sign"            => $param['sign'],
        ];

        $res = [
            'status'        => 0,
            'order_number'  => $param['mer_order_no'],
            'third_order'   => $param['order_no'],
            'third_money'   => bcmul($param['trade_price'] , 100),
            'third_fee'     => 0,
            'error'         => '',
        ];

        if ($param['sign'] == $this->sign($params)) {
            if($param['business_type'] == '10003'){
                //付款状态：1、 待付款 2、 支付成功 3、 支付失败
                if($param['pay_status'] === 2){
                    $res['status'] = 1;
                    return $res;
                }
            }else{
                throw new \Exception("Incorrect business_type: {$param['business_type']}");
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
        $config     = Recharge::getThirdConfig('nn88pay');
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
        $des_iv = substr($des_key,0,8);
        $res = base64_encode(openssl_encrypt($str, 'des-ede3-cbc', $des_key, OPENSSL_RAW_DATA, $des_iv));
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
        $des_iv = substr($des_key,0,8);
        $str = base64_decode($str);
        $res = openssl_decrypt(base64_decode($str), 'des-ede3-cbc', $des_key, OPENSSL_RAW_DATA, $des_iv);
        return $res;
    }

    public function bankList(){
        $config     = Recharge::getThirdConfig('NN88PAY');
        $this->key       = $config['key'];
        $this->partnerID = $config['partner_id'];
        //请求参数 Request parameter
        $data = array(
            'business_type' => '10005',
            'timestamp'     => time(),
        );

        $data['sign']    = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl    = $config['payurl'];

        $this->formPost();
        $this->addStartPayLog();

        $result  = json_decode($this->re, true);
        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:'.(string)$this->re;

        if ($this->http_code == 200) {
            if($code == 0){
                return $result['bank_list'];
            }
        }

        throw new \Exception('http_code:'.$this->http_code.' code:'.$code.' message:'.$message);
    }
}
