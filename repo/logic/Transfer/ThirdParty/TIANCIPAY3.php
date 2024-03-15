<?php

namespace Logic\Transfer\ThirdParty;

use Logic\Recharge\Recharge;
use Utils\Curl;

/**
 *
 * TIANCIPAY3代付
 */
class TIANCIPAY3 extends BASES {
    private $httpCode = '';

    //回调，不处理逻辑
    public function callbackMsg() {
        return 'success';
    }

    //请求代付接口
    public function runTransfer() {
        $params = [
            "out_trade_no"   => $this->orderID,
            "bank_id"        => $this->getBankName(),
            "account_number" => $this->bankCard,
            "bank_owner"     => $this->bankUserName,
            "amount"         => bcdiv($this->money, 100, 2),
            "callback_url"   => $this->payCallbackDomain . '/thirdAdvance/callback/tiancipay3'
        ];
        $params['sign'] =$this->sign($params);
        //判断 额外参数是否为空 Determine whether the extra parameter is empty
        $this->payUrl    .= '/api/payment';
        $this->parameter = $params;

        $this->basePostNew();
        $result  = json_decode($this->re, true);
        $code    = isset($result['success']) ? $result['success'] : false;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;
        if($this->httpCode == 200) {
            //true成功,false失败
            if($code) {
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
                $this->return['msg']     = 'TIANCIPAY3:' . $message ?? '代付失败';
                return;
            }
        }

        $this->return['code']    = 886;
        $this->return['balance'] = $this->money;
        $this->return['msg']     = $message;
        $this->transferNo        = '';//第三方订单号
    }

    public function getThirdBalance() {
        $this->payUrl .= "/api/balance/inquiry";
        $this->baseGetNew();
        $result  = json_decode($this->re, true);

        $code    = isset($result['success']) ? $result['success'] : false;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;


        if($this->httpCode == 200) {
            if($code) {
                $this->return['code']    = 10509;
                $this->return['balance'] = bcmul($result['data']['balance'], 100, 0);
                $this->return['msg']     = $message;
                return;
            }
        }
        $this->_end();
        throw new \Exception('http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message);
    }

    public function getTransferResult() {
        $this->payUrl    .= '/api/payment/'.$this->orderID;

        $this->baseGetNew();

        $result = json_decode($this->re, true);

        $code       = isset($result['success']) ? $result['success'] : false;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;

        if($this->httpCode == 200) {
            if($code){
                //订单状态 new => 新订单
                //processing => 处理中
                //reject => 拒绝
                //completed => 成功
                //failed => 失败
                //refund => 冲回
                $result=$result['data'];
                if($result['state'] == 'completed') {
                    $status       = 'paid';
                    $this->return = ['code' => 1, 'msg' => $message];
                } elseif(in_array($result['state'],['reject','failed','refund'])) {
                    $status       = 'failed';
                    $this->return = ['code' => 0, 'msg' => $message];
                } else {
                    $this->return = ['code' => 0, 'msg' => $message];
                    return;
                }

                $real_money = bcmul($result['amount'], 100);
                $fee        = $this->money - $real_money;
                $this->updateTransferOrder($this->money, $real_money, $result['trade_no'],//第三方转账编号
                    '', $status, $fee,$message);
                return;
            }

        }
        $this->return = ['code' => 886, 'transfer' => '', 'msg' => 'http_code:' . $this->httpCode . ' message:' . $message];
    }

    //验证回调签名
    public function sign($data) {
        unset($data['sign']);
        ksort($data);

        $str = '';
        foreach($data as $k => $v) {
            if(is_null($v) || $v === '')
                continue;
            $str .= $k . '=' . $v . '&';
        }
        $str = trim($str, '&');

        $sign_str = $str .$this->key .$this->pubKey;
        return md5($sign_str);
    }

    public function basePostNew($referer = null) {
        $this->payRequestUrl = $this->payUrl;
        $params_data         = json_encode($this->parameter, JSON_UNESCAPED_UNICODE);

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


    public function baseGetNew()
    {
        $this->payRequestUrl = $this->payUrl;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->payUrl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $this->key
        ]);

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

        //记录错误返回
        $message = isset($params['errors']) ? $params['errors'] : '';

        //订单状态 new => 新订单
        //processing => 处理中
        //reject => 拒绝
        //completed => 成功
        //failed => 失败
        //refund => 冲回

        if($this->parameter['state'] == 'completed') {
            $status       = 'paid';
            $this->return = ['code' => 1, 'msg' => ''];
        } elseif(in_array($this->parameter['state'],['reject','failed','refund'])) {
            $status       = 'failed';
            $this->return = ['code' => 0, 'msg' => '代付失败'];
        } else {
            $this->return = ['code' => 0, 'msg' => 'error'];
            return;
        }

        $this->re = $this->return;
        $this->updateTransferOrder($this->money, $real_money, $params['out_trade_no'],//第三方转账编号
            '', $status,0,$message);
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
            $banks = [
                "BAAC"  => "BAAC",
                "BAY"   => "BAY",
                "BBL"   => "BBL",
                "BNPP"  => "BNPP",
                "BOC"   => "BOC",
                "CITI"  => "CITI",
                "GHB"   => "GHB",
                "GSB"   => "GSB",
                "HSBC"  => "HSBC",
                "ICBC"  => "ICBC",
                "KBANK" => "KBANK",
                "KTB"   => "KTB",
                "SCB"   => "SCB",
                "SCBT"  => "SCBT",
                "SMBC"  => "SMBC",
                "TCRB"  => "TCRB",
                "TISCO" => "TISCO",
                "KKB"   => "KKP",
            ];
        }else{
            $banks = [
                'Gcash' => 'GCASH',
                'Paymaya Philippines, Inc.' => 'PAYMAYA'
            ];
        }
        return $banks[$this->bankCode];
    }
}
