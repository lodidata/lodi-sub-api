<?php

namespace Logic\Transfer\ThirdParty;

use Logic\Recharge\Recharge;
use Utils\Curl;

/**
 *
 * META2代付
 */
class META2PAY extends BASES {
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
            'appId'                   => $this->partnerID,
            'pickupCenter'            => '7',
            'referenceNo'             => $this->orderID,
            'collectedAmount'         => bcdiv($this->money, 100, 2),
            'accountNo'               => $this->bankCard,
            'userName'                => $this->order['user_name'] ?? 'name',
            'birthDate'               => '2000-10-11',
            'mobileNumber'            => $this->order['user_mobile'] ?? '1111111111',
            'certificateType'         => 'SSS',
            'certificateNo'           => '1542369848',
            'address'                 => 'address',
            'city'                    => 'HOUSTON',
            'province'                => 'TEXAS',
            'notificationURL'         => $this->payCallbackDomain . '/thirdAdvance/callback/meta2pay',
            'bankCode'                => $this->bankCode,
        ];

        $bank_info = $this->getBankName();
        $data['pickupCenter'] = $bank_info['pickupCenter'];
        $data['bankCode'] = $bank_info['bankCode'];

        $this->parameter = $data;
        //判断 额外参数是否为空 Determine whether the extra parameter is empty
        $this->payUrl .= '/payment/remit/payout';

        $this->initParam($data);
        $this->basePostNew();
        $result  = isset($this->re) ? json_decode($this->re, true) : '';
        $code    = isset($result['platRespCode']) ? $result['platRespCode'] : 1;
        $message = isset($result['msg']) ? $result['msg'] : '';
        if($this->httpCode == 200) {
            //true成功,false失败
            if($code === 0) {
                $this->return['code']    = 10500;
                $this->return['balance'] = $this->money;
                $this->return['msg']     = $message;
                $this->transferNo        = $result['transId'];//第三方订单号
                //成功就直接返回了
                return;
            } else {
                $message = 'http_code:' . $this->httpCode . 'errorMsg:' . json_encode($result, JSON_UNESCAPED_UNICODE);
                $this->updateTransferOrder($this->money, 0, '', '', 'failed', null, $message);
                $this->return['code']    = 886;
                $this->return['balance'] = 0;
                $this->return['msg']     = 'meta2PAY:' . $message ?? '代付失败';
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
        $config_params = !empty($this->thirdConfig['params']) ? json_decode($this->thirdConfig['params'], true) : [];
        $params        = [
            'appId' => $this->partnerID,
        ];
        $this->payUrl .= "/custom/getBalance";
        $this->initParam($params);
        $this->basePostNew();
        $result  = json_decode($this->re, true);
        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;
        if($this->httpCode == 200) {
            if(isset($result['sign'])) {
                $this->return['code']    = 10500;
                $this->return['balance'] = bcmul($result['availableAmount'],100);
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
            'appId'         => $this->partnerID,
            'transId'       => $this->transferNo,
            'referenceNo'   => $this->orderID
        ];


        $this->payUrl .= "/payment/remit/getRemitOrder";
        $this->initParam($params);
        $this->basePostNew();

        $result  = json_decode($this->re, true);
        $code    = isset($result['platRespCode']) ? $result['platRespCode'] : 1;
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)'';
        if($this->httpCode == 200) {
            if($code === 0) {
                $resultData = $result;
                //订单状态：PROCESSING 处理中FAILED 失败SUCCESS 成功
                if($resultData['status'] == '0') {
                    $status       = 'paid';
                    $this->return = ['code' => 1, 'msg' => $message];
                } elseif($resultData['status'] == '2') {
                    $status       = 'failed';
                    $this->return = ['code' => 0, 'msg' => $message];
                } elseif($resultData['status'] == '1') {
                    $status       = "pending";
                    $this->return = ['code' => 0, 'msg' => $message];
                } else {
                    $this->return = ['code' => 0, 'msg' => $message];
                    return;
                }
                $real_money = bcmul($resultData['amount'], 100);
                $fee        = $this->money - $real_money;
                $this->updateTransferOrder($this->money, $real_money, $resultData['transId'], '', $status, $fee);
                return;
            } else if(in_array($code, [401, 500])) {
                //804没有单号改为失败
                $this->updateTransferOrder($this->money, 0, '', '', 'failed', null, $message);
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
    //验证回调签名
    public function sign($data) {
        $str        = json_encode($data);
        $this->time = time() . '000';
        $str        .= $this->time;

        $prikey = "-----BEGIN RSA PRIVATE KEY-----\n" . $this->key . "\n-----END RSA PRIVATE KEY-----";
        $key    = openssl_get_privatekey($prikey);
        openssl_sign($str, $sign, $key, OPENSSL_ALGO_SHA256);
        return base64_encode($sign);
    }
    function pivate_key_encrypt($data)
    {
        ksort($data);
        $params_str = '';
        foreach ($data as $key => $val) {
            $params_str = $params_str . $val;
        }
        $pivate_key = '-----BEGIN PRIVATE KEY-----'."\n".$this->key."\n".'-----END PRIVATE KEY-----';
        $pi_key = openssl_pkey_get_private($pivate_key);
        $crypto = '';
        foreach (str_split($params_str, 117) as $chunk) {
            openssl_private_encrypt($chunk, $encryptData, $pi_key);
            $crypto .= $encryptData;
        }

        return base64_encode($crypto);
    }

    //验证回调签名
    public function verifySign($data,$sign,$time) {
        $sign = base64_decode($sign);

        $str = json_encode($data);
        $str .= $time;

        $pubkey = "-----BEGIN PUBLIC KEY-----\n" . $this->pubKey . "\n-----END PUBLIC KEY-----";
        $key    = openssl_get_publickey($pubkey);
        if(openssl_verify($str, $sign, $key, OPENSSL_ALGO_SHA256) === 1) {
            return true;
        }
        return false;
    }
    function public_key_decrypt($data, $public_key)
    {
        $public_key = '-----BEGIN PUBLIC KEY-----'."\n".$public_key."\n".'-----END PUBLIC KEY-----';
        $data = base64_decode($data);
        $pu_key =  openssl_pkey_get_public($public_key);
        $crypto = '';
        foreach (str_split($data, 128) as $chunk) {
            openssl_public_decrypt($chunk, $decryptData, $pu_key);
            $crypto .= $decryptData;
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
        $amount     = bcmul($params['amount'], 100);//以分为单位
        $real_money = $amount;//实际到账金额

        //订单状态付款状态：(//订单状态：Failed 失败Payed 成功 OverTime超时 Canceled 订单取消)v
        if($this->parameter['status'] == '0') {
            $status       = 'paid';
            $this->return = ['code' => 1, 'msg' => ''];
        } elseif(in_array($this->parameter['status'],['Failed', 'OverTime', 'Canceled'])) {
            $status       = 'failed';
            $this->return = ['code' => 0, 'msg' => '代付失败'];
        } else {
            $this->return = ['code' => 0, 'msg' => 'error'];
            return;
        }

        $this->re = $this->return;
        $this->updateTransferOrder($this->money, $real_money, $params['transId'],//第三方转账编号
            '', $status);
    }

    /**
     * 银行名称
     * @return mixed
     */
    private function getBankName() {
        $banks = [
            "GrabPay"                   => "GrabPay",
            "Paymaya Philippines, Inc." => "Paymaya Philippines, Inc.",
            "Gcash"                     => "Gcash",
        ];
        $bank_info = [
            'bankCode'     => $banks[$this->bankCode],
            'pickupCenter' => 7,
        ];
        if($this->bankCode == 'GrabPay'){
            $bank_info['pickupCenter'] = 11;
        }elseif($this->bankCode == 'Paymaya Philippines, Inc.'){
            $bank_info['pickupCenter'] = 9;
        }
        return $bank_info;
    }
}
