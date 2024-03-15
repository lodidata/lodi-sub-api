<?php

namespace Logic\Transfer\ThirdParty;

/**
 *
 * POPPAY代付
 */
class POPPAY extends BASES
{
    private $httpCode = '';

    //回调，不处理逻辑
    public function callbackMsg(){
        return 'success';
    }

    //请求代付接口
    public function runTransfer()
    {
        /*if($this->money % 100){
            throw new \Exception('Transfer only supports integer');
        }*/

        $params = array(
            'merchant_id'           =>  $this->partnerID, //
            'merchant_trade_no'     => $this->orderID, //是	string	商户订单号 Merchant order number
            'amount'                => bcdiv($this->money, 100,2),//代付金额
            'pay_type'              => 'BANK',
            'description'           => 'payout',
            'customer_name'         => $this->bankUserName,
            'customer_mobile'       => $this->mobile,
            'customer_address'      => 'ph',
            'account_no'            => $this->bankCard, // string 收款银行账号 (示例：6227888888888888)
            'bank_code'             => $this->getBankName(),  //string 收款银行编号
            'barangay'              => 'Maharlika',
            'city'                  => 'Pasig',
            'zip_code'              => '1110',
            'gender'                => 'Male',
            'customer_first_name'   => 'James',
            'customer_middle_name'  => 'bob',
            'customer_last_name'    => 'pp',
            'notify_url'            => $this->payCallbackDomain . '/thirdAdvance/callback/poppay',  //string 异步回调地址 (当代付完成时，平台将向此URL地址发送异步通知。建议使用 https)，
        );

        //判断 额外参数是否为空 Determine whether the extra parameter is empty
        $this->initParam($params);
        $this->payUrl .= '/trade/payout/direct';

        $this->basePostNew();
        $result  = json_decode($this->re, true);
        $code    = isset($result['ret_code']) ? $result['ret_code'] : 1;
        $message = isset($result['ret_msg']) ? $result['ret_msg'] : 'errorMsg:'.(string)$this->re;

        if ($this->httpCode == 200) {
            //1-支付成功，2-支付失败 3-审核中 4-处理中 5-冲正退回 poppay 回的3
            if($code == '0000') {
                if($result['trade_status'] == 3 || $result['trade_status'] == 1)
                $this->return['code']           = 10500;
                $this->return['balance']        = $this->money;
                $this->return['msg']            = $message;
                $this->transferNo               = $result['platform_trade_no'];//第三方订单号
                //成功就直接返回了
                return;
            }

            $message = 'http_code:'.$this->httpCode.'errorMsg:'.json_encode($result, JSON_UNESCAPED_UNICODE);
            $this->updateTransferOrder(
                $this->money,
                0,
                '',
                '',
                'failed',
                null,
                $message
            );
            $this->return['code'] = 886;
            $this->return['balance'] = 0;
            $this->return['msg'] = 'POPPAY:' . $message ?? '代付失败';
            return;
        }

        $this->return['code']           = 886;
        $this->return['balance']        = $this->money;
        $this->return['msg']            = $message;
        $this->transferNo               = '';//第三方订单号
    }


    //查询余额
    public function getThirdBalance()
    {
        $params = [
            'merchant_id' => $this->partnerID,
        ];
        $this->payUrl .= '/balance/query';

        $this->initParam($params);
        $this->basePostNew ();

        $result  = json_decode($this->re, true);
        $code    = isset($result['ret_code']) ? $result['ret_code'] : 1;
        $message = isset($result['ret_msg']) ? $result['ret_msg'] : 'errorMsg:'.(string)$this->re;

        if ($this->httpCode == 200) {
            if($code === '0000'){
                $this->return['code']    = 10509;
                $this->return['balance'] = bcmul($result['balance'], 100);
                $this->return['msg']     = $message;
                return;
            }

        }
        $this->_end();
        throw new \Exception('http_code:'.$this->httpCode.' code:'.$code.' message:'.$message);
    }

