<?php

namespace Logic\Transfer\ThirdParty;

use Logic\Recharge\Recharge;

class SULPAY extends BASES {
    private $httpCode = '';

    //回调，不处理逻辑
    public function callbackMsg() {
        return 'success';
    }

    //请求代付接口
    public function runTransfer() {
        //组装参数
        //此代付没有回调，通过查询接口处理
        $data = [
            'payKey'            => $this->partnerID,
            'outTradeNo'        => $this->orderID,
            'orderPrice'        => bcdiv($this->money, 100, 2),
            'proxyType'         => 'T0',
            'productType'       => 'QUICKPAY',
            'bankAccountType'   => 'PRIVATE_DEBIT_ACCOUNT',
            'phoneNo'           => !empty($this->mobile) ? $this->mobile : '1234567890',
            'receiverName'      => $this->bankUserName,
            'certType'          => 'IDENTITY',
            'certNo'            => '123456789',
            'receiverAccountNo' => $this->bankCard,
            'bankClearNo'       => 'gcash',
            'bankBranchNo'      => 'gcash',
            'bankName'          => 'gcash',
            'bankCode'          => 'gcash',
            'bankBranchName'    => 'gcash',
        ];

        $data['sign']        = $this->sign($data);
        $this->payUrl        .= '/accountProxyPay/initPay';
        $this->parameter     = $data;
        $this->basePostNew();

        $result = json_decode($this->re, true);
        $code    = isset($result['resultCode']) ? $result['resultCode'] : '9999';
        $message = isset($result['errMsg']) ? $result['errMsg'] : 'errorMsg:' . (string)$this->re;
        if($this->httpCode == 200) {
            if($code == '0000' || $code == '9996') {
                $this->return['code']    = 10500;
                $this->return['balance'] = $this->money;
                $this->return['msg']     = $message;
                $this->transferNo        = $result['outTradeNo'];//第三方订单号
                //成功就直接返回了
                return;
            }
        }else{
            $message = 'http_code:' . $this->httpCode . 'errorMsg:' . json_encode($result, JSON_UNESCAPED_UNICODE);
            $this->updateTransferOrder($this->money, 0, '', '', 'failed', null, $message);
            $this->return['code']    = 886;
            $this->return['balance'] = 0;
            $this->return['msg']     = 'SULPAY:' . $message ?? '代付失败';
            return;
        }

        $this->return['code']    = 886;
        $this->return['balance'] = $this->money;
        $this->return['msg']     = $message;
        $this->transferNo        = '';//第三方订单号
    }

