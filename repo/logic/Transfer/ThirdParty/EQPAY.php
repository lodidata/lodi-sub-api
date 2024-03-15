<?php

namespace Logic\Transfer\ThirdParty;

use Logic\Recharge\Recharge;
use Utils\Curl;

/**
 *
 * EQPAY代付
 */
class EQPAY extends BASES {
    private $httpCode = '';

    //回调，不处理逻辑
    public function callbackMsg() {
        return 'success';
    }

    //请求代付接口
    public function runTransfer() {
        $params          = [
            "username"    => $this->partnerID, //商户号
            "amount"  => bcdiv($this->money, 100, 2),   //代付金额
            "order_number" => $this->orderID,   //订单号
            "bank_name"   => $this->getBankName(),            //银行名称
            "bank_card_number"  => $this->bankCard,            //银行帐号
            "bank_card_holder_name"  => $this->bankUserName,   //持有人姓名
            "notify_url"       => $this->payCallbackDomain . '/thirdAdvance/callback/eqpay',   //回调地址
        ];

        $this->parameter = $params;
        //判断 额外参数是否为空 Determine whether the extra parameter is empty
        $this->payUrl .= '/api/v1/third-party/agency-withdraws';

        $this->initParam($params);
        $this->basePostNew();
        $result  = isset($this->re) ? json_decode($this->re, true) : '';
        $code    = isset($result['http_status_code']) ? $result['http_status_code'] : 1;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;

        if($this->httpCode == 200) {
            //true成功,false失败
            if( in_array($code, ['200', '201']) ) {
                $this->return['code']    = 10500;
                $this->return['balance'] = $this->money;
                $this->return['msg']     = $message;
                $this->transferNo        = $result['data']['system_order_number'];//第三方订单号
                //成功就直接返回了
                return;
            }else{
                $message = 'http_code:' . $this->httpCode . 'errorMsg:' . json_encode($result, JSON_UNESCAPED_UNICODE);
                $this->updateTransferOrder($this->money, 0, '', '', 'failed', null, $message);
                $this->return['code']    = 886;
                $this->return['balance'] = 0;
                $this->return['msg']     = 'EQPAY:' . $message ?? '代付失败';
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
            'username' => $this->partnerID
        ];
        $this->payUrl .= "/api/v1/third-party/profile-queries";
        $this->initParam($params);
        $this->basePostNew();
        $result  = json_decode($this->re, true);
        $code    = isset($result['http_status_code']) ? $result['http_status_code'] : 1;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;

        $config_params = !empty($this->thirdConfig['params']) ? json_decode($this->thirdConfig['params'],true) : [];

        if($this->httpCode == 200) {
            if( in_array($code, ['200', '201']) ) {
                $balance = $result['data']['available_balance']??0;
                $this->return['code']    = 10509;
                $this->return['balance'] = bcmul($balance ,100);
                $this->return['msg']     = json_encode($result['data']);
                return;
            }
        }
        $this->_end();
        throw new \Exception('http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message);
    }

    //代付订单查询
    public function getTransferResult() {
        $params = [
            'username'      => $this->partnerID,
            'order_number' => $this->orderID
        ];

        $this->payUrl .="/api/v1/third-party/withdraw-queries";
        $this->initParam($params);
        $this->basePostNew();

        $result     = json_decode($this->re, true);
        $code       = isset($result['http_status_code']) ? $result['http_status_code'] : 1;
        $message    = isset($result['message']) ? $result['message'] : 'errorMsg:'.(string)$this->re;

        if ($this->httpCode == 200) {
            if( in_array($code, ['200', '201'])){
                $resultData=$result['data'];
                //订单状态：1、2、3 、11 处理中4、5 成功6、7、8 失败
                if(in_array( $resultData['status'], [4, 5] )){
                    $status = 'paid';
                    $this->return = ['code' => 1,  'msg' => $message];
                }elseif( in_array( $resultData['status'], [6, 7, 8] ) ){
                    $status = 'failed';
                    $this->return = ['code' => 0,  'msg' => $message];
                }elseif( in_array( $resultData['status'], [1, 2, 3, 11] ) ){
                    $status="pending";
                    $this->return = ['code' => 0,  'msg' => $message];
                }else{
                    $this->return = ['code' => 0,  'msg' => $message];
                    return;
                }

                $real_money = bcmul($resultData['amount'], 100);
                $fee        = $this->money - $real_money;
                $this->updateTransferOrder(
                    $this->money,
                    $real_money,
                    $resultData['system_order_number'],
                    '',
                    $status,
                    $fee,
                    $message
                );
                return;
            }else if($code === '804'){
                //804没有单号改为失败
                $this->updateTransferOrder($this->money, 0, '', '', 'failed', null, $message);
                return;
            }

        }
        $this->return = ['code' => 886, 'transfer' => '', 'msg' => 'http_code:'.$this->httpCode.' code:'.$code.' message:'.$message];
    }

    //组装数组
    public function initParam($params = []) {
        $data            = $params;
        $data['sign']    = $this->sign($params);  //校验码
        $this->parameter = $data;
    }

    //验证回调签名
    public function sign($data) {
        unset($data['sign']);
        ksort($data);
        reset($data);
        $data['secret_key'] = $this->key;
        $str = '';
        foreach ($data as $k => $v){
            if(is_null($v) || $v === '') continue;
            $str .= $k.'='.$v.'&';
        }
        $str = trim($str, '&');
        $sign = md5($str);
        return $sign;
    }


    //验证回调签名
    public function verifySign($data) {
        $data_sign = $data['sign'];
        unset($data['sign']);
        ksort($data);
        reset($data);
        $data['secret_key'] = $this->pubKey;
        $str = '';
        foreach ($data as $k => $v){
            if(is_null($v) || $v === '') continue;
            $str .= $k.'='.$v.'&';
        }
        $str = trim($str, '&');
        $sign = md5($str);
        if( $sign == $data_sign ){
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

        if(!$this->verifySign($params)) {
            throw new \Exception('Sign error');
        }

        //订单状态已经成功 或者 消息与订单状态一致
        if($this->order['status'] == 'paid') {
            return;
        }
        $amount     = bcmul($params['amount'], 100);//以分为单位
        $real_money = $amount;//实际到账金额

        //订单状态付款状态：(//订单状态：PROCESSING 处理中FAILED 失败SUCCESS 成功)
        if( in_array( $this->parameter['status'], [4, 5] ) ) {
            $status       = 'paid';
            $this->return = ['code' => 1, 'msg' => ''];
        } elseif( in_array( $this->parameter['status'], [1, 2, 3, 11] ) ) {
            $status       = 'failed';
            $this->return = ['code' => 0, 'msg' => '代付处理中'];
        }elseif( in_array( $this->parameter['status'], [6, 7, 8] ) ) {
            $status       = 'failed';
            $this->return = ['code' => 0, 'msg' => '代付失败'];
        } else {
            $this->return = ['code' => 0, 'msg' => 'error'];
            return;
        }

        $this->re = $this->return;
        $this->updateTransferOrder($this->money, $real_money, $params['system_order_number'],//第三方转账编号
            '', $status);
    }

    /**
     * 银行名称
     * @return mixed
     */
    private function getBankName() {

        $banks = [
            "Gcash" => "Gcash",
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
