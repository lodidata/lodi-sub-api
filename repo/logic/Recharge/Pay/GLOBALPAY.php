<?php

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;

class GLOBALPAY extends BASES {
    public $http_code;

    static function instantiation() {
        return new GLOBALPAY();
    }

    //与第三方交互
    public function start() {
        $this->initParam();  // 数据初始化
        $this->formPost();   // 发送请求
        $this->parseRE();    // 处理结果
    }

    //组装数组
    public function initParam() {
        $currencyCode = "PHP";
        $config_params = !empty($this->data['params']) ? json_decode($this->data['params'],true) : [];
        if(!empty($config_params) && isset($config_params['currency_code'])){
            $currencyCode = $config_params['currency_code'];
        }

        $rechargeType = 101202;
        if(!empty($this->rechargeType)){
            $rechargeType = $this->rechargeType;
        }
        $data = array(
            'mer_no'        => $this->partnerID,
            'mer_order_no'  => $this->orderID,
            'pname'         => "test",
            'pemail'        => "test@gmail.com",
            'phone'         => "13122336688",
            'order_amount'  => bcdiv($this->money, 100, 2),
            'ccy_no'        => $currencyCode,
            'busi_code'     => $rechargeType,
            'notifyUrl'     => $this->payCallbackDomain . '/pay/callback/globalpay',
            'pageUrl'       => $this->returnUrl ?? 'no return'
        );
        $data['sign'] = $this->sign($data);

        $this->parameter = $data;
        $this->payUrl   .= '/ty/orderPay';
    }

    public function formPost() {
        $params_data = json_encode($this->parameter, JSON_UNESCAPED_UNICODE);
        $ch = curl_init();
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

    //处理结果
    public function parseRE() {
        $result     = json_decode($this->re, true);
        $status     = isset($result['status']) ? $result['status'] : 'SUCCESS';
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
            $this->return['pay_no']  = $this->orderID;

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
        if(!isset($param['status']) || $param['status'] != "SUCCESS"){
            throw new \Exception('unpaid');
        }
        $params = $param;

        $res = [
            'status'        => 0,
            'order_number'  => $params['mer_order_no'],
            'third_order'   => $params['order_no'],
            'third_money'   => $params['order_amount'] * 100,
            'third_fee'     => 0,
            'error'         => '',
        ];

        //检验状态
        if($this->makeMd5Sign($param, $param['sign'])){
            if($params['status'] == "SUCCESS")
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
    function sign($data)
    {
        unset($data['sign']);

        ksort($data);
        $str = '';
        foreach ($data as $k => $v){
            if(!empty($v)){
                $str .= $k.'='.$v.'&';
            }
        }
        $str = rtrim($str,'&');

        $pem = chunk_split($this->key, 64, "\n");
        $pem = "-----BEGIN RSA PRIVATE KEY-----\n" . $pem . "-----END RSA PRIVATE KEY-----\n";
        $private_key = openssl_pkey_get_private($pem);

        $crypto = '';
        foreach (str_split($str, 117) as $chunk) {
            openssl_private_encrypt($chunk, $encryptData, $private_key);
            $crypto .= $encryptData;
        }
        $encrypted = base64_encode($crypto);
        $return = str_replace(array('+','/','='),array('-','_',''),$encrypted);

        return $return;
    }

    /**
     * 私钥解密
     * @param string $data 要解密的数据
     * @return bool $bool 解密后的字符串
     */
    function makeMd5Sign($data, $sign){
        $config    = Recharge::getThirdConfig('globalpay');

        unset($data['sign']);

        ksort($data);
        $str = '';
        foreach ($data as $k => $v){
            if(!empty($v)){
                $str .= $k.'='.$v.'&';
            }
        }

        $str .='key='.$config['pub_key'];

        if($sign != md5($str)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 补单
     * @param $order_number
     * @return mixed
     */
    public function supplyOrder($order_number, $payNo = '')
    {

    }

}