<?php

namespace Logic\Transfer\ThirdParty;

/**
 *
 * YFBPAY代付
 */
class YFBPAY extends BASES
{
    private $httpCode = '';

    //回调，不处理逻辑
    public function callbackMsg(){
        return 'success';
    }

    //请求代付接口
    public function runTransfer()
    {
        if($this->money % 100){
            $message = 'Transfer only supports integer';
            $this->updateTransferOrder(
                $this->money,
                0,
                '',
                '',
                'failed',
                null,
                $message
            );

            throw new \Exception('Transfer only supports integer');
            
        }

        $params = array(
            'orderNo'      => $this->orderID, //是	string	商户订单号 Merchant order number
            'amount'       => bcdiv($this->money, 100),//代付金额 (单位：฿，不支持小数；代付金额范围请与优付宝平台确认)
            'name'         => $this->bankUserName, //收款人姓名 (示例：张三)
            'bankName'     => $this->getBankName(),  //string 收款银行名称 (示例：中国建设银行)
            'bankAccount'  => $this->bankCard, // string 收款银行账号 (示例：6227888888888888)
            'bankBranch'   => null,
            'memo'         => null,
            'mobile'       => null,
            'datetime'     => date('Y-m-d H:i:s'), // string (date-time) 日期时间 (格式:2020-01-01 23:59:59)
            'notifyUrl'    => $this->payCallbackDomain . '/thirdAdvance/callback/yfbpay',  //string 异步回调地址 (当代付完成时，平台将向此URL地址发送异步通知。建议使用 https)，
            'reverseUrl'   => null,
            'extra'        => null,
        );

        //判断 额外参数是否为空 Determine whether the extra parameter is empty
        $this->initParam($params);

        $this->basePostNew('/payout/create');
        $result  = json_decode($this->re, true);
        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['text']) ? $result['text'] : 'errorMsg:'.(string)$this->re;

        if ($this->httpCode == 200) {
            if($code == 0){
                $this->return['code']           = 10500;
                $this->return['balance']        = $result['amount'] * 100;
                $this->return['msg']            = $message;
                $this->transferNo               = $result['tradeNo'];
                return;
            }else{
                //代付失败，改成失败状态
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
                $this->return['msg'] = 'YFBPAY:' . $message ?? '代付失败';
                return;
            }
        }

        $this->return['code']           = 886;
        $this->return['balance']        = $this->money;
        $this->return['msg']            = $message;
        $this->transferNo               = '';
    }


    //查询余额
    public function getThirdBalance()
    {
        $this->initParam();
        $this->basePostNew ('/payout/balance');

        $result  = json_decode($this->re, true);
        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['text']) ? $result['text'] : 'errorMsg:'.(string)$this->re;

        if ($this->httpCode == 200) {
            if($code == 0){
                $this->return['code']    = 10509;
                $this->return['balance'] = $result['balance']*100;
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
            'tradeNo' => $this->transferNo,
            'orderNo' => $this->orderID,
        ];

        $this->initParam($params);
        $this->basePostNew('/payout/status');

        $result     = json_decode($this->re, true);
        $code       = isset($result['code']) ? $result['code'] : 1;
        $message    = isset($result['text']) ? $result['text'] : 'errorMsg:'.(string)$this->re;

        if ($this->httpCode == 200) {
            if($code == 0){
                //订单状态
                if($result['status'] === 'PAID'){
                    $status = 'paid';
                    $this->return = ['code' => 1,  'msg' => $message];

                }elseif($result['status'] === 'CANCELLED'){
                    $status = 'failed';
                    $this->return = ['code' => 0,  'msg' => $message];
                }else{
                    $this->return = ['code' => 0,  'msg' => $result['status']];
                    return;
                }

                $real_money = $result['paid'] * 100;
                $fee        = $this->money - $real_money;
                $this->updateTransferOrder(
                    $this->money,
                    $real_money,
                    $result['tradeNo'],
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
            'merchantNo'        => $this->partnerID,//	是	string	商户号 business number
            'time'              => time(),//	是	integer	发送请求的 10 位时间戳 10-bit timestamp of sending request
            'appSecret'         => $this->pubKey,//	是	string	默认为 MD5 Default is MD5
        );

        $params && $data = array_merge($data, $params);
        $data['sign']           = $this->sign($data);  //校验码
        $this->parameter = $data;
    }

    /**
     * 获取代付平台 的银行code
     */

    //生成签名
    public function sign($data)
    {
        unset($data['bankBranch']);
        unset($data['memo']);
        unset($data['appSecret']);
        unset($data['sign']);
        ksort($data);
        reset($data);

        $str = '';
        foreach ($data as $k => $v){
            $str .= $k.'='.$v.'&';
        }
        $str = trim($str, '&');

        $sign_str       = $str . $this->key;
        $digest         = hash("sha256", $sign_str);
        $sign           = strtoupper(md5($digest));
        return $sign;
    }




    public function basePostNew($str, $referer = null)
    {
        $this->payRequestUrl = $this->payUrl . $str;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->payUrl . $str);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->parameter));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
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
        $field = [
            'status'      => $params['status'],
            'tradeNo'     => $params['tradeNo'],
            'orderNo'     => $params['orderNo'],
            'amount'      => $params['amount'],
            'name'        => $params['name'],
            'bankName'    => $params['bankName'],
            'bankAccount' => $params['bankAccount'],
            'bankBranch'  => $params['bankBranch'],
            'memo'        => $params['memo'],
            'mobile'      => $params['mobile'],
            'fee'         => $params['fee'],
            'extra'       => $params['extra'],
            'sign'        => $params['sign'],
        ];

        if($this->sign($field) != $params['sign']){
            throw new \Exception('Sign error');
        }

        //订单状态已经成功 或者 消息与订单状态一致
        if($this->order['status'] == 'paid'){
            return;
        }

        $amount     = $field['amount'] * 100;//以分为单位
        $fee        = $field['fee'] * 100;//以分为单位
        $real_money = $amount - $fee;//实际到账金额

        //金额不一致
        if($this->money != $amount) {
            throw new \Exception('Inconsistent amount');
        }

        //订单状态
        if($field['status'] === 'PAID'){
            $status = 'paid';
            $this->return = ['code' => 1,  'msg' => ''];
        }else{
            $status = 'failed';
            $this->return = ['code' => 0, 'msg' => '代付失败'];
        }
        $this->re = $this->return;
        $this->updateTransferOrder(
            $this->money,
            $real_money,
            $field['tradeNo'],
            '',
            $status,
            $fee,
            $params['extra']
        );
    }

    /**
     * 银行名称
     * @return mixed
     */
    private function getBankName()
    {
        $banks = [
            "Allied Banking Corp"                  => "ABC",
            "AUB"                                  => "AUB",
            "BOC"                                  => "BOC",
            "BPI"                                  => "BPI",
            "PBC"                                  => "PBC",
            "Chinabank"                            => "CHINABANK",
            "CTBC"                                 => "CTBC",
            "Gcash"                                => "GCASH",
            "ING"                                  => "ING",
            "Landbank of the Philippines"          => "LANDBANK",
            "Metropolitan Bank and Trust Co"       => "METROBANK",
            "PNB"                                  => "PNB",
            "Rizal Commercial Banking Corporation" => "RCBC",
            "SBC"                                  => "SBC",
            "United Coconut Planters Bank"         => "UCPB",
            "Yuanta Savings Bank, Inc."            => "YSB",
        ];

        return $banks[$this->bankCode];
    }
}
