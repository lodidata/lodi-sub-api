<?php

namespace Logic\Transfer\ThirdParty;

class MMPAY extends BASES {
    private $httpCode = '';

    //回调，不处理逻辑
    public function callbackMsg() {
        return 'success';
    }

    //请求代付接口
    public function runTransfer() {
        //组装参数
        $data            = [
            'merchant'          => $this->partnerID,
            'total_amount'      => bcdiv($this->money, 100, 2),
            'order_id'          => $this->orderID,
            'bank'              => $this->getBankName(),
            'bank_card_name'    => $this->bankCard,
            'bank_card_account' => $this->bankCard,
            'bank_card_remark'  => $this->bankCard,
            'callback_url'      => $this->payCallbackDomain . '/thirdAdvance/callback/mmpay',
        ];
        $data['sign']    = $this->sign($data);
        $this->payUrl    .= '/api/daifu';
        $this->parameter = $data;
        $this->basePostNew();

        $result = json_decode($this->re, true);

        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;
        if($this->httpCode == 200) {
            if($result['status'] == '1') {
                $this->return['code']    = 10500;
                $this->return['balance'] = $this->money;
                $this->return['msg']     = $message;
                $this->transferNo        = $this->orderID;//第三方订单号
                //成功就直接返回了
                return;
            }else{
                $message = 'http_code:' . $this->httpCode . 'errorMsg:' . json_encode($result, JSON_UNESCAPED_UNICODE);
                $this->updateTransferOrder($this->money, 0, '', '', 'failed', null, $message);
                $this->return['code']    = 886;
                $this->return['balance'] = 0;
                $this->return['msg']     = 'MMPAY:' . $message ?? '代付失败';
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
            'merchant' => $this->partnerID,
        ];
        $params['sign'] = $this->sign($params);

        $this->parameter = $params;

        $this->payUrl .= "/api/me";
        $this->basePostNew();
        $result  = json_decode($this->re, true);
        $code    = isset($result['status']) ? $result['status'] : 1;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;

        if($this->httpCode == 200) {
            $this->return['code']    = 10509;
            $this->return['balance'] = bcmul($result['balance'], 100);
            $this->return['msg']     = $message;
            return;
        }

        $this->_end();
        throw new \Exception('http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message);
    }

    //查询代付结果
    public function getTransferResult() {
        $data         = [
            'merchant' => $this->partnerID,
            'order_id' => $this->orderID,
        ];
        $data['sign'] = $this->sign($data);

        $this->payUrl    .= '/api/query';
        $this->parameter = $data;

        $this->basePostNew();

        $result = json_decode($this->re, true);

        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;

        if($this->httpCode == 200) {
            //订单状态 status (0-錯誤 1-等待中，2,6-進行中，3-失敗，5-成功 )
            if($result['status'] == '5') {
                $status       = 'paid';
                $this->return = ['code' => 1, 'msg' => $message];
            } elseif($result['status'] == '3') {
                $status       = 'failed';
                $this->return = ['code' => 0, 'msg' => $message];
            } else {
                $this->return = ['code' => 0, 'msg' => $message];
                return;
            }

            $real_money = bcmul($result['amount'], 100);
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
        unset($data['sign']);
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

        return md5($sign_str);
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
        $amount     = bcmul($params['amount'], 100);//以分为单位
        $real_money = $amount;//实际到账金额

        //订单状态付款状态：(：5 - 成功 : 3 – 失敗。)
        if($this->parameter['status'] == '5') {
            $status       = 'paid';
            $this->return = ['code' => 1, 'msg' => ''];
        } elseif($this->parameter['status'] == '3') {
            $status       = 'failed';
            $this->return = ['code' => 0, 'msg' => '代付失败'];
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
            "Gcash"                               => "gcash",
            "BPI"                                 => "bpi",
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
            "Binangonan Rural Bank Inc"           => "BRB",
            "ERB"                                 => "ERB"
        ];
        return $banks[$this->bankCode];
    }

}
