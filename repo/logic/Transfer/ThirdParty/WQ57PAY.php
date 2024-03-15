<?php

namespace Logic\Transfer\ThirdParty;

use Logic\Recharge\Recharge;
use Utils\Curl;

/**
 *
 * 57代付
 */
class WQ57PAY extends BASES {
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
            'merchantKey'             => $this->app_secret,
            'amount'                  => bcdiv($this->money, 100, 2),
            'outID'                   => $this->orderID,
            'notifyUrl'               => $this->payCallbackDomain . '/thirdAdvance/callback/wq57pay',
            'collectionAccountNumber' => $this->bankCard,
            'collectionsTypeName'     => $this->bankCode,
            'payee'                   => $this->bankUserName
        ];
        $config_params = !empty($this->thirdConfig['params']) ? json_decode($this->thirdConfig['params'], true) : [];
        if(!empty($config_params) && isset($config_params['currency'])) {
            $data['currency'] = $config_params['currency'];
        }
        $payType=$this->getPaytype($this->bankCode);
        if(!empty($payType)){
            $data['payType'] = $payType;
        }else{
            if(!empty($config_params) && isset($config_params['payType'])) {
                $data['payType'] = $config_params['payType'];
            }
        }
        $this->parameter = $data;
        //判断 额外参数是否为空 Determine whether the extra parameter is empty
        $this->payUrl .= '/api?option=mutation&field=createTransferAPI';

        $this->initParam($data);
        $this->basePostNew();
        $result  = isset($this->re) ? json_decode($this->re, true) : '';
        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;
        if($this->httpCode == 200) {
            //true成功,false失败
            if($code === 0) {
                $this->return['code']    = 10500;
                $this->return['balance'] = $this->money;
                $this->return['msg']     = $message;
                $this->transferNo        = $result['data']['outID'];//第三方订单号
                //成功就直接返回了
                return;
            } else {
                $message = 'http_code:' . $this->httpCode . 'errorMsg:' . json_encode($result, JSON_UNESCAPED_UNICODE);
                $this->updateTransferOrder($this->money, 0, '', '', 'failed', null, $message);
                $this->return['code']    = 886;
                $this->return['balance'] = 0;
                $this->return['msg']     = '75PAY:' . $message ?? '代付失败';
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
            'currency' => $config_params['currency']
        ];

        $this->payUrl .= "/api?option=query&field=QueryCurrencyAPI";
        $this->initParam($params);
        $this->basePostNew();
        $result  = json_decode($this->re, true);
        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;

        if($this->httpCode == 200) {
            if($code === 0) {
                $this->return['code']    = 10509;
                $this->return['balance'] = bcmul($result['data'][0]['AvailableDeposit'], 100, 0);
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
            'outID' => $this->orderID
        ];
        $this->payUrl .= "/api?option=query&field=QueryTransferAPI";
        $this->initParam($params);
        $this->basePostNew();

        $result  = json_decode($this->re, true);
        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;
        if($this->httpCode == 200) {
            if($code === 0) {
                $resultData = $result['data'];
                //订单状态：PROCESSING 处理中FAILED 失败SUCCESS 成功
                if($resultData['status'] == 'Payed') {
                    $status       = 'paid';
                    $this->return = ['code' => 1, 'msg' => $message];
                } elseif($resultData['status'] == 'Failed') {
                    $status       = 'failed';
                    $this->return = ['code' => 0, 'msg' => $message];
                } elseif($resultData['status'] == 'Processing') {
                    $status       = "pending";
                    $this->return = ['code' => 0, 'msg' => $message];
                } else {
                    $this->return = ['code' => 0, 'msg' => $message];
                    return;
                }

                $real_money = bcmul($resultData['amount'], 100);
                $fee        = $this->money - $real_money;
                $this->updateTransferOrder($this->money, $real_money, $resultData['outID'], '', $status, $fee);
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
        $this->time      = time() . '000';
        $this->sign      = $this->sign($params);
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

    //验证回调签名
    public function verifySign($data, $sign, $time) {
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

    public function basePostNew($referer = null) {
        $header = [
            "Content-Type: application/json",
            "key:" . $this->app_secret,
            "timestamp:" . $this->time,
            "signature:" . $this->sign,
        ];

        $this->payRequestUrl = $this->payUrl;
        $params_data         = json_encode($this->parameter, JSON_UNESCAPED_UNICODE);
        $ch                  = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->payUrl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
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
        $sign            = $params['signature'];
        $timestamp       = $params['timestamp'];
        unset($params['signature']);
        unset($params['timestamp']);

        if(!$this->verifySign($params, $sign, $timestamp)) {
            throw new \Exception('Sign error');
        }

        //订单状态已经成功 或者 消息与订单状态一致
        if($this->order['status'] == 'paid') {
            return;
        }
        $amount     = bcmul($params['amount'], 100);//以分为单位
        $real_money = $amount;//实际到账金额

        //订单状态付款状态：(//订单状态：Failed 失败Payed 成功 OverTime超时 Canceled 订单取消)
        if($this->parameter['status'] == 'Payed') {
            $status       = 'paid';
            $this->return = ['code' => 1, 'msg' => ''];
        } elseif(in_array($this->parameter['status'], ['Failed', 'OverTime', 'Canceled'])) {
            $status       = 'failed';
            $this->return = ['code' => 0, 'msg' => '代付失败'];
        } else {
            $this->return = ['code' => 0, 'msg' => 'error'];
            return;
        }

        $this->re = $this->return;
        $this->updateTransferOrder($this->money, $real_money, $params['outID'],//第三方转账编号
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

    private function getPaytype($bankCode) {
        $banks = [
            'Gcash'                     => 'Gcash',
            'Paymaya Philippines, Inc.' => 'Maya'
        ];
        return $banks[$bankCode];
    }
}
