<?php

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\Utils;

/**
 *
 * antpay
 */
class ANTPAY extends BASES {
    public $http_code;

    static function instantiation() {
        return new ANTPAY();
    }

    //与第三方交互
    public function start() {
        $this->initParam();  // 数据初始化
        $this->formPost();  // 发送请求
        $this->parseRE();  // 处理结果
    }

    //组装数组
    public function initParam() {
        $merchant_key = '';
        $config_params = !empty($this->data['params']) ? json_decode($this->data['params'],true) : [];
        if(!empty($config_params) && isset($config_params['merchant_key'])){
            $merchant_key = $config_params['merchant_key'];
        }
        //请求参数 Request parameter
        // 支付方式,101202:菲律宾gcash钱包,101204:菲律宾手机号支付,101205:菲律宾maya钱包
        $rechargeType = 101202;
        if(!empty($this->rechargeType)){
            $rechargeType = $this->rechargeType;
        }
        $data = array(
            'merchant_id'      => $this->partnerID,
            'order_no'         => $this->orderID,
            'amount'           => bcdiv($this->money,100),
            'notify_url'       => $this->payCallbackDomain . '/pay/callback/antpay',
            'pay_type'         => $rechargeType,
            'return_url'       => $this->returnUrl ?? 'no return',
        );
        $data['sign'] = $this->buildRSASignByPrivateKey($this->sign($data, $merchant_key));

        $this->parameter = json_encode($data);
        $this->payUrl   .= '/api/addDeposit';
    }

    public function formPost() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->payUrl);
        // 执行后不直接打印出来
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // 设置请求方式为post
        curl_setopt($ch, CURLOPT_POST, true);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->parameter);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json; charset=utf-8'
        ]);
        // 请求头，可以传数组
        // curl_setopt($ch, CURLOPT_HEADER, $header);
        // curl_setopt($ch, CURLOPT_HEADER, 1);
        // 跳过证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // 不从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $output = curl_exec($ch);
        $this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $this->re = $output;
    }


    //处理结果
    public function parseRE() {
        $result     = json_decode($this->re, true);
        $status       = isset($result['status']) ? $result['status'] : '';
        //message返回数组，这里做特殊处理
        $message = 'errorMsg:'.(string)$this->re;
        if(isset($result['message'])){
            if(is_array($result['message'])){
                $message = json_encode($result['message']);
            }else{
                $message = $result['message'];
            }
        }
//        $message    = isset($result['message']) ? $result['message'] : 'errorMsg:'.(string)$this->re;
        if ($this->http_code  == 200) {
            //下单成功，跳转支付链接 The order is successfully placed, jump to the payment link
            if($status == 1){
                $code = 0;
                $targetUrl = $result['pay_url'];
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
        $config    = Recharge::getThirdConfig('antpay');
        $this->key = $config['key'];
        $this->pubKey = $config['pub_key'];
        if(empty($config['params'])){
            throw new \Exception('no params');
        }
        $cnf_param = json_decode($config['params'], true);
        $merchant_key = $cnf_param['merchant_key'];

        if(!isset($param['status']) || $param['status'] != 2){
            throw new \Exception('unpaid');
        }
        $params = $param;

        $res = [
            'status'        => 0,
            'order_number'  => $params['order_no'],
            'third_order'   => $params['trans_id'],
            'third_money'   => $params['amount'] * 100,
            'third_fee'     => 0,
            'error'         => '',
        ];

        //检验状态
        if($this->buildRSASignByPublicKey($this->sign($params,$merchant_key), $param['sign'])){
            if($params['status'] == 2)
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
    function sign($array, $merchant_key)
    {
        unset($array['sign']);
        unset($array['s']);
        $result = "";
        try {
            $keys = array_keys($array);
            sort($keys);
            $str = "";
            foreach ($keys as $key) {
                $val = $array[$key];
                if (!empty($val) && $key != "sign") {
                    $str .= $key . "=" . $val . "&";
                }
            }
            $str = $str . "key=" . $merchant_key;

            $result = $str;
        } catch (Exception $e) {
            return null;
        }
        return $result;
    }

    /**
     * 私钥加密
     * @param string $data 要加密的数据
     * @return 加密后的字符串
     */
    public function buildRSASignByPrivateKey($data)
    {
        //获取完整的私钥
        $pem = "-----BEGIN RSA PRIVATE KEY-----" . PHP_EOL;

        $pem .= chunk_split($this->key, 64, PHP_EOL);

        $pem .= "-----END RSA PRIVATE KEY-----" . PHP_EOL;
        $privateKey = openssl_pkey_get_private($pem);

        $privatekey = openssl_get_privatekey($privateKey);
        //php5.4+ OPENSSL_ALGO_SHA256
        openssl_sign($data, $result, $privatekey, OPENSSL_ALGO_SHA256);

        $result = base64_encode($result);

        return $result;
    }

    /**
     * 私钥解密
     * @param string $data 要解密的数据
     * @return bool $bool 解密后的字符串
     */
    public function buildRSASignByPublicKey($data, $sign)
    {
        $pem = "-----BEGIN PUBLIC KEY-----" . PHP_EOL;

        $pem .= chunk_split($this->pubKey, 64, PHP_EOL);

        $pem .= "-----END PUBLIC KEY-----" . PHP_EOL;

        $public_key = openssl_pkey_get_public($pem);
        $publicKey = openssl_get_publickey($public_key);

        $result = openssl_verify($data, base64_decode($sign), $publicKey, OPENSSL_ALGO_SHA256);
        if($result == 1){
            return true;
        }else{
            return false;
        }
    }


    /**
     * 补单
     * @param $order_number
     * @return mixed
     */
    public function supplyOrder($order_number, $payNo = '')
    {
        $config     = Recharge::getThirdConfig('antpay');
        $this->key  = $config['key'];

        //请求参数 Request parameter
        $data = array(
            'merchantId' => $config['partner_id'],//    是   string  商户号 business number
            'bizNum'    => $order_number,
        );

        $data['sign']    = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl    = $config['payurl'].'/pay/order/query/status';

        $this->formPost();
        $this->addStartPayLog();

        $result  = json_decode($this->re, true);
        $code    = isset($result['success']) ? $result['success'] : '';
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:'.(string)$this->re;

        if ($this->http_code == 200) {
            if($code === true){
                //未支付
                if($result['data']['status'] != 1){
                    throw new \Exception($result['data']['status']);
                }
                $res = [
                    'status'       => $result['data']['status'],
                    'order_number' => $result['data']['merchantBizNum'],
                    'third_order'  => $result['data']['sysBizNum'],
                    'third_money'  => $result['data']['money'],
                ];
                return $res;
            }
        }

        throw new \Exception('http_code:'.$this->http_code.' code:'.$code.' message:'.$message);
    }

}