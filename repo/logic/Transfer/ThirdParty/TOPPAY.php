<?php

namespace Logic\Transfer\ThirdParty;

use Logic\Recharge\Recharge;
use Utils\Curl;

/**
 *
 * TOPPAY代付
 */
class TOPPAY extends BASES {
    private $httpCode = '';

    //回调，不处理逻辑
    public function callbackMsg() {
        return 'success';
    }

    //请求代付接口
    public function runTransfer() {
        $params          = [
            "merchant_no"       => $this->partnerID, //商户号
            "out_trade_no"      => $this->orderID,   //订单号
            "description"       => 'trans',   //订单描述
            "pay_amount"        => bcdiv($this->money, 100, 2),   //支付金额
            "bank_code"         => $this->getBankName(),   //银行编码
            "bank_number"       => $this->bankCard,   //银行卡号
            "account_hold_name" => trim($this->bankUserName),   //收款人完整姓名
            'notify_url'        => $this->payCallbackDomain . '/thirdAdvance/callback/toppay'

        ];
        $this->parameter = $params;
        //判断 额外参数是否为空 Determine whether the extra parameter is empty
        $this->payUrl .= '/api/trade/payout';

        $this->initParam($params);
        $this->basePostNew();
        $result  = isset($this->re) ? json_decode($this->re, true) : '';
        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;
        if($this->httpCode == 200) {
            //true成功,false失败
            if($code == '0') {
                $this->return['code']    = 10500;
                $this->return['balance'] = $this->money;
                $this->return['msg']     = $message;
                $this->transferNo        = $result['data']['trade_no'];//第三方订单号
                //成功就直接返回了
                return;
            }else{
                $message = 'http_code:' . $this->httpCode . 'errorMsg:' . json_encode($result, JSON_UNESCAPED_UNICODE);
                $this->updateTransferOrder($this->money, 0, '', '', 'failed', null, $message);
                $this->return['code']    = 886;
                $this->return['balance'] = 0;
                $this->return['msg']     = 'TOPPAY:' . $message ?? '代付失败';
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
            'merchant_no' => $this->partnerID,
            'timestamp'  => time()
        ];
        $this->payUrl .= "/api/balance";
        $this->initParam($params);
        $this->basePostNew();
        $result  = json_decode($this->re, true);
        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;

        if($this->httpCode == 200) {
            if($code == '0') {
                $this->return['code']    = 10509;
                $this->return['balance'] = bcmul($result['data']['balance'], 100);
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
            'merchant_no'   => $this->partnerID,
            'out_trade_no' => $this->orderID
        ];
        $this->payUrl .= "/api/trade/query";
        $this->initParam($params);
        $this->basePostNew();

        $result  = json_decode($this->re, true);
        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;

        if($this->httpCode == 200) {
            if($code == '0') {
                $resultData = $result['data'];
                //订单状态：0 处理中 1 成功 2 失败
                if($resultData['status'] == '1') {
                    $status       = 'paid';
                    $this->return = ['code' => 1, 'msg' => $message];
                } elseif($resultData['status'] == '2') {
                    $status       = 'failed';
                    $this->return = ['code' => 0, 'msg' => $message];
                } elseif($resultData['status'] == '0') {
                    $status       = "pending";
                    $this->return = ['code' => 0, 'msg' => $message];
                } else {
                    $this->return = ['code' => 0, 'msg' => $message];
                    return;
                }

                $real_money = bcmul($resultData['pay_amount'], 100);
                $fee        = $this->money - $real_money;
                $this->updateTransferOrder($this->money, $real_money, $resultData['trade_no'], '', $status, $fee, $message);
                return;
            }
        }
        $this->return = ['code' => 886, 'transfer' => '', 'msg' => 'http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message];
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
            if($v === '') {
                continue;
            }
            $str .= $k . "=" . $v . "&";
        }
        $prikey = "-----BEGIN PRIVATE KEY-----\n" . wordwrap($this->key, 64, "\n", true) . "\n-----END PRIVATE KEY-----";
        $key    = openssl_get_privatekey($prikey);
        $sign   = '';
        foreach(str_split($str, 117) as $temp) {
            openssl_private_encrypt($temp, $signStr, $key);
            $sign .= $signStr;
        }

        return base64_encode($sign);
    }

    public function verifySign(array $data): bool {
        if(isset($data['sign'])) {
            $sign = base64_decode($data['sign']);
            unset($data['sign']);
        } else {
            return false;
        }
        ksort($data);
        $str = [];
        foreach($data as $k => $v) {
            if($v === '') {
                continue;
            }
            $str[] = $k . '=' . $v;
        }
        $send = implode('&', $str) . '&';
        // 获取用户公钥，并格式化
        $publicKey = "-----BEGIN PUBLIC KEY-----\n" . wordwrap(trim($this->pubKey), 64, "\n", true) . "\n-----END PUBLIC KEY-----";
        $publicKey = openssl_pkey_get_public($publicKey);
        $result    = '';
        foreach(str_split($sign, 128) as $value) {
            openssl_public_decrypt($value, $decrypted, $publicKey);
            $result .= $decrypted;
        }
        return $result === $send;
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

        if(!$this->verifySign($params)) {
            throw new \Exception('Sign error');
        }

        //订单状态已经成功 或者 消息与订单状态一致
        if($this->order['status'] == 'paid') {
            return;
        }
        $amount     = bcmul($params['pay_amount'], 100);//以分为单位
        $real_money = $amount;//实际到账金额

        //订单状态付款状态：(//订单状态：0 处理中 1 成功 2 失败)
        if($this->parameter['status'] == '1') {
            $status       = 'paid';
            $this->return = ['code' => 1, 'msg' => ''];
        } elseif($this->parameter['status'] == '0') {
            $status       = 'failed';
            $this->return = ['code' => 0, 'msg' => '代付处理中'];
        } elseif($this->parameter['status'] == '2') {
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
            "AUB"                                    => "AUB",
            "UnionBank EON"                          => "UnionBank",
            "Starpay"                                => "Starpay",
            "EB"                                     => "EB",
            "ESB"                                    => "ESB",
            "MB"                                     => "MB",
            "ERB"                                    => "ERB",
            "PB"                                     => "PB",
            "PBC"                                    => "PBC",
            "PBB"                                    => "PBB",
            "PNB"                                    => "PNB",
            "PSB"                                    => "PSB",
            "PTC"                                    => "PTC",
            "PVB"                                    => "PVB",
            "RBG"                                    => "RBG",
            "Rizal Commercial Banking Corporation"   => "Rizal Commercial Banking Corporation",
            "RB"                                     => "RB",
            "SBC"                                    => "SBC",
            "SBA"                                    => "SBA",
            "SSB"                                    => "SSB",
            "UCPB SAVINGS BANK"                      => "UCPB SAVINGS BANK",
            "Queen City Development Bank, Inc."      => "Queen City Development Bank, Inc.",
            "United Coconut Planters Bank"           => "United Coconut Planters Bank",
            "Wealth Development Bank, Inc."          => "Wealth Development Bank, Inc.",
            "Yuanta Savings Bank, Inc."              => "Yuanta Savings Bank, Inc.",
            "GrabPay"                                => "GrabPay",
            "Banco De Oro Unibank, Inc."             => "Banco De Oro Unibank, Inc.",
            "Bangko Mabuhay (A Rural Bank), Inc."    => "Bangko Mabuhay (A Rural Bank), Inc.",
            "BOC"                                    => "BOC",
            "CTBC"                                   => "CTBC",
            "Chinabank"                              => "Chinabank",
            "CBS"                                    => "CBS",
            "CBC"                                    => "CBC",
            "ALLBANK (A Thrift Bank), Inc."          => "ALLBANK (A Thrift Bank), Inc.",
            "BDO Network Bank, Inc."                 => "BDO Network Bank, Inc.",
            "Binangonan Rural Bank Inc"              => "Binangonan Rural Bank Inc",
            "Camalig"                                => "Camalig",
            "DBI"                                    => "DBI",
            "Gcash"                                  => "Globe Gcash",
            "Cebuana Lhuillier Rural Bank, Inc."     => "Cebuana Lhuillier Rural Bank, Inc.",
            "ISLA Bank (A Thrift Bank), Inc."        => "ISLA Bank (A Thrift Bank), Inc.",
            "Landbank of the Philippines"            => "Landbank of the Philippines",
            "Maybank Philippines, Inc."              => "Maybank Philippines, Inc.",
            "Metropolitan Bank and Trust Co"         => "Metropolitan Bank and Trust Co",
            "Omnipay"                                => "Omnipay",
            "Partner Rural Bank (Cotabato), Inc."    => "Partner Rural Bank (Cotabato), Inc.",
            "Paymaya Philippines, Inc."              => "Paymaya Philippines, Inc.",
            "Allied Banking Corp"                    => "Allied Banking Corp",
            "ING"                                    => "ING",
            "BPI Direct Banko, Inc., A Savings Bank" => "BPI Direct Banko, Inc., A Savings Bank",
            "CSB"                                    => "CSB",
            "BPI"                                    => "BPI",
        ];
        return $banks[$this->bankCode];
    }
}
