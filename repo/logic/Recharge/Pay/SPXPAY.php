<?php

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;
use Exception;
use Model\FundsDeposit;

/**
 *
 * SPXPAY
 */
class SPXPAY extends BASES {
    public $http_code;

    static function instantiation() {
        return new SPXPAY();
    }

    //与第三方交互
    public function start() {
        $this->initParam();  // 数据初始化
        $this->formPost();  // 发送请求
        $this->parseRE();  // 处理结果
    }

    //组装数组
    public function initParam() {
        $rechargeType = 'GCash';
        if(!empty($this->rechargeType)){
            $rechargeType = $this->rechargeType;
        }
        $data = [
            'merchant_order_no' => $this->orderID,
            'merchant_req_time' => date("YmdHis"),
            'order_amount' => bcdiv($this->money,100),
            'trade_summary' => 'I am trade summary',
            'pay_type' => $rechargeType,
            'user_terminal' => 'PC',
            'user_ip' => '127.0.0.1',
            'currency' => 'PHP',
            'third_user_id' => '',
            'back_notice_url' => urlencode($this->payCallbackDomain . '/pay/callback/spxpay'),
            'merchant_param' => urlencode("merchant_order_no={$this->orderID}"),
        ];

        $this->parameter = $data;
        $this->payUrl   .= "/api/{$this->partnerID}/pay/order/create?trace_id=" . $this->getTraceId();
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
            if($result['code'] == 'SUCCESS') {
                $code = 0;
            } else {
                $code = 1;
            }
            $targetUrl = $result['pay_url'];
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
    public function returnVerify($stdInput = [])
    {
        $config       = Recharge::getThirdConfig('spxpay');
        if(empty($config)) {
            throw new Exception('pay type error');
        }
        $this->partnerID        = $config['partner_id'];
        $this->payUrl           = $config['payurl'];
        $this->key              = $config['key'];
        $this->pubKey           = $config['pub_key'];

        if (empty($stdInput['sign']) || empty($stdInput['content'])) {
            throw new \Exception('sign is wrong');
        }

        $content = $this->rsaDecrypt($stdInput['content']);
        if (empty($content)) {
            throw new \Exception('sign is wrong');
        }

        parse_str($content, $param);

        $params = $param;
        $deposit = FundsDeposit::where('trade_no', '=', $params['merchant_order_no'])->first();
        $res = [
            'status' => 0,
            'order_number' => $params['merchant_order_no'],
            'third_order' => $params['platform_order_no'],
            'third_money' => $deposit['money'],
            'third_fee' => 0,
            'error' => '',
        ];

        if (isset($param['order_status']) && $param['order_status'] == 'Success') {
            $res['status'] = 1;
        }

        return $res;
    }

    public function getPublicKey(): string
    {
        $pem = "-----BEGIN PUBLIC KEY-----" . PHP_EOL;

        $pem .= chunk_split($this->pubKey, 64, PHP_EOL);

        $pem .= "-----END PUBLIC KEY-----" . PHP_EOL;
        return $pem;
    }

    public function getPrivateKey(): string
    {
        //获取完整的私钥
        $pem = "-----BEGIN RSA PRIVATE KEY-----" . PHP_EOL;

        $pem .= chunk_split($this->key, 64, PHP_EOL);

        $pem .= "-----END RSA PRIVATE KEY-----" . PHP_EOL;
        return $pem;
    }


    /**
     * 补单
     * @param $order_number
     * @return mixed
     */
    public function supplyOrder($order_number, $payNo = '')
    {
        return;
    }

    private function getTraceId(): string
    {
        $randomStrings = [];

        for ($i = 0; $i < 4; $i++) {
            $randomString = bin2hex(random_bytes(10)); // 生成20个字节（160位）的随机字节串，并将其转换为十六进制表示
            $randomStrings[] = $randomString;
        }
        return substr(implode("", $randomStrings), 0, 20);
    }

    function arrayToQueryString($data): string
    {
        $str = [];
        foreach ($data as $k => $v) {
            if (empty($v)) {
                continue;
            }

            $str[] = "{$k}={$v}";
        }
        return implode("&", $str);
    }

    private function sslSign($data): string
    {
        openssl_sign($data, $signedStr, openssl_get_privatekey($this->getPrivateKey()));
        return base64_encode($signedStr);
    }

    private function sslSignVerify($data, $decodedSignature): bool
    {
        return openssl_verify($data, $decodedSignature, $this->getPublicKey());
    }

    // 加密数据
    private function rsaEncrypt($data): string
    {
        // 将PEM格式的公钥转换为PHP能识别的格式
        $publicKey = openssl_pkey_get_public($this->getPublicKey());
        // 对数据进行加密
        openssl_public_encrypt($data, $encryptedData, $publicKey);
        // 将加密后的数据进行Base64编码
        return base64_encode($encryptedData);
    }

    // 解密数据
    private function rsaDecrypt($encryptedData): string
    {
        // 将PEM格式的私钥转换为PHP能识别的格式
        $privateKey = openssl_pkey_get_private($this->getPrivateKey());
        // 将Base64编码的加密数据进行解码
        $encryptedData = base64_decode($encryptedData);
        // 对数据进行解密
        openssl_private_decrypt($encryptedData, $data, $privateKey);
        return $data;
    }


    private function formPost()
    {
        $params = $this->parameter;
        $url = $this->payUrl;
        $urlParams = $this->arrayToQueryString($params);
//        echo $urlParams . PHP_EOL;
        $content = $this->rsaEncrypt($urlParams);
        $sign = $this->sslSign($urlParams);
        $data = [
            "sign" => $sign,
            "content" => $content,
        ];
        $this->parameter = array_merge($this->parameter, $data);
//        echo $this->payUrl,PHP_EOL;
//        echo json_encode($data, JSON_UNESCAPED_UNICODE),PHP_EOL;

        // 转换为 JSON 格式
        $jsonData = json_encode($data);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时时间为10秒
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonData);

        // 发送请求并获取响应
        $this->re = $response = curl_exec($curl);
        $this->http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        // 检查是否有错误发生
        if ($response === false) {
            $error = curl_error($curl);
            // 处理错误
            throw new Exception("读取response为空异常");
        }

        // 关闭 cURL 请求
        curl_close($curl);
        $responseJson = json_decode($response, true);
//        print_r($responseJson);die;

        if (empty($responseJson) || empty($responseJson["code"]) || empty($responseJson["message"])) {
            throw new Exception("回包json解释异常 url:" . $url . " resp:" . $response);
        }

        if ($responseJson["code"] != "SUCCESS") {
            throw new Exception("提交异常 code:{$responseJson["code"]}  msg:{$responseJson["message"]}");
        }

        if (empty($responseJson["sign"]) || empty($responseJson["content"])) {
            throw new Exception("回包json解释异常, sign或者content为空:" . $response);
        }

        $content = $this->rsaDecrypt($responseJson['content']);
        if (empty($content)) {
            throw new Exception("回包json解释异常, content解码失败");
        }

        if (!$this->sslSignVerify($content, base64_decode($responseJson['sign']))) {
            throw new Exception("回包json解释异常, 验签失败");
        }

        parse_str($content, $output);
        $this->re = json_encode(array_merge($responseJson, $output), JSON_UNESCAPED_UNICODE);
    }
}