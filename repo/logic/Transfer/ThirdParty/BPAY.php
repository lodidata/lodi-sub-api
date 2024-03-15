<?php

namespace Logic\Transfer\ThirdParty;

use Logic\Recharge\Recharge;
use Utils\Curl;

/**
 *
 * BPAY代付
 */
class BPAY extends BASES {
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
            "countryCode"     => "",            //国家代码
            "currencyCode"    => "",            //币种代码
            "transferType"    => "",   //代付类型
            "transferAmount"  => bcdiv($this->money, 100, 2),   //代付金额
            "feeDeduction"    => "1",   //手续费扣取 0：从转账金额中扣除1：从账户余额中扣除
            "remark"          => "transfer",   //转账备注
            "notifyUrl"       => $this->payCallbackDomain . '/thirdAdvance/callback/bpay',   //回调地址
            "extendedParams"  => "",//扩展参数
        ];
        $config_params = !empty($this->thirdConfig['params']) ? json_decode($this->thirdConfig['params'],true) : [];
        if(!empty($config_params) && isset($config_params['countryCode'])){
            $params['countryCode'] = $config_params['countryCode'];
        }
        if(!empty($config_params) && isset($config_params['currencyCode'])){
            $params['currencyCode'] = $config_params['currencyCode'];
        }
        if(!empty($config_params) && isset($config_params['transferType'])){
            $params['transferType'] = $config_params['transferType'];
        }
        if($params['countryCode'] == 'PHL'){
            $bank = $this->PHLBabnkName();
            $params['extendedParams'] = "bankAccount^{$this->bankCard}|bankCode^{$bank['bankCode']}";
            $params['transferType']   = $bank['transferType'];

        }elseif($params['countryCode'] == 'THA'){
            $params['extendedParams'] = "bankAccount^{$this->bankCard}|bankCode^{$this->bankCode}|payeeName^{$this->bankUserName}";
            //$params['extendedParams'] = "bankAccount^{$this->bankCard}|bankName^Bangkok Bank|payeeName^$this->bankUserName";
        }elseif($params['countryCode'] == 'MEX'){
            $bank_code = $this->getBankName();
            $params['extendedParams'] = "bankAccount^{$this->bankCard}|bankCode^{$bank_code}|payeeName^{$this->bankUserName}";
        }

        $this->parameter = $params;
        //判断 额外参数是否为空 Determine whether the extra parameter is empty
        $this->payUrl .= '/transfer/order/create';

        $this->initParam($params);
        $this->basePostNew();
        $result  = isset($this->re) ? json_decode($this->re, true) : '';
        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;
        if($this->httpCode == 200) {
            //true成功,false失败
            if($code === '200') {
                $this->return['code']    = 10500;
                $this->return['balance'] = $this->money;
                $this->return['msg']     = $message;
                $this->transferNo        = $result['data']['orderNo'];//第三方订单号
                //成功就直接返回了
                return;
            }else{
                $message = 'http_code:' . $this->httpCode . 'errorMsg:' . json_encode($result, JSON_UNESCAPED_UNICODE);
                $this->updateTransferOrder($this->money, 0, '', '', 'failed', null, $message);
                $this->return['code']    = 886;
                $this->return['balance'] = 0;
                $this->return['msg']     = 'BPAY:' . $message ?? '代付失败';
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
            'merchantNo' => $this->partnerID
        ];
        $this->payUrl .= "/transfer/balance/query";
        $this->initParam($params);
        $this->basePostNew();
        $result  = json_decode($this->re, true);
        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;

        $config_params = !empty($this->thirdConfig['params']) ? json_decode($this->thirdConfig['params'],true) : [];

        if($this->httpCode == 200) {
            if($code === '200') {
                $balance = 0;
                $tmp=[];
                if(!empty($result['data'])) {
                    foreach($result['data'] as $v) {
                        if($v['currencyCode'] == $config_params['currencyCode']){
                            $balance = $v['availableAmount'];
                            $tmp=$v;
                        }
                    }
                }
                $this->return['code']    = 10509;
                $this->return['balance'] = bcmul($balance ,100);
                $this->return['msg']     = json_encode($tmp);
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
        $this->payUrl .="/transfer/order/query";
        $this->initParam($params);
        $this->basePostNew();

        $result     = json_decode($this->re, true);
        $code       = isset($result['code']) ? $result['code'] : 1;
        $message    = isset($result['message']) ? $result['message'] : 'errorMsg:'.(string)$this->re;

        if ($this->httpCode == 200) {
            if($code === '200'){
                $resultData=$result['data'];
                //订单状态：PROCESSING 处理中FAILED 失败SUCCESS 成功
                if($resultData['transferStatus'] == 'SUCCESS'){
                    $status = 'paid';
                    $this->return = ['code' => 1,  'msg' => $message];

                }elseif($resultData['transferStatus'] == 'FAILED'){
                    $status = 'failed';
                    $this->return = ['code' => 0,  'msg' => $message];
                }elseif($resultData['transferStatus'] == 'PROCESSING'){
                    $status="pending";
                    $this->return = ['code' => 0,  'msg' => $message];
                }else{
                    $this->return = ['code' => 0,  'msg' => $message];
                    return;
                }

                $real_money = bcmul($resultData['orderAmount'], 100);
                $fee        = $this->money - $real_money;
                $this->updateTransferOrder(
                    $this->money,
                    $real_money,
                    $resultData['orderNo'],
                    '',
                    $status,
                    $fee,
                    $message
                );
                return;
            }else if($code === '804'){
                //804没有单号改为失败
                $this->updateTransferOrder($this->money, 0, '', '', 'failed', null, $message);
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
        $str = '';
        ksort($data);

        foreach($data as $k => $v) {
            $str .= $k . "=" . $v . "&";
        }
        $str    = rtrim($str, '&');
        $prikey = "-----BEGIN RSA PRIVATE KEY-----\n" . wordwrap($this->key, 64, "\n", true) . "\n-----END RSA PRIVATE KEY-----";
        $key    = openssl_get_privatekey($prikey);
        openssl_sign($str, $sign, $key, OPENSSL_ALGO_MD5);
        openssl_free_key($key);
        return base64_encode($sign);
    }


    //验证回调签名
    public function verifySign($data) {
        $sign = base64_decode($data['sign']);
        unset($data['sign']);
        ksort($data);

        $str = '';
        foreach($data as $k => $v) {
            $str .= $k . "=" . $v . "&";
        }
        $str    = rtrim($str, '&');
        $pubkey = "-----BEGIN PUBLIC KEY-----\n".wordwrap($this->pubKey, 65, "\n", true)."\n-----END PUBLIC KEY-----";
        $key = openssl_get_publickey($pubkey);
        if(openssl_verify($str, $sign, $key, OPENSSL_ALGO_MD5)){
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
        $amount     = bcmul($params['transferAmount'], 100);//以分为单位
        $real_money = $amount;//实际到账金额

        //订单状态付款状态：(//订单状态：PROCESSING 处理中FAILED 失败SUCCESS 成功)
        if($this->parameter['transferStatus'] == 'SUCCESS') {
            $status       = 'paid';
            $this->return = ['code' => 1, 'msg' => ''];
        } elseif($this->parameter['transferStatus'] == 'pending') {
            $status       = 'failed';
            $this->return = ['code' => 0, 'msg' => '代付处理中'];
        }elseif($this->parameter['transferStatus'] == 'FAILED') {
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
            "STP"        => "MXNSTP",
            "HSBC"       => "MXNHSBC",
            "AZTECA"     => "MXNAZTECA",
            "BANAMEX"    => "MXNBANAMEX",
            "BANORTE"    => "MXNBANORTE",
            "BANREGIO"   => "MXNBANREGIO",
            "BANCOPPEL"  => "MXNBANCOPPEL",
            "SANTANDER"  => "MXNSANTANDER",
            "SCOTIABANK" => "MXNSCOTIABANK",
            "BANCOMEXT"  => "MXNBCT",
            "INBURSA"    => "MXNIBA",
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
