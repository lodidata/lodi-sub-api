<?php

namespace Logic\Transfer\ThirdParty;

use Logic\Recharge\Recharge;

class ONEYOUPAY extends BASES {
    private $httpCode = '';

    //回调，不处理逻辑
    public function callbackMsg() {
        return 'success';
    }

    //请求代付接口
    public function runTransfer() {
        //组装参数
        $data                 = [
            'username'          => $this->partnerID,
            'amount'            => floatval(bcdiv($this->money, 100, 2)),
            'order_number'      => $this->orderID,
            'notify_url'        => $this->payCallbackDomain . '/thirdAdvance/callback/oneyoupay',
            'bank_name'         => $this->getBankName(),            //银行名称
            'bank_card_number'  => $this->bankCard,                 //银行帐号
        ];
        $data['sign'] = $this->sign($data);

        $this->payUrl         .= '/api/v1/third-party/agency-withdraws';
        $this->parameter      = $data;
        $this->basePostNew();

        $result = json_decode($this->re, true);

        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;
        if($this->httpCode == 200) {
            if(in_array($result['http_status_code'], [200, 201])) {
                $this->return['code']    = 10500;
                $this->return['balance'] = $this->money;
                $this->return['msg']     = $message;
                $this->transferNo        = $this->orderID;//第三方订单号
                //成功就直接返回了
                return;
            } else {
                $message = 'http_code:' . $this->httpCode . 'errorMsg:' . json_encode($result, JSON_UNESCAPED_UNICODE);
                $this->updateTransferOrder($this->money, 0, '', '', 'failed', null, $message);
                $this->return['code']    = 886;
                $this->return['balance'] = 0;
                $this->return['msg']     = 'oneyoupay:' . $message ?? '代付失败';
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
        $params                 = [
            'username' => $this->partnerID,
        ];
        $params['sign'] = $this->sign($params);

        $this->parameter = $params;

        $this->payUrl .= "/api/v1/third-party/profile-queries";
        $this->basePostNew();
        $result  = json_decode($this->re, true);

        $code    = isset($result['http_status_code']) ? $result['http_status_code'] : 200;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;

        if($this->httpCode == 200 && in_array($code, [200, 201])) {
            $this->return['code']    = 10509;
            $this->return['balance'] = bcmul($result['data']['balance'], 100);
            $this->return['msg']     = $message;
            return;
        }

        $this->_end();
        throw new \Exception('http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message);
    }

    //查询代付结果
    public function getTransferResult() {
        $data                 = [
            'user' => $this->partnerID,
            'orderId'    => $this->orderID,
        ];
        $data['sign'] = $this->sign($data);

        $this->payUrl    .= '/api/daifu/query';
        $this->parameter = $data;

        $this->basePostNew();

        $result = json_decode($this->re, true);

        $code    = isset($result['http_status_code']) ? $result['http_status_code'] : 200;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;

        if($this->httpCode == 200 && in_array($code, [200, 201])) {
            //订单状态 status (1、2、3 、11 处理中；4、5 成功；6、7、8 失败)
            $third_no = $this->orderID;
            if(in_array($result['data']['status'], [4, 5])) {
                $status       = 'paid';
                $this->return = ['code' => 1, 'msg' => $message];
            } elseif(in_array($result['data']['status'], [6, 7, 8])) {
                $status       = 'failed';
                $this->return = ['code' => 0, 'msg' => $message];
            } else {
                $this->return = ['code' => 0, 'msg' => $message];
                return;
            }

            $real_money = bcmul($result['amount'], 100);
            $fee        = $this->money - $real_money;
            $this->updateTransferOrder($this->money, $real_money, $third_no,//第三方转账编号
                '', $status, $fee, $message);
            return;
        }
        $this->return = ['code' => 886, 'transfer' => '', 'msg' => 'http_code:' . $this->httpCode . ' message:' . $message];
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
            'Content-Type: application/json'
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

    //生成签名
    public function sign($data)
    {
        unset($data['sign']);
        ksort($data);

        $str = '';
        foreach ($data as $k => $v) {
            if(is_null($v) || $v === '') continue;     //值为 null 则不加入签名
            $str .= $k . '=' . $v . '&';
        }

        $sign_str = $str . 'secret_key=' . $this->key;

        return md5($sign_str);
    }

    /**
     * 代付异步回调
     * @param array $params 异步参数
     * @return mixed
     */
    public function callbackResult($params) {
        $this->parameter = $params;
        $message = $params['message'];
        $param = $params['data'];
        if($this->sign($param) != $param['sign']) {
            throw new \Exception('Sign error');
        }

        //订单状态已经成功 或者 消息与订单状态一致
        if($this->order['status'] == 'paid') {
            return;
        }
        $amount     = bcmul($param['amount'], 100);//以分为单位
        $real_money = $amount;//实际到账金额

        //订单状态付款状态：(1、2、3 、11 处理中；4、5 成功；6、7、8 失败)
        if(in_array($param['status'], [4,5])) {
            $status       = 'paid';
            $this->return = ['code' => 1, 'msg' => ''];
        } elseif(in_array($param['status'], [6,7,8])) {
            $status       = 'failed';
            $this->return = ['code' => 0, 'msg' => '代付失败'];
        } else {
            $this->return = ['code' => 0, 'msg' => 'error'];
            return;
        }

        $this->re = $this->return;
        $this->updateTransferOrder($this->money, $real_money, $params['orderId'],//第三方转账编号
            '', $status, 0, $message);
    }

    /**
     * 银行名称
     * @return mixed
     */
    private function getBankName() {

        $banks = [
            "Gcash" => "GCash",
            "SBC"=>"Security Bank Corporation",
            "PNB"=>"Philippine National Bank (PNB)",
            "United Coconut Planters Bank" => "United Coconut Planters Bank (UCPB)",
            "PSB"=>"Philippine Savings Bank",
            "PBC"=>"Philippine Bank of Communications",
            "ALLBANK (A Thrift Bank), Inc."=>"ALLBANK (A Thrift Bank), Inc.",
            "Bangko Mabuhay (A Rural Bank), Inc."=>"Bangko Mabuhay (A Rural Bank), Inc.",
            "BOC"=>"Bank Of Commerce",
            "Camalig"=>"Camalig Bank",
            "GrabPay"=>"GrabPay Philippines",
            "ISLA Bank (A Thrift Bank), Inc."=>"ISLA Bank (A Thrift Bank), Inc.",
            "Maybank Philippines, Inc."=>"Maybank Philippines, Inc.",
            "Partner Rural Bank (Cotabato), Inc."=>"Partner Rural Bank (Cotabato), Inc.",
            "PTC"=>"Philippine Trust Company",
            "PB"=>"Producers Bank",
            "Starpay"=>"Starpay",
            "UCPB SAVINGS BANK"=>"UCPB SAVINGS BANK"
        ];
        return $banks[$this->bankCode];
    }

}