<?php

namespace Logic\Transfer\ThirdParty;

use Logic\Recharge\Recharge;

class METAPAY extends BASES {
    private $httpCode = '';

    public $pending_message = [
        'REQUEST_FAIL',
        'UNKNOWN_FATAL_ERROR',
        'ORDER_STATUS_COLLISION',
        'PAYMENT_ORDER_NOT_FOUND',
    ];

    //回调，不处理逻辑
    public function callbackMsg() {
        return 'success';
    }

    //请求代付接口
    public function runTransfer() {
        //组装参数
        $bank_code = $this->getBankName();
        if($bank_code == 500){
            $pay_type = 'AP006';
        }else{
            $pay_type = 'AP001';
        }
        $data            = [
            'BANK_ACCOUNT_NAME'  => trim($this->bankUserName),
            'BANK_ACCOUNT_NO'    => $this->bankCard,
            'BANK_CODE'          => $bank_code,
            'MERCHANT_ID'        => $this->partnerID,
            'NOTIFY_URL'         => $this->payCallbackDomain . '/thirdAdvance/callback/metapay',
            'PAY_TYPE'           => $pay_type,
            'SUBMIT_TIME'        => date('YmdHis',time()),
            'TRANSACTION_NUMBER' => $this->orderID,
            'TRANSACTION_AMOUNT' => bcdiv($this->money, 100, 2),
            'VERSION'            => 1,
        ];
        $data['SIGNED_MSG']    = $this->sign($data);
        $this->payUrl    .= '/gateway/api-center/agent-pay';
        $this->parameter = $data;
        $this->basePostNew();

        $result = json_decode($this->re, true);

        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;
        if($this->httpCode == 200) {
            if($result['code'] == 'G_00005') {
                $this->return['code']    = 10500;
                $this->return['balance'] = $this->money;
                $this->return['msg']     = $message;
                $this->transferNo        = $result['data']['transactionId'];//第三方订单号
                //成功就直接返回了
                return;
            }elseif(!in_array($message,$this->pending_message)){
                $message = 'http_code:' . $this->httpCode . 'errorMsg:' . json_encode($result, JSON_UNESCAPED_UNICODE);
                $this->updateTransferOrder($this->money, 0, '', '', 'failed', null, $message);
                $this->return['code']    = 886;
                $this->return['balance'] = 0;
                $this->return['msg']     = 'METAPAY:' . $message ?? '代付失败';
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
        $params         = [
            'MERCHANT_ID' => $this->partnerID,
            'SUBMIT_TIME' => date('YmdHis',time()),
            'VERSION'     => 1,
        ];
        $params['SIGNED_MSG'] = $this->sign($params);

        $this->parameter = $params;

        $this->payUrl .= "/gateway/api-center/merchant-balance-query";
        $this->basePostNew();
        $result  = json_decode($this->re, true);
        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;

        if($this->httpCode == 200 && $code == 'G_00001') {
            $this->return['code']    = 10509;
            $this->return['balance'] = bcmul($result['data']['MERCHANT_BALANCE_AVAILABLE'], 100);
            $this->return['msg']     = $message;
            return;
        }

        $this->_end();
        throw new \Exception('http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message);
    }

    //查询代付结果
    public function getTransferResult() {
        $data         = [
            'MERCHANT_ID' => $this->partnerID,
            'TRAN_CODE'   => $this->orderID,
            'VERSION'     => 1,
        ];
        $data['SIGNED_MSG'] = $this->sign($data);

        $this->payUrl    .= '/gateway/api-center/agent-pay-query';
        $this->parameter = $data;

        $this->basePostNew();

        $result = json_decode($this->re, true);

        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;

        if($this->httpCode == 200 && $result['code'] == 'G_00001') {
            //订单状态 status (0-錯誤 1-等待中，2,6-進行中，3-失敗，5-成功 )
            if($result['data']['STATUS'] == 'SUCCESS') {
                $status       = 'paid';
                $this->return = ['code' => 1, 'msg' => $message];
            } elseif($result['data']['STATUS'] == 'FAILED' || $result['data']['STATUS'] == 'MERCHANT_TIMEOUT') {
                $status       = 'failed';
                $this->return = ['code' => 0, 'msg' => $message];
            } else {
                $this->return = ['code' => 0, 'msg' => $message];
                return;
            }

            $real_money = bcmul($result['data']['TRAN_AMT'], 100);
            $fee        = $this->money - $real_money;
            $this->updateTransferOrder($this->money, $real_money, null,//第三方转账编号
                '', $status, $fee, $message);
            return;
        }
        $this->return = ['code' => 886, 'transfer' => '', 'msg' => 'http_code:' . $this->httpCode . ' message:' . $message];
    }

    public function basePostNew() {
        $this->payRequestUrl = $this->payUrl;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->payUrl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->parameter));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
        ]);

        $response        = curl_exec($ch);
        $this->curlError = curl_error($ch);
        $this->httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->re        = $response;
        curl_close($ch);
    }

    //生成签名
    public function sign($data) {
        unset($data['SIGNED_MSG']);
        ksort($data);
        reset($data);

        $str = '';
        foreach($data as $k => $v) {
            if(is_null($v) || $v === '')
                continue;
            $str .= $k . '=' . $v . '&';
        }
        $str = trim($str, '&');

        $sign_str = $str .  $this->key;

        return md5($sign_str);
    }

    /**
     * 代付异步回调
     * @param array $params 异步参数
     * @return mixed
     */
    public function callbackResult($params) {
        $this->parameter = $params;

        if($this->sign($params) != $params['SIGNED_MSG']) {
            throw new \Exception('Sign error');
        }

        //订单状态已经成功 或者 消息与订单状态一致
        if($this->order['status'] == 'paid') {
            return;
        }
        $amount     = bcmul($params['ORIG_AMT'], 100);//以分为单位
        $real_money = $amount;//实际到账金额

        //订单状态付款状态
        if($this->parameter['STATUS'] == 'SUCCESS') {
            $status       = 'paid';
            $this->return = ['code' => 1, 'msg' => ''];
        } else {
            $this->return = ['code' => 0, 'msg' => 'error'];
            return;
        }

        $this->re = $this->return;
        $this->updateTransferOrder($this->money, $real_money, null,//第三方转账编号
            '', $status);
    }

    /**
     * 银行名称
     * @return mixed
     */
    private function getBankName() {

        $banks = [
            "Gcash"                                  => "500",
            "Bangko Mabuhay (A Rural Bank), Inc."    => "518",
            "Chinabank"                              => "508",
            "PNB"                                    => "507",
            "Metropolitan Bank and Trust Co"         => "504",
            "BPI"                                    => "502",
        ];
        return $banks[$this->bankCode];
    }

}