    //查询代付结果
    public function getTransferResult()
    {
        $params = [
            'merchant_id'       => $this->partnerID,
            'trade_type'        => 'payout',
            'merchant_trade_no' => $this->orderID,
            'timestamp'         => date('Y-m-d H:i:s'),
        ];
        $this->payUrl    .= '/trade/query';
        $this->initParam($params);
        $this->basePostNew();

        $result     = json_decode($this->re, true);
        $code       = isset($result['ret_code']) ? $result['ret_code'] : 1;
        $message    = isset($result['ret_msg']) ? $result['ret_msg'] : 'errorMsg:'.(string)$this->re;

        if ($this->httpCode == 200) {
            if($code == '0000'){
                //订单状态：0-待支付，1-支付成功，2-支付失败 3-审核中 4-处理中 5-冲正退回
                if($result['trade_status'] == 1){
                    $status = 'paid';
                    $this->return = ['code' => 1,  'msg' => $message];

                }elseif($result['trade_status'] == 2){
                    $status = 'failed';
                    $this->return = ['code' => 0,  'msg' => $message];
                }elseif($result['trade_status'] === 3){
                    $this->return = ['code' => 0,  'msg' => $message];
                    return;
                }else{
                    $this->return = ['code' => 0,  'msg' => $message];
                    return;
                }

                $real_money = bcmul($result['actual_amount'], 100);
                $fee        = $this->money - $real_money;
                $this->updateTransferOrder(
                    $this->money,
                    $real_money,
                    $result['platform_trade_no'],
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
    public function initParam($params=[])
    {
        //请求参数 Request parameter
        $data = array(
            'timestamp' => date('Y-m-d H:i:s'),
        );

        $params && $data = array_merge($data, $params);
        $data['sign']    = $this->sign($data);  //校验码
        $this->parameter = $data;
    }

    //生成签名
    public function sign($data) {
        unset($data['sign']);
        ksort($data);
        reset($data);

        $str = '';
        foreach ($data as $k => $v){
            if(is_null($v) || $v === '') continue;
            $str .= $k.'='.$v.'&';
        }
        $prikey = "-----BEGIN RSA PRIVATE KEY-----\n".wordwrap($this->key, 64, "\n", true)."\n-----END RSA PRIVATE KEY-----";
        $key = openssl_get_privatekey($prikey);
        openssl_sign($str, $sign, $key);
        openssl_free_key($key);
        return base64_encode($sign);
    }

    //验证回调签名
    public function verifySign($data) {
        $sign = base64_decode($data['sign']);
        unset($data['sign']);
        ksort($data);
        reset($data);

        $str = '';
        foreach ($data as $k => $v){
            if(is_null($v) || $v === '') continue;
            $str .= $k.'='.$v.'&';
        }
        $pubkey = "-----BEGIN PUBLIC KEY-----\n".wordwrap($this->pubKey, 64, "\n", true)."\n-----END PUBLIC KEY-----";
        $key = openssl_pkey_get_public($pubkey);
        if(openssl_verify($str, $sign, $key, OPENSSL_ALGO_SHA1) === 1){
            return true;
        }
        return false;
    }


    public function basePostNew($referer = null)
    {
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
        ]);
        if ($referer) {
            curl_setopt($ch, CURLOPT_REFERER, $referer);
        }
        $response = curl_exec($ch);
        $this->curlError = curl_error($ch);
        $this->httpCode  = curl_getinfo($ch,CURLINFO_HTTP_CODE);
        $this->re        = $response;
        curl_close($ch);

    }


    /**
     * 代付异步回调
     * @param array $params 异步参数
     * @return mixed
     */
    public function callbackResult($params)
    {
        $this->parameter  = $params;

        if(!$this->verifySign($params)){
            throw new \Exception('Sign error');
        }

        //订单状态已经成功 或者 消息与订单状态一致
        if($this->order['status'] == 'paid'){
            return;
        }

        $real_money     = bcmul($params['amount'] , 100);//以分为单位

        if($params['error_code'] == '0000'){
            //订单状态：1-支付成功，2-支付失败 3-审核中 4-处理中 5-冲正退回
            if($params['trade_status'] == 1){
                $status = 'paid';
                $this->return = ['code' => 1,  'msg' => ''];
            }elseif($params['trade_status'] == 2){
                $status = 'failed';
                $this->return = ['code' => 0, 'msg' => '代付失败'];
            }else{
                $this->return = ['code' => 0, 'msg' => $params['error_msg']];
                return;
            }
        }
        $message = isset($params['error_msg']) ? $params['error_msg'] : '';

        $this->re = $this->return;
        $this->updateTransferOrder(
            $this->money,
            $real_money,
            $params['platform_trade_no'],//第三方转账编号
            '',
            $status,
            0,
            $message
        );
    }

    /**
     * 银行名称
     * @return mixed
     */
    private function getBankName()
    {

        $banks = [
            "AUB" => "AUB",
            "UnionBank EON" => "UnionBank EON",
            "Starpay" => "Starpay",
            "EB" => "EB",
            "ESB" => "ESB",
            "MB" => "MB",
            "ERB" => "ERB",
            "PB" => "PB",
            "PBC" => "PBC",
            "PBB" => "PBB",
            "PNB" => "PNB",
            "PSB" => "PSB",
            "PTC" => "PTC",
            "PVB" => "PVB",
            "RBG" => "RBG",
            "Rizal Commercial Banking Corporation" => "Rizal Commercial Banking Corporation",
            "RB" => "RB",
            "SBC" => "SBC",
            "SBA" => "SBA",
            "SSB" => "SSB",
            "UCPB SAVINGS BANK" => "UCPB SAVINGS BANK",
            "Queen City Development Bank, Inc." => "Queen City Development Bank, Inc.",
            "United Coconut Planters Bank" => "United Coconut Planters Bank",
            "Wealth Development Bank, Inc." => "Wealth Development Bank, Inc.",
            "Yuanta Savings Bank, Inc." => "Yuanta Savings Bank, Inc.",
            "GrabPay" => "GrabPay",
            "Banco De Oro Unibank, Inc." => "Banco De Oro Unibank, Inc.",
            "Bangko Mabuhay (A Rural Bank), Inc." => "Bangko Mabuhay (A Rural Bank), Inc.",
            "BOC" => "BOC",
            "CTBC" => "CTBC",
            "Chinabank" => "Chinabank",
            "CBS" => "CBS",
            "CBC" => "CBC",
            "ALLBANK (A Thrift Bank), Inc." => "ALLBANK (A Thrift Bank), Inc.",
            "BDO Network Bank, Inc." => "BDO Network Bank, Inc.",
            "Binangonan Rural Bank Inc" => "Binangonan Rural Bank Inc",
            "Camalig" => "Camalig",
            "DBI" => "DBI",
            "Gcash" => "Globe Gcash",
            "Cebuana Lhuillier Rural Bank, Inc." => "Cebuana Lhuillier Rural Bank, Inc.",
            "ISLA Bank (A Thrift Bank), Inc." => "ISLA Bank (A Thrift Bank), Inc.",
            "Landbank of the Philippines" => "Landbank of the Philippines",
            "Maybank Philippines, Inc." => "Maybank Philippines, Inc.",
            "Metropolitan Bank and Trust Co" => "Metropolitan Bank and Trust Co",
            "Omnipay" => "Omnipay",
            "Partner Rural Bank (Cotabato), Inc." => "Partner Rural Bank (Cotabato), Inc.",
            "Paymaya Philippines, Inc." => "Paymaya Philippines, Inc.",
            "Allied Banking Corp" => "Allied Banking Corp",
            "ING" => "ING",
            "BPI Direct Banko, Inc., A Savings Bank" => "BPI Direct Banko, Inc., A Savings Bank",
            "CSB" => "CSB",
            "BPI" => "BPI",
        ];
        return $banks[$this->bankCode];
    }
}
