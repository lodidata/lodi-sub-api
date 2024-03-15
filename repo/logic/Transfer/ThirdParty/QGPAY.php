<?php

namespace Logic\Transfer\ThirdParty;

use Logic\Recharge\Recharge;
use Utils\Curl;

/**
 *
 * QGPAY代付
 */
class QGPAY extends BASES {
    private $httpCode = '';
    private $sign;
    private $time;

    //回调，不处理逻辑
    public function callbackMsg() {
        return 'success';
    }

    //请求代付接口
    public function runTransfer() {
        $data          = [
            'merchant'     => $this->partnerID,
            'orderNo'      => $this->orderID,
            'businessCode' => '100019',
            'accNo'        => $this->bankCard,
            'accName'      => $this->bankUserName,
            'orderAmount'  => bcdiv($this->money, 100, 2),
            'bankCode'     => $this->getBankName(),
            'phone'        => '0123456789',
            'province'     => '',
            'identityNo'   => '',
            'identityType' => '',
            'notifyUrl'    => $this->payCallbackDomain . '/thirdAdvance/callback/qgpay',
            'remake'       => 'transfer',
        ];


        $this->parameter = $data;
        //判断 额外参数是否为空 Determine whether the extra parameter is empty
        $this->payUrl .= '/singleOrder';

        $this->initParam($data);
        $this->basePostNew();
        $result  = isset($this->re) ? json_decode($this->re, true) : '';
        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['message']) ? $result['message'] : '';
        if($this->httpCode == 200) {
            //true成功,false失败
            if($code === 0) {
                $this->return['code']    = 10500;
                $this->return['balance'] = $this->money;
                $this->return['msg']     = $message;
                $this->transferNo        = $result['data']['orderNo'];//第三方订单号
                //成功就直接返回了
                return;
            } else {
                $message = 'http_code:' . $this->httpCode . 'errorMsg:' . json_encode($result, JSON_UNESCAPED_UNICODE);
                $this->updateTransferOrder($this->money, 0, '', '', 'failed', null, $message);
                $this->return['code']    = 886;
                $this->return['balance'] = 0;
                $this->return['msg']     = 'QGPAY:' . $message ?? '代付失败';
                return;
            }
        }

