<?php

namespace Logic\Transfer\ThirdParty;

use Exception;

class SPXPAYMAYAQXPAY extends BASES {
    private $httpCode = '';

    //回调，不处理逻辑
    public function callbackMsg() {
        return 'SUCCESS';
    }

    //请求代付接口
    public function runTransfer() {
        //组装参数
        $data = [
            'merchant_order_no' => $this->orderID,
            'merchant_req_time' => date("YmdHis"),
            'order_amount'      => bcdiv($this->money, 100, 2),
            'trade_summary'     => 'I am trade summary',
            'settle_type'       => json_decode($this->thirdConfig['params'], 1)['settle_type'],
            'bank_account_no'   => $this->bankCard,
            'bank_code'         => $this->bankCode,
            'bank_account_name' => $this->bankUserName,
            'request_ip'        => '127.0.0.1',
            'bank_name'         => $this->bankCard,
            'currency'          => 'PHP',
            'order_reason'      => '打钱',
            'back_notice_url'   => urlencode($this->payCallbackDomain . '/thirdAdvance/callback/spxpaymayaqxpay'),
            'merchant_param'    => urlencode("merchant_order_no={$this->orderID}"),
        ];
        $this->payUrl    .= "/api/{$this->partnerID}/settle/order/create?trace_id=" . $this->getTraceId();
        $this->parameter = $data;
        $this->basePostNew();
        $result = json_decode($this->re, true);

        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;
        if($this->httpCode == 200) {
            if ($result['code'] == 'SUCCESS') {
                $this->return['code'] = 10500;
                $this->return['balance'] = $this->money;
                $this->return['msg'] = $message;
                $this->transferNo = $result['platform_order_no'];//第三方订单号
                //成功就直接返回了
            } else {
                $message = 'http_code:' . $this->httpCode . 'errorMsg:' . json_encode($result, JSON_UNESCAPED_UNICODE);
                $this->updateTransferOrder($this->money, 0, '', '', 'failed', null, $message);
                $this->return['code'] = 886;
                $this->return['balance'] = 0;
                $this->return['msg'] = 'SPXPAYMAYAQXPAY:' . $message ?? '代付失败';
            }
            return;
        }

        $this->return['code']    = 886;
        $this->return['balance'] = $this->money;
        $this->return['msg']     = $message;
        $this->transferNo        = '';//第三方订单号
    }

    //查询余额
    public function getThirdBalance() {
        //组装参数
        $data = [
            'currency' => "PHP",
        ];
        $this->payUrl    .= "/api/{$this->partnerID}/balance/query?trace_id=" . $this->getTraceId();
        $this->parameter = $data;
        $this->basePostNew();
        $result  = json_decode($this->re, true);
        $code    = isset($result['status']) ? $result['status'] : 1;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;

        if($result['code'] == 'SUCCESS') {
            $this->return['code']    = 10509;
            $this->return['balance'] = bcmul($result['balance'] ?? 0, 100);
            $this->return['msg']     = $message;
            return;
        }

        $this->_end();
        throw new \Exception('http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message);
    }

    //查询代付结果
    public function getTransferResult() {
        $data         = [
            'platform_order_no' => $this->transferNo,
        ];
        $this->payUrl    .= "/api/{$this->partnerID}/settle/order/query?trace_id=" . $this->getTraceId();
        $this->parameter = $data;
        $this->basePostNew();

        $result = json_decode($this->re, true);

        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;

        if($this->httpCode == 200) {
            //订单状态 status (1 创建订单成功 2 代收/代付成功  3 失败)
            if($result['order_status'] == 'Success') {
                $status       = 'paid';
                $this->return = ['code' => 1, 'msg' => $message, 'balance' => null];
            } elseif($result['order_status'] == 'Fail') {
                $status       = 'failed';
                $this->return = ['code' => 0, 'msg' => $message, 'balance' => null];
            } else {
                $this->return = ['code' => 0, 'msg' => $message, 'balance' => null];
                return;
            }
            $this->updateTransferOrder($this->money, $this->money, $result['platform_order_no'],//第三方转账编号
                '', $status, 0,$message);
            return;
        }
        $this->return = ['code' => 886, 'transfer' => '', 'msg' => 'http_code:' . $this->httpCode . ' message:' . $message, 'balance' => null];
    }

    private function basePostNew()
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
//        echo $this->payUrl, PHP_EOL;
//        echo json_encode($this->parameter, JSON_UNESCAPED_UNICODE), PHP_EOL;
//        print_r($this->parameter);die;

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
        $this->http_code = $this->httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        // 检查是否有错误发生
        if ($response === false) {
            $error = curl_error($curl);
            // 处理错误
            throw new Exception("读取response为空异常");
        }

        // 关闭 cURL 请求
        curl_close($curl);
        $this->re = $responseJson = json_decode($response, true);

        if (empty($responseJson) || empty($responseJson["code"]) || empty($responseJson["message"])) {
            throw new Exception("回包json解释异常 url:" . $url . " resp:" . $response);
        }

        if ($responseJson["code"] != "SUCCESS") {
//            echo "提交异常 code:{$responseJson["code"]}  msg:{$responseJson["message"]}";die;
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

    /**
     * 解密数据
     * @param $params
     * @return array
     * @throws Exception
     */
    public function dataDecrypt($params): array
    {
        if (empty($params['sign']) || empty($params['content'])) {
            throw new \Exception('Sign error');
        }
        $content = $this->rsaDecrypt($params['content']);
        if (empty($content)) {
            throw new \Exception('Sign error');
        }
        parse_str($content, $output);
        return array_merge($params, $output);
    }

    /**
     * 代付异步回调
     * @param array $params 异步参数
     * @return mixed
     */
    public function callbackResult($params) {
        $this->parameter = $params;
        if (empty($params['sign']) || empty($params['content'])) {
            throw new \Exception('Sign error');
        }

        $content = $this->rsaDecrypt($params['content']);
        if (empty($content)) {
            throw new \Exception('Sign error');
        }

        parse_str($content, $output);

        //订单状态已经成功 或者 消息与订单状态一致
        if($this->order['status'] == 'paid') {
            return;
        }

        //订单状态付款状态：(：2 - 成功 : 3 – 失敗。)
        if($this->parameter['order_status'] == 'Success') {
            $status       = 'paid';
            $this->return = ['code' => 1, 'msg' => ''];
        } elseif($this->parameter['order_status'] == 'Fail') {
            $status       = 'failed';
            $this->return = ['code' => 0, 'msg' => '代付失败'];
        } else {
            $this->return = ['code' => 0, 'msg' => 'error'];
            return;
        }

        $this->re = $this->return;
        $this->updateTransferOrder($this->money, $this->money, $params['platform_order_no'],//第三方转账编号
            '', $status);
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

    /**
     * 银行名称
     * @return mixed
     */
    private function getBankName() {

        $banks = [];
        return $banks[$this->bankCode];
    }
}
