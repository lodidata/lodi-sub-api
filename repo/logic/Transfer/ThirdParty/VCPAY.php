<?php

namespace Logic\Transfer\ThirdParty;

use Utils\Utils;

class VCPAY extends BASES {
    private $httpCode = '';

    //回调，不处理逻辑
    public function callbackMsg() {
        return 'success';
    }

    //请求代付接口
    public function runTransfer() {
        //组装参数
        $data            = [
            'app_id'        => $this->partnerID,
            'nonce_str'     => Utils::creatUsername(),
            'trade_type'    => 'PHT001',
            'order_amount'  => $this->money,
            'out_trade_no'  => $this->orderID,
            'bank_code'     => '000000',
            'bank_owner'    => $this->bankUserName,
            'bank_account'  => 'NONE',
            'identity_type' => 'PHONE',
            'identity'      => $this->bankCard,
            'notify_url'    => $this->payCallbackDomain . '/thirdAdvance/callback/vcpay',
        ];
        $data['sign']    = $this->sign($data);
        $this->payUrl    .= '/wd/save';
        $this->parameter = $data;
        $this->basePostNew();

        $result = json_decode($this->re, true);

        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;
        if($this->httpCode == 200) {
            if($code == 200) {
                $this->return['code']    = 10500;
                $this->return['balance'] = $this->money;
                $this->return['msg']     = $message;
                $this->transferNo        = $result['trade_no'];//第三方订单号
                //成功就直接返回了
                return;
            } else {
                $message = 'http_code:' . $this->httpCode . 'errorMsg:' . json_encode($result, JSON_UNESCAPED_UNICODE);
                $this->updateTransferOrder($this->money, 0, '', '', 'failed', null, $message);
                $this->return['code']    = 886;
                $this->return['balance'] = 0;
                $this->return['msg']     = 'VCPAY:' . $message ?? '代付失败';
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
            'app_id'    => $this->partnerID,
            'nonce_str' => Utils::creatUsername(),
        ];
        $params['sign'] = $this->sign($params);

        $this->parameter = $params;

        $this->payUrl .= "/pay/balance";
        $this->basePostNew();
        $result  = json_decode($this->re, true);
        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;

        if($this->httpCode == 200) {
            if($code == 200) {
                $this->return['code']    = 10509;
                $this->return['balance'] = $result['balance'];
                $this->return['msg']     = $message;
                return;
            }
        }

        $this->_end();
        throw new \Exception('http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message);
    }

    //查询代付结果
    public function getTransferResult() {
        $data         = [
            'app_id'       => $this->partnerID,
            'nonce_str'    => Utils::creatUsername(),
            'out_trade_no' => $this->orderID,
        ];
        $data['sign'] = $this->sign($data);

        $this->payUrl    .= '/wd/query';
        $this->parameter = $data;

        $this->basePostNew();

        $result = json_decode($this->re, true);

        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;

        if($this->httpCode == 200) {
            if($code == 200){
                //订单状态 交易状态：0：失败，1：成功，2：关闭，3：处理中
                if($result['trade_state'] == 1) {
                    $status       = 'paid';
                    $this->return = ['code' => 1, 'msg' => $message];
                } elseif($result['status'] == 2) {
                    $status       = 'failed';
                    $this->return = ['code' => 0, 'msg' => $message];
                } else {
                    $this->return = ['code' => 0, 'msg' => $message];
                    return;
                }

                $real_money = $result['order_amount'];
                $fee        = $this->money - $real_money;
                $this->updateTransferOrder($this->money, $real_money, $result['trade_no'],//第三方转账编号
                    '', $status, $fee, $message);
                return;
            }

        }
        $this->return = ['code' => 886, 'transfer' => '', 'msg' => 'http_code:' . $this->httpCode . ' message:' . $message];
    }

    public function basePostNew() {
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
        $response        = curl_exec($ch);
        $this->curlError = curl_error($ch);
        $this->httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->re        = $response;
        curl_close($ch);
    }

    //生成签名
    public function sign($data) {
        unset($data['sign']);
        unset($data['code']);
        unset($data['msg']);
        ksort($data);
        reset($data);

        $str = '';
        foreach($data as $k => $v) {
            if(is_null($v) || $v === '')
                continue;
            $str .= $k . '=' . $v . '&';
        }
        $str = trim($str, '&');

        $sign_str = $str . '&key=' . $this->key;

        return strtoupper(md5($sign_str));
    }

    /**
     * 代付异步回调
     * @param array $params 异步参数
     * @return mixed
     */
    public function callbackResult($params) {
        $this->parameter = $params;

        if($this->sign($params) != $params['sign']) {
            throw new \Exception('Sign error');
        }

        //订单状态已经成功 或者 消息与订单状态一致
        if($this->order['status'] == 'paid') {
            return;
        }
        $amount     = $params['order_amount'];//以分为单位
        $real_money = $amount;//实际到账金额

        //交易状态：0：失败，1：成功，2：关闭，3：处理中
        if($this->parameter['trade_state'] == 1) {
            $status       = 'paid';
            $this->return = ['code' => 1, 'msg' => ''];
        } elseif($this->parameter['trade_state'] == 2) {
            $status       = 'failed';
            $this->return = ['code' => 0, 'msg' => '代付失败'];
        } else {
            $this->return = ['code' => 0, 'msg' => 'error'];
            return;
        }

        $this->re = $this->return;
        $this->updateTransferOrder($this->money, $real_money, $params['trade_no'],//第三方转账编号
            '', $status);
    }

    /**
     * 银行名称
     * @return mixed
     */
    private function getBankName() {

        $banks = [
            "Gcash"                               => "gcash",
            "BPI"                                 => "bpi",
            "Banco De Oro Unibank, Inc."          => "Unibank",
            "Metropolitan Bank and Trust Co"      => "mbt",
            "SBC"                                 => "SBC",
            "PNB"                                 => "PNB",
            "CBC"                                 => "CBC",
            "United Coconut Planters Bank"        => "UCPB",
            "PSB"                                 => "PSB",
            "AUB"                                 => "AUB",
            "PBC"                                 => "PBC",
            "ALLBANK (A Thrift Bank), Inc."       => "AB",
            "BDO Network Bank, Inc."              => "BNB",
            "CBS"                                 => "CBS",
            "CTBC"                                => "CTBC",
            "ESB"                                 => "ESB",
            "GrabPay"                             => "GP",
            "ISLA Bank (A Thrift Bank), Inc."     => "ISLA",
            "Omnipay"                             => "OP",
            "Partner Rural Bank (Cotabato), Inc." => "PRB",
            "Paymaya Philippines, Inc."           => "PMP",
            "PBB"                                 => "PBB",
            "PTC"                                 => "PTC",
            "Starpay"                             => "STP",
            "SSB"                                 => "SSB",
            "UCPB SAVINGS BANK"                   => "USB",
            "Wealth Development Bank, Inc."       => "WDB",
        ];
        return $banks[$this->bankCode];
    }
}
