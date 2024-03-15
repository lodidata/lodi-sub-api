<?php

namespace Logic\Transfer\ThirdParty;

use Logic\Recharge\Recharge;
use Utils\Curl;

/**
 *
 * POPPAY代付
 */
class NGPAY extends BASES {
    private $httpCode = '';

    //回调，不处理逻辑
    public function callbackMsg() {
        return 'success';
    }

    //请求代付接口
    public function runTransfer() {
        $params          = [
            "transaction_id" => $this->orderID,
            "type"           => "gcash",
            "account_number" => $this->bankCard,
            "account_name"   => $this->bankName,
            "amount"         => bcdiv($this->money, 100),
            "currency"       => "PHP"
        ];
        $this->parameter = $params;
        //判断 额外参数是否为空 Determine whether the extra parameter is empty
        $this->payUrl .= '/api/v1/withdrawal';

        $this->basePostNew();
        $result  = json_decode($this->re, true);
        $code    = isset($result['success']) ? $result['success'] : 1;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;
        if($this->httpCode == 200) {
            //true成功,false失败
            if($code && $result['data']['status'] == 'applying') {
                $this->return['code']    = 10500;
                $this->return['balance'] = $this->money;
                $this->return['msg']     = $message;
                $this->transferNo        = $result['data']['system_transaction_id'];//第三方订单号
                //成功就直接返回了
                return;
            }else{
                $message = 'http_code:' . $this->httpCode . 'errorMsg:' . json_encode($result, JSON_UNESCAPED_UNICODE);
                $this->updateTransferOrder($this->money, 0, '', '', 'failed', null, $message);
                $this->return['code']    = 886;
                $this->return['balance'] = 0;
                $this->return['msg']     = 'NGPPAY:' . $message ?? '代付失败';
                return;
            }
        }

        $this->return['code']    = 886;
        $this->return['balance'] = $this->money;
        $this->return['msg']     = $message;
        $this->transferNo        = '';//第三方订单号
    }

    public function getThirdBalance() {

        $this->return['code']    = 10509;
        $this->return['balance'] = "1000000000";
        $this->return['msg']     = "";
    }

    public function getTransferResult() {

    }

    //验证回调签名
    public function sign($data) {
        $secret  = $data['secret'];
        $mid     = $this->partnerID;
        $orderId = $data['transaction_id'];

        $combine = $mid . $orderId . $secret;
        $sign    = hash_hmac('sha512', $combine, $secret);
        return $sign;
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
            "Authorization: Bearer " . $this->key
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
        $field = [
            'secret'         => $this->app_secret,
            'transaction_id' => $params['transaction_id']
        ];

        if($this->sign($field) != $params['signature']) {
            throw new \Exception('Sign error');
        }

        //订单状态已经成功 或者 消息与订单状态一致
        if($this->order['status'] == 'paid') {
            return;
        }

        $amount     = bcmul($params['amount'], 100);//以分为单位
        $real_money = $amount;//实际到账金额

        //订单状态付款状态：(completed 完成, declined 拒绝 ,cancelled 取消)
        if($this->parameter['status'] == 'completed') {
            $status       = 'paid';
            $this->return = ['code' => 1, 'msg' => ''];
        }elseif($this->parameter['status'] =='declined' || $this->parameter['status'] == 'cancelled'){
            $status = 'failed';
            $this->return = ['code' => 0, 'msg' => '代付失败'];
        } else {
            $this->return = ['code' => 0, 'msg' => 'error'];
            return;
        }


        $this->re = $this->return;
        $this->updateTransferOrder($this->money, $real_money, $params['transaction_id'],//第三方转账编号
            '', $status);
    }
}
