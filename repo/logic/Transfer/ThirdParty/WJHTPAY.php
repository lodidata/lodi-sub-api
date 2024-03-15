<?php

namespace Logic\Transfer\ThirdParty;

use Logic\Recharge\Recharge;
use Utils\Curl;

/**
 *
 * WJHTPAY代付
 */
class WJHTPAY extends BASES {
    private $httpCode = '';

    //回调，不处理逻辑
    public function callbackMsg() {
        return 'success';
    }

    //请求代付接口
    public function runTransfer() {
        $params          = [
            "merchant"          => $this->partnerID, //商户号
            "total_amount"      => bcdiv($this->money, 100, 2),   //支付金额
            "order_id"          => $this->orderID,   //订单号
            'callback_url'      => $this->payCallbackDomain . '/thirdAdvance/callback/wjhtpay',
            'bank'              => trim($this->getBankName()),
            'bank_card_name'    => trim($this->bankUserName),
            'bank_card_account' => trim($this->bankCard),
            'bank_card_remark'  => trim($this->bankCard),
        ];
        $this->parameter = $params;
        //判断 额外参数是否为空 Determine whether the extra parameter is empty
        $this->payUrl .= '/api/daifu';

        $this->initParam($params);
        $this->basePostNew();
        $result  = isset($this->re) ? json_decode($this->re, true) : '';
        $code    = isset($result['status']) ? $result['status'] : 0;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;
        if($this->httpCode == 200) {
            //true成功,false失败
            if($code == '1') {
                $this->return['code']    = 10500;
                $this->return['balance'] = $this->money;
                $this->return['msg']     = $message;
                $this->transferNo        = $this->orderID;   //第三方订单号
                //成功就直接返回了
                return;
            }else{
                $message = 'http_code:' . $this->httpCode . 'errorMsg:' . json_encode($result, JSON_UNESCAPED_UNICODE);
                $this->updateTransferOrder($this->money, 0, '', '', 'failed', null, $message);
                $this->return['code']    = 886;
                $this->return['balance'] = 0;
                $this->return['msg']     = 'WJHTPAY:' . $message ?? '代付失败';
                return;
            }
        }

        $this->return['code']    = 886;
        $this->return['balance'] = $this->money;
        $this->return['msg']     = $message;
        $this->transferNo        = '';   //第三方订单号

    }