    //查询余额
    public function getThirdBalance() {
        $data = [
            'payKey'   => $this->partnerID,
            'nonceStr' => time(),
        ];
        $data['sign']        = $this->sign($data);
        $this->payUrl        .= '/query/balance';
        $this->parameter     = $data;
        $this->basePostNew();
        $result  = json_decode($this->re, true);

        $code    = isset($result['resultCode']) ? $result['resultCode'] : '9999';
        $message = isset($result['errMsg']) ? $result['errMsg'] : 'errorMsg:' . (string)$this->re;


        if($this->httpCode == 200) {
            if($code == '0000') {
                $this->return['code']    = 10509;
                $this->return['balance'] = bcmul($result['QUICKPAY-T0'], 100, 0);
                $this->return['msg']     = $message;
                return;
            }
        }
        $this->_end();
        throw new \Exception('http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message);
    }

    //查询代付结果
    public function getTransferResult() {
        $data = [
            'payKey'     => $this->partnerID,
            'outTradeNo' => $this->orderID,
        ];
        $data['sign']        = $this->sign($data);
        $this->payUrl        .= '/proxyPayQuery/query';
        $this->parameter     = $data;

        $this->basePostNew();

        $result = json_decode($this->re, true);
        $code    = isset($result['resultCode']) ? $result['resultCode'] : 9999;
        $message = isset($result['errMsg']) ? $result['errMsg'] : 'errorMsg:' . (string)$this->re;
        $message .= " http_code:  {$this->httpCode} ";
        if($this->httpCode == 200) {
            if($code == '0000'){
                //订单状态 tradeResult (REMIT_SUCCESS 交易成功 REMITTING 打款中  REMIT_FAIL 打款失败)
                if($result['remitStatus'] == 'REMIT_SUCCESS') {
                    $status       = 'paid';
                    $this->return = ['code' => 1, 'msg' => $message];
                } elseif($result['remitStatus'] == 'REMIT_FAIL') {
                    $status       = 'failed';
                    $this->return = ['code' => 0, 'msg' => $message];
                } else {
                    $this->return = ['code' => 0, 'msg' => $message];
                    return;
                }

                $real_money = bcmul($result['settAmount'], 100);
                $fee        = $this->money - $real_money;
                $this->updateTransferOrder($this->money, $real_money, $result['outTradeNo'],//第三方转账编号
                    '', $status, $fee, $message);
                return;
            }
        }

        $this->return = ['code' => 886, 'transfer' => '', 'msg' => 'http_code:' . $this->httpCode . ' message:' . $message];
    }

    public function basePostNew() {
        $this->payRequestUrl = $this->payUrl;
        $ch                  = curl_init();
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
    public function sign($param) {
        unset($param['sign']);
        unset($param['s']);

        $newParam = $param;
        if (!empty($newParam)) {
            $fields = array_keys($newParam);
            $sortParam = [];
            sort($fields);
            foreach ($fields as $k => $v) {
                if(empty($newParam[$v])){
                    continue;
                }
                $sortParam[] = $v . '=' . $newParam[$v];
            }
            $originalString = implode('&', $sortParam) . '&paySecret=' . $this->key;
        } else {
            $originalString = $this->key;
        }

        return strtoupper(md5($originalString));
    }

    /**
     * 代付异步回调
     * @param array $params 异步参数
     * @return mixed
     */
    public function callbackResult($params) {
        //此代付没有回调
        throw new \Exception('error');
        $this->parameter = $params;
        
        if($this->sign($params) != $params['sign']) {
            throw new \Exception('Sign error');
        }

        //订单状态已经成功 或者 消息与订单状态一致
        if($this->order['status'] == 'paid') {
            return;
        }
        $result= $params;
        $amount     = bcmul($result['transferAmount'], 100);//以分为单位
        $real_money = $amount;//实际到账金额

        //订单状态付款状态：(：1： 成功2： 失败 )
        if($result['tradeResult'] == 1) {
            $status       = 'paid';
            $this->return = ['code' => 1, 'msg' => ''];
        } elseif($result['tradeResult'] == 2) {
            $status       = 'failed';
            $this->return = ['code' => 0, 'msg' => '代付失败'];
        } else {
            $this->return = ['code' => 0, 'msg' => 'error'];
            return;
        }

        $this->re = $this->return;
        $this->updateTransferOrder($this->money, $real_money, $result['tradeNo'],//第三方转账编号
            '', $status);
    }

    /**
     * 银行名称
     * @return mixed
     */
    private function getBankName()
    {
        global $app;
        $ci = $app->getContainer();
        $country_code = '';
        if(isset($ci->get('settings')['website']['site_type'])){
            $country_code = $ci->get('settings')['website']['site_type'];
        }

        if($country_code == 'ncg'){
            return 'gcash';
        }else{
            $banks = [
                "AUB" => "AUB",
                "Starpay" => "SPY",
                "PBC" => "PBC",
                "PBB" => "PBB",
                "PNB" => "PNB",
                "PSB" => "PSB",
                "PTC" => "PTC",
                "PVB" => "PVB",
                "RBG" => "RBG",
                "SBA" => "SBA",
                "SSB" => "SSB",
                "UCPB SAVINGS BANK" => "USB",
                "GrabPay" => "GBY",
                "BOC" => "BOC",
                "CBS" => "CBS",
                "CBC" => "CBC",
                "DBI" => "DBI",
                "Gcash" => "GCASH",
                "ISLA Bank (A Thrift Bank), Inc." => "ISL",
                "Maybank Philippines, Inc." => "MPI",
                "Partner Rural Bank (Cotabato), Inc." => "PAR",
                "Paymaya Philippines, Inc." => "PPI",
                "ING" => "ING",
                "BPI" => "BPI",
                "SCB" => "SCB"
            ];
            return $banks[$this->bankCode];
        }
    }

}
