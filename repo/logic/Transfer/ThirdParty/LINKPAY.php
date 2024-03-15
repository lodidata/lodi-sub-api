<?php

namespace Logic\Transfer\ThirdParty;

use Logic\Recharge\Recharge;
use Utils\Curl;

/**
 *
 * LINKPAY代付
 */
class LINKPAY extends BASES {
    private $httpCode = '';

    //回调，不处理逻辑
    public function callbackMsg() {
        return 'success';
    }

    //请求代付接口
    public function runTransfer() {
        $params          = [
            "merchantNo"      => $this->partnerID, //商户号
            "merchantOrderNo" => $this->orderID,   //订单号
            "payAmount"       => bcdiv($this->money, 100, 2),   //代付金额
            "bankCode"        => $this->getBankName(),
            "bankNumber"      => $this->bankCard,
            "accountHoldName" => trim($this->bankUserName),
            "notifyUrl"       => $this->payCallbackDomain . '/thirdAdvance/callback/linkpay',   //回调地址
        ];

        $this->parameter = $params;
        $this->payUrl .= '/disbursement/cash';

        $this->initParam($params);
        $this->basePostNew();
        $result  = isset($this->re) ? json_decode($this->re, true) : '';
        $code    = isset($result['status']) ? $result['status'] : 1;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;
        if($this->httpCode == 200) {
            //true成功,false失败
            if($code == '200') {
                $this->return['code']    = 10500;
                $this->return['balance'] = $this->money;
                $this->return['msg']     = $message;
                $this->transferNo        = $result['data']['platOrderNo'];//第三方订单号
                //成功就直接返回了
                return;
            }else{
                $message = 'http_code:' . $this->httpCode . 'errorMsg:' . json_encode($result, JSON_UNESCAPED_UNICODE);
                $this->updateTransferOrder($this->money, 0, '', '', 'failed', null, $message);
                $this->return['code']    = 886;
                $this->return['balance'] = 0;
                $this->return['msg']     = 'LINKPAY:' . $message ?? '代付失败';
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
        $params       = [
            'merchantNo' => $this->partnerID,
            'timestamp'  => time(),
        ];
        $this->payUrl .= "/disbursement/balance";
        $this->initParam($params);
        $this->basePostNew();
        $result  = json_decode($this->re, true);
        $code    = isset($result['status']) ? $result['status'] : 1;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;

        if($this->httpCode == 200) {
            if($code == '200') {
                $balance = $result['data']['availableAmount'];
                $this->return['code']    = 10509;
                $this->return['balance'] = bcmul($balance ,100);
                $this->return['msg']     = $message;
                return;
            }
        }
        $this->_end();
        throw new \Exception('http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message);
    }

    //代付订单查询
    public function getTransferResult() {
        $params = [
            'merchantNo'      => $this->partnerID,
            'merchantOrderNo' => $this->orderID
        ];
        $this->payUrl .="/disbursement/cash/query";
        $this->initParam($params);
        $this->basePostNew();

        $result     = json_decode($this->re, true);
        $code       = isset($result['status']) ? $result['status'] : 1;
        $message    = isset($result['message']) ? $result['message'] : 'errorMsg:'.(string)$this->re;

        if ($this->httpCode == 200) {
            if($code == '200'){
                $resultData=$result['data'];
                //订单状态：PENDING CREATED 处理中 、  FAILED 失败  、SUCCESS 成功
                if($resultData['orderStatus'] == 'SUCCESS'){
                    $status = 'paid';
                    $this->return = ['code' => 1,  'msg' => $message];
                }elseif($resultData['orderStatus'] == 'FAILED'){
                    $status = 'failed';
                    $this->return = ['code' => 0,  'msg' => $message];
                }elseif($resultData['orderStatus'] == 'PENDING' || $resultData['orderStatus'] == 'CREATED'){
                    $status="pending";
                    $this->return = ['code' => 0,  'msg' => $message];
                }else{
                    $this->return = ['code' => 0,  'msg' => $message];
                    return;
                }

                $real_money = $this->money;
                $fee        = $this->money - $real_money;
                $this->updateTransferOrder(
                    $this->money,
                    $real_money,
                    $resultData['platOrderNo'],
                    '',
                    $status,
                    $fee,
                    $message
                );
                return;
            }

        }
        $this->return = ['code' => 886, 'transfer' => '', 'msg' => 'http_code:'.$this->httpCode.' code:'.$code.' message:'.$message];
    }

    //组装数组
    public function initParam($params = []) {
        $data            = $params;
        $data['sign']    = $this->sign($params);  //校验码
        $this->parameter = $data;
    }

    //验证回调签名
    public function sign($data) {
        unset($data['sign']);
        ksort($data);
        reset($data);

        $str = '';
        foreach ($data as $k => $v){
            if(is_null($v) || $v === '') continue;
            $str .= $v;
        }

        $prikey = "-----BEGIN RSA PRIVATE KEY-----\n".wordwrap($this->key, 64, "\n", true)."\n-----END RSA PRIVATE KEY-----";

        $content = '';
        foreach (str_split($str, 117) as $str1) {
            openssl_private_encrypt($str1, $crypted, $prikey);
            $content .= $crypted;
        }
        $sign = base64_encode($content);

        return $sign;
    }


    //验证回调签名
    public function verifySign($data) {
        $sign = $data['sign'];
        $sign = str_replace('-','+',$sign);
        $sign = str_replace('_','/',$sign);

        $pay_public_key = "-----BEGIN PUBLIC KEY-----\n".wordwrap($this->pubKey, 64, "\n", true)."\n-----END PUBLIC KEY-----";
        $res = openssl_pkey_get_public($pay_public_key);

        $crypto = '';
        foreach (str_split(base64_decode($sign), 128) as $chunk) {
            openssl_public_decrypt($chunk, $decryptData, $res);
            $crypto .= $decryptData;
        }

        if(!empty($crypto)){
            return true;
        }
        return false;
    }

    public function basePostNew($referer = null) {
        $this->payRequestUrl = $this->payUrl;
        $params_data = json_encode($this->parameter, JSON_UNESCAPED_UNICODE);
        $ch = curl_init();

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
            'Content-Length:' . strlen($params_data) ,
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

        if(!$this->verifySign($params)) {
            throw new \Exception('Sign error');
        }

        //订单状态已经成功 或者 消息与订单状态一致
        if($this->order['status'] == 'paid') {
            return;
        }
        $amount     = bcmul($params['amount'], 100);//以分为单位
        $real_money = $amount;//实际到账金额

        //订单状态付款状态：(//订单状态：PENDING CREATED 处理中 、  FAILED 失败  、SUCCESS 成功)
        if($this->parameter['orderStatus'] == 'SUCCESS') {
            $status       = 'paid';
            $this->return = ['code' => 1, 'msg' => ''];
        } elseif($this->parameter['orderStatus'] == 'PENDING' || $this->parameter['orderStatus'] == 'CREATED') {
            $status       = 'pending';
            $this->return = ['code' => 0, 'msg' => '代付处理中'];
        }elseif($this->parameter['orderStatus'] == 'FAILED') {
            $status       = 'failed';
            $this->return = ['code' => 0, 'msg' => '代付失败'];
        } else {
            $this->return = ['code' => 0, 'msg' => 'error'];
            return;
        }

        $this->re = $this->return;
        $this->updateTransferOrder($this->money, $real_money, $params['platOrderNo'],//第三方转账编号
            '', $status);
    }

    /**
     * 银行名称
     * @return mixed
     */
    private function getBankName() {

        $banks = [
            'AUB'                                 => 'AUB',
            'Starpay'                             => 'STARPAY',
            'EB'                                  => 'EAST_WEST',
            'ESB'                                 => 'ESB',
            'MB'                                  => 'MALAYAN_BANK',
            'ERB'                                 => 'EASTWEST_RURAL',
            'PB'                                  => 'PB',
            'PBC'                                 => 'PBC',
            'PBB'                                 => 'PBB',
            'PNB'                                 => 'PNB',
            'PSB'                                 => 'PSB',
            'PTC'                                 => 'PTC',
            'PVB'                                 => 'PVB',
            'Rizal Commercial Banking Corporation' => 'RCBC',
            'RB'                                  => 'RB',
            'SBC'                                 => 'SECURITY_BANK',
            'SBA'                                 => 'STERLING_BANK',
            'SSB'                                 => 'SUN_BANK',
            'United Coconut Planters Bank'        => 'UCPB',
            'GrabPay'                             => 'GRABPAY',
            'BOC'                                 => 'BOC',
            'CTBC'                                => 'CTBC',
            'Chinabank'                           => 'CHINABANK',
            'CBS'                                 => 'CBS',
            'CBC'                                 => 'CBC',
            'Camalig'                             => 'CAMALIG_BANK',
            'DBI'                                 => 'DBI',
            'Gcash'                               => 'GLOBE_GCASH',
            'Cebuana Lhuillier Rural Bank, Inc.'  => 'CEBUANA_BANK',
            'Landbank of the Philippines'         => 'LAND_BANK',
            'Maybank Philippines, Inc.'           => 'MAYBANK',
            'Metropolitan Bank and Trust Co'      => 'METRO_BANK',
            'Omnipay'                             => 'OMNIPAY',
            'Partner Rural Bank (Cotabato), Inc.' => 'PRB',
            'Paymaya Philippines, Inc.'           => 'PAYMAYA',
            'ING'                                 => 'ING_BANK',
            'BPI'                                 => 'BPI',
        ];
        return $banks[$this->bankCode];
    }


    private function PHLBabnkName(){
        $banks=[
            'Gcash'=>[
                'bankCode'=>'GCASH',
                'transferType'=>902410175001
            ],
            'Paymaya Philippines, Inc.'=>[
                'bankCode'=>'PAYMAYA',
                'transferType'=>902410175002
            ]
        ];
        return $banks[$this->bankCode] ?? $banks['Gcash'];
    }
}