    //查询余额
    public function getThirdBalance() {
        $params       = [
            'merchant' => $this->partnerID,
        ];
        $this->payUrl .= "/api/me";
        $this->initParam($params);
        $this->basePostNew();
        $result  = json_decode($this->re, true);
        $code    = isset($result['status']) ? $result['status'] : 1;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;

        if($this->httpCode == 200) {
            if($code == 1) {
                $this->return['code']    = 10509;
                $this->return['balance'] = bcmul($result['balance'], 100);
                $this->return['msg']     = 'success';
                return;
            }
        }
        $this->_end();
        throw new \Exception('http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message);
    }

    //代付订单查询
    public function getTransferResult() {
        $params       = [
            'merchant' => $this->partnerID,
            'order_id' => $this->orderID
        ];
        $this->payUrl .= "/api/query";
        $this->initParam($params);
        $this->basePostNew();

        $result  = json_decode($this->re, true);
        $code    = isset($result['status']) ? $result['status'] : 0;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;

        if($this->httpCode == 200) {
            //订单状态：0 处理中 1 成功 2 失败
            if($code == '5') {
                $status       = 'paid';
                $this->return = ['code' => 1, 'msg' => $message];
            } elseif($code == '3') {
                $status       = 'failed';
                $this->return = ['code' => 0, 'msg' => $message];
            } else{
                $status       = "pending";
                $this->return = ['code' => 0, 'msg' => $message];
            }

            $real_money = bcmul($result['amount'], 100);
            $fee        = $this->money - $real_money;
            $this->updateTransferOrder($this->money, $real_money, $result['order_id'], '', $status, $fee, $message);
            return;
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
            if(is_null($v) || $v === '')
                continue;
            $str .= $k . '=' . $v . '&';
        }
        $str = trim($str, '&');

        $sign_str = $str . '&key=' . $this->key;

        return md5($sign_str);
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
        if($this->sign($params) != $params['sign']) {
            throw new \Exception('Sign error');
        }

        //订单状态已经成功 或者 消息与订单状态一致
        if($this->order['status'] == 'paid') {
            return;
        }
        $amount     = bcmul($params['amount'], 100);//以分为单位
        $real_money = $amount;//实际到账金额

        //订单状态付款状态：(//订单状态：0 处理中 1 成功 2 失败)
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
        $this->updateTransferOrder($this->money, $real_money, $params['order_id'],//第三方转账编号
            '', $status);
    }

    /**
     * 银行名称
     * @return mixed
     */
    private function getBankName() {

        $banks = [
            "AUB"                                    => "AUB",
            "Starpay"                                => "STP",
            "ESB"                                    => "ESB",
            "MB"                                     => "MBS",
            "PBC"                                    => "PBC",
            "PBB"                                    => "PBB",
            "PNB"                                    => "PNB",
            "PSB"                                    => "PSB",
            "PTC"                                    => "PTC",
            "SBC"                                    => "SBC",
            "SSB"                                    => "SSB",
            "United Coconut Planters Bank"           => "UCPB",
            "Wealth Development Bank, Inc."          => "WDB",
            "GrabPay"                                => "GP",
            "Bangko Mabuhay (A Rural Bank), Inc."    => "BM",
            "CTBC"                                   => "CTBC",
            "CBS"                                    => "CBS",
            "CBC"                                    => "CBC",
            "ALLBANK (A Thrift Bank), Inc."          => "AB",
            "BDO Network Bank, Inc."                 => "BNB",
            "Camalig"                                => "CB",
            "Gcash"                                  => "gcash",
            "Cebuana Lhuillier Rural Bank, Inc."     => "CLB",
            "ISLA Bank (A Thrift Bank), Inc."        => "ISLA",
            "Landbank of the Philippines"            => "LBOB",
            "Maybank Philippines, Inc."              => "MBP",
            "Metropolitan Bank and Trust Co"         => "mbt",
            "Omnipay"                                => "OP",
            "Partner Rural Bank (Cotabato), Inc."    => "PRB",
            "Paymaya Philippines, Inc."              => "PMP",
            "ING"                                    => "IB",
            "BPI Direct Banko, Inc., A Savings Bank" => "BK",
            "BPI"                                    => "bpi",
            "BPI Bank"                               => "bpi",
            "BDO Unibank"                            => "Unibank",
            "LANDBANK / OFBank"                      => "LBOB",
            "Security Bank Corporation"              => "SBC",
            "UBP"        =>                          "Union Bank of the Philippines",
            "EWBC"       =>                          "East West Banking Corporation",
            "RCBC"       =>                          "RCBC/DiskarTech",
            "UCPB"       =>                          "United Coconut Planters Bank (UCPB)",
            "DBP"       =>                           "Development Bank of the Philippines",
            "AB"       =>                            "ALLBANK(A Thirft Bank)",
            "Asenso"       =>                        "Rural Bank of Guinobatan / Asenso",
            "BM"       =>                            "Bangko Mabuhay",
            "BC"       =>                            "Bank of Commerce",
            "BK"       =>                            "BanKo,A Subsidiary of BPI",
            "Bayad"       =>                         "CIS Bayad Center / Bayad",
            "BNB"       =>                           "BDO NeTwork Bank",
            "CB"       =>                            "Camalig Bank",
            "CARD Bank"       =>                     "CARD Bank",
            "CLB"       =>                           "Cebuana Lhuillier Bank / Cebuana Xpress",
            "Coins"       =>                          "DCPay / COINS.PH",
            "DCDB"       =>                          "Dumaguete City Development Bank",
            "DB"       =>                           "Dungganon Bank",
            "GP"       =>                           "GrabPay",
            "ISLA"       =>                          "ISLA Bank",
            "JC"       =>                          "Zybi Tech Inc. / JuanCash",
            "Komo"       =>                          "East West Rural Bank / Komo",
            "LSB"       =>                          "Legazpi Saving Bank",
            "MBS"       =>                          "Malayan Bank Savings and Mortgage Bank, Inc.",
            "MBP"       =>                          "Maybank Philippnies",
            "MCCB"       =>                          "Mindanao Consolidated CoopBank",
            "NB"       =>                          "Netbank",
            "OP"       =>                          "OmniPay, Inc.",
            "PRB"       =>                          "Partner Rural Bank (Cotabato), Inc.",
            "PMP"       =>                          "PayMaya Philippines",
            "PDB"       =>                          "Producers Bank",
            "QB"       =>                          "Queenbank",
            "QCRB"       =>                          "Quezon Capital Rural Bank",
            "RBB"       =>                          "Robinsons Bank Corporation",
            "SB"       =>                          "Seabank",
            "SP"       =>                          "ShopeePay",
            "SCB"       =>                          "Standard Chartered Bank",
            "STP"       =>                          "Starpay",
            "SLB"       =>                          "Sterling Bank of Asia",
            "TC"       =>                          "TayoCash",
            "USB"       =>                          "UCPB Savings Bank",
            "USSC"       =>                          "USSC Monet Services",
            "VB"       =>                          "Veterans Bank",
            "WDB"       =>                          "Wealth Development Bank",
            "usdt-trc"       =>                      "usdt-trc",
            "(TRC)"       =>                      "USDT(TRC)",
            "ERC"       =>                      "USDT-ERC",
            "ALIPAY"       =>                      "Alipay / Lazada Wallet",
            "BRB"       =>                      "Binangonan Rural Bank / BRBDigital",
            "BCH"       =>                      "Bank of China",
            "SME"       =>                      "CARD SME Bank",
            "CPI"       =>                      "CIMB Philippines, Inc.",
            "ERB"       =>                      "Entrepreneur Rural Bank, Inc./ENTRP",
            "GOT"       =>                      "GoTyme Bank",
            "IRI"       =>                      "I-Remit / iCASH",
            "IEM"       =>                      "Infoserve / Nationlink",
            "LDB"       =>                      "Luzon Development Bank",
            "MYA"       =>                      "Maya Bank, Inc.",
            "PAS"       =>                      "Pacific Ace Savings Bank",
            "PPS"       =>                      "PalawanPay",
            "TDB"       =>                      "Tonik Bank",
            "TPI"       =>                      "TraxionPay/DigiCOOP/COOPNET",
            "UDB"       =>                      "UnionDigital Bank",
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
