<?php

namespace Logic\Transfer\ThirdParty;

use Logic\Recharge\Recharge;

class JHPAY extends BASES {
    private $httpCode = '';

    //回调，不处理逻辑
    public function callbackMsg() {
        return 'success';
    }

    //请求代付接口
    public function runTransfer() {
        //组装参数
        $data            = [
            'mchid'        => $this->partnerID,
            'out_trade_no' => $this->orderID,
            'money'        => bcdiv($this->money, 100, 2),
            'bankname'     => $this->bankName,
            'subbranch'    => $this->bankName,
            'accountname'  => $this->bankUserName,
            'cardnumber'   => $this->bankCard,
            'province'     => "MANILA",
            'city'         => "MANILA",
//            'callback_url' => $this->payCallbackDomain . '/thirdAdvance/callback/didipay',
        ];
        $data['pay_md5sign']    = $this->sign($data);
        $this->payUrl    .= '/Payment_Dfpay_add.html';
        $this->parameter = $data;
        $this->basePostNew();

        $result = json_decode($this->re, true);

        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;
        if($this->httpCode == 200) {
            if($result['status'] == 'success') {
                $this->return['code']    = 10500;
                $this->return['balance'] = $this->money;
                $this->return['msg']     = $message;
                $this->transferNo        = $result['transaction_id'];//第三方订单号
                //成功就直接返回了
                return;
            }else{
                $message = 'http_code:' . $this->httpCode . 'errorMsg:' . json_encode($result, JSON_UNESCAPED_UNICODE);
                $this->updateTransferOrder($this->money, 0, '', '', 'failed', null, $message);
                $this->return['code']    = 886;
                $this->return['balance'] = 0;
                $this->return['msg']     = 'JHPAY:' . $message ?? '代付失败';
                return;
            }
        }

        //$message = json_encode($result);
        $this->return['code']    = 886;
        $this->return['balance'] = $this->money;
        $this->return['msg']     = $message;
        $this->transferNo        = '';//第三方订单号
    }

    //查询余额
    public function getThirdBalance() {

        $this->return['code']    = 10509;
        $this->return['balance'] = 1000000;
        $this->return['msg']     = '';
        return;

    }

    //查询代付结果
    public function getTransferResult() {
        $data         = [
            'mchid' => $this->partnerID,
            'out_trade_no' => $this->orderID,
        ];
        $data['pay_md5sign'] = $this->sign($data);

        $this->payUrl    .= '/Payment_Dfpay_query.html';
        $this->parameter = $data;

        $this->basePostNew();

        $result = json_decode($this->re, true);

        $code       = isset($result['status']) ? $result['status'] : 'error';
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;

        if($this->httpCode == 200) {
            if($code == 'success'){
                //订单状态 refCode (1成功，2失败，3处理中)
                if($result['refCode'] == '1') {
                    $status       = 'paid';
                    $this->return = ['code' => 1, 'msg' => $message];
                } elseif($result['status'] == '2') {
                    $status       = 'failed';
                    $this->return = ['code' => 0, 'msg' => $message];
                } else {
                    $this->return = ['code' => 0, 'msg' => $message];
                    return;
                }

                $real_money = bcmul($result['amount'], 100);
                $fee        = $this->money - $real_money;
                $this->updateTransferOrder($this->money, $real_money, $result['transaction_id'],//第三方转账编号
                    '', $status, $fee, $message);
                return;
            }
        }
        $this->return = ['code' => 886, 'transfer' => '', 'msg' => 'http_code:' . $this->httpCode .' code:'.$code.' message:' . $message];
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

        return strtoupper(md5($sign_str));
    }

    /**
     * 代付异步回调
     * @param array $params 异步参数
     * @return mixed
     */
    public function callbackResult($params) {
        $this->parameter = $params;

        if($this->sign($params) != $params['pay_md5sign']) {
            throw new \Exception('Sign error');
        }

        //订单状态已经成功 或者 消息与订单状态一致
        if($this->order['status'] == 'paid') {
            return;
        }
        $amount     = bcmul($params['amount'], 100);//以分为单位
        $real_money = $amount;//实际到账金额

        //订单状态付款状态：(1成功，2失败，3处理中)
        if($this->parameter['refCode'] == '1') {
            $status       = 'paid';
            $this->return = ['code' => 1, 'msg' => ''];
        } elseif($this->parameter['refCode'] == '2') {
            $status       = 'failed';
            $this->return = ['code' => 0, 'msg' => '代付失败'];
        } else {
            $this->return = ['code' => 0, 'msg' => 'error'];
            return;
        }

        $this->re = $this->return;
        $this->updateTransferOrder($this->money, $real_money, $this->parameter['transaction_id'],//第三方转账编号
            '', $status);
    }

}