        $this->return['code']    = 886;
        $this->return['balance'] = $this->money;
        $this->return['msg']     = $message;
        $this->transferNo        = '';//第三方订单号
    }

    //查询余额
    public function getThirdBalance() {
        $params        = [
            'merchant'  => $this->partnerID,
            'timestamp' => time().'000',
        ];
        $this->payUrl .= "/balanceOf";
        $this->initParam($params);
        $this->basePostNew();
        $result  = json_decode($this->re, true);
        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;
        if($this->httpCode == 200) {
            if($code == 0) {
                $balance = 0;
                foreach($result['data'] as $key => $val){
                    if($val['currency'] == 'PHP'){
                        $balance = bcmul($val['balance'],100);
                    }
                }
                $this->return['code']    = 10500;
                $this->return['balance'] = $balance;
                $this->return['msg']     = $message;
                return;
            }

        }
        $this->_end();
        throw new \Exception('http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message);
    }

    //代付订单查询
    public function getTransferResult() {

        $params       = [
            'merNo'      => $this->partnerID,
            'merOrderNo' => $this->orderID
        ];


        $this->payUrl .= "/singleQuery";
        $this->initParam($params);
        $this->basePostNew();

        $result  = json_decode($this->re, true);
        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)'';
        if($this->httpCode == 200) {
            if($code == 0) {
                $resultData = $result['data'];
                //订单状态： status Y代表成功，N代表申请中，Z代表失败
                if($resultData['status'] == 'Y') {
                    $status       = 'paid';
                    $this->return = ['code' => 1, 'msg' => $message];
                } elseif($resultData['status'] == 'Z') {
                    $status       = 'failed';
                    $this->return = ['code' => 0, 'msg' => $message];
                } elseif($resultData['status'] == 'N') {
                    $status       = "pending";
                    $this->return = ['code' => 0, 'msg' => $message];
                } else {
                    $this->return = ['code' => 0, 'msg' => $message];
                    return;
                }
                
                $real_money = bcmul($resultData['payamount'], 100);
                $fee        = $this->money - $real_money;
                $this->updateTransferOrder($this->money, $real_money, $resultData['orderNo'], '', $status, $fee);
                return;
            }
        }
        $this->return = ['code' => 886, 'transfer' => '', 'msg' => 'http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message];
    }

    //组装数组
    public function initParam($params = []) {
        $data            = $params;
        $data['sign']    = $this->pivate_key_encrypt($data);
        $this->parameter = $data;
    }

    function pivate_key_encrypt($data)
    {
        ksort($data);
        $str = '';
        foreach ($data as $k => $v){
            if(!empty($v)){
                $str .=(string) $k.'='.$v.'&';
            }
        }
        $str = rtrim($str,'&');
        //替换成自己的私钥
        $pem = chunk_split($this->key, 64, "\n");
        $pem = "-----BEGIN PRIVATE KEY-----\n" . $pem . "-----END PRIVATE KEY-----\n";
        $private_key = openssl_pkey_get_private($pem);
        $crypto = '';
        foreach (str_split($str, 117) as $chunk) {
            openssl_private_encrypt($chunk, $encryptData, $private_key);
            $crypto .= $encryptData;
        }
        $encrypted = base64_encode($crypto);
        $encrypted = str_replace(array('+','/','='),array('-','_',''),$encrypted);

        return $encrypted;
    }

    //验证回调签名
    function public_key_decrypt($sign, $public_key)
    {
        //替换自己的公钥
        $pem = chunk_split( $public_key,64, "\n");
        $pem = "-----BEGIN PUBLIC KEY-----\n" . $pem . "-----END PUBLIC KEY-----\n";
        $public_key = openssl_pkey_get_public($pem);

        $base64=str_replace(array('-', '_'), array('+', '/'), $sign);

        $crypto = '';
        foreach(str_split(base64_decode($base64), 128) as $chunk) {
            openssl_public_decrypt($chunk,$decrypted,$public_key);
            $crypto .= $decrypted;
        }

        return $crypto;
    }

    public function basePostNew($referer = null) {

        $this->payRequestUrl = $this->payUrl;
        $params_data = json_encode($this->parameter, JSON_UNESCAPED_UNICODE);
        $ch          = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->payUrl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length:' . strlen($params_data),
        ]);
        if($referer) {
            curl_setopt($ch, CURLOPT_REFERER, $referer);
        }
        $response        = curl_exec($ch);
        $this->curlError = curl_error($ch);
        $this->httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->re        = $response;
        curl_close($ch);
    }



    /**
     * 代付异步回调
     * @param array $params 异步参数
     * @return mixed
     */
    public function callbackResult($params) {
        $this->parameter = $params;

        //检验状态
        if (!$this->public_key_decrypt($params['sign'],$this->pubKey)) {
            throw new \Exception('sign is wrong');
        }

        //订单状态已经成功 或者 消息与订单状态一致
        if($this->order['status'] == 'paid') {
            return;
        }
        $amount     = bcmul($params['payAmount'], 100);//以分为单位
        $real_money = $amount;//实际到账金额

        //订单状态付款状态：(//订单状态：Failed 失败Payed 成功 OverTime超时 Canceled 订单取消)v
        if($this->parameter['status'] == 'success') {
            $status       = 'paid';
            $this->return = ['code' => 1, 'msg' => ''];
        } elseif($this->parameter['status'] == 'fail') {
            $status       = 'failed';
            $this->return = ['code' => 0, 'msg' => '代付失败'];
        } else {
            $this->return = ['code' => 0, 'msg' => 'error'];
            return;
        }

        $this->re = $this->return;
        $this->updateTransferOrder($this->money, $real_money, $params['orderNo'],//第三方转账编号
            '', $status);
    }

    /**
     * 银行名称
     * @return mixed
     */
    private function getBankName() {

        $banks = [
            "Gcash" => "GCASH",
            "Paymaya Philippines, Inc."  => "PAYMAYA",
        ];
        return $banks[$this->bankCode];
    }
}
