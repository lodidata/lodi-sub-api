<?php

namespace Logic\Transfer\ThirdParty;

use Logic\Recharge\Recharge;

class TRPAY extends BASES {
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
            'bankname'     => $this->getBankName(),
            'subbranch'    => $this->getBankName(),
            'accountname'  => $this->bankUserName,
            'cardnumber'   => $this->bankCard,
            'pay_userid'   => 11111,
            'province'     => "MANILA",
            'city'         => "MANILA",
        ];

        $data['pay_md5sign']    = $this->sign($data);
        $data['pay_notifyurl'] = $this->payCallbackDomain . '/thirdAdvance/callback/trpay';
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
                $this->return['msg']     = 'TRPAY:' . $message ?? '代付失败';
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
        $params         = [
            'pay_memberid' => $this->partnerID,
        ];
        $params['pay_md5sign'] = $this->sign($params);

        $this->parameter = $params;

        $this->payUrl .= "/Pay_PayQuery_balance.html";
        $this->basePostNew();
        $result  = json_decode($this->re, true);
        $code    = isset($result['status']) ? $result['status'] : 1;
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;

        if($this->httpCode == 200 && $code == 'success') {
            $this->return['code']    = 10509;
            $this->return['balance'] = (int)bcmul($result['data'], 100);
            $this->return['msg']     = $message;
            return;
        }

        $this->_end();
        throw new \Exception('http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message);

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
    public function sign($param) {
        unset($param['sign']);

        $newParam = array_filter($param);
        ksort($newParam);
        if (!empty($newParam)) {
            $sortParam = [];
            foreach ($newParam as $k => $v) {
                if(empty($v)){
                    continue;
                }
                $sortParam[] = $k . '=' . $v;
            }
            $originalString = implode('&', $sortParam) . '&key='.$this->key;
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
        $this->parameter = $params;

        if($this->returnSign($params) != $params['sign']) {
            throw new \Exception('Sign error');
        }

        //订单状态已经成功 或者 消息与订单状态一致
        if($this->order['status'] == 'paid') {
            return;
        }

        $amount     = bcmul($params['amount'], 100);//以分为单位
        $real_money = $amount;//实际到账金额

        //订单状态付款状态：(2成功，3失败)
        if($this->parameter['refCode'] == '2') {
            $status       = 'paid';
            $this->return = ['code' => 1, 'msg' => ''];
        } elseif($this->parameter['refCode'] == '3') {
            $status       = 'failed';
            $this->return = ['code' => 0, 'msg' => '代付失败'];
        } else {
            $this->return = ['code' => 0, 'msg' => 'error'];
            return;
        }

        $this->re = $this->return;
        $this->updateTransferOrder($this->money, $real_money, $this->parameter['orderid'],//第三方转账编号
            '', $status);
    }

    //生成回调签名
    private function returnSign($params) {
        $returnArray = array( // 返回字段
            "memberid" => $params["memberid"], // 商户ID
            "orderid" =>  $params["orderid"], // 订单号
            "amount" =>  $params["amount"], // 交易金额
            "refCode" => $params["refCode"]
        );
        ksort($returnArray);
        reset($returnArray);
        $md5str = "";
        foreach ($returnArray as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        return strtoupper(md5($md5str . "key=" . $this->key));
    }

    /**
     * 银行名称
     * @return mixed
     */
    private function getBankName() {
        
        $banks = [
            "Gcash"   => "GCASH",
            "BPI"     => "BPI",
            "GrabPay" => "GrabPay",
            "OmniPay" => "OmniPay",
            "Starpay" => "Starpay",
        ];
        return $banks[$this->bankCode];
    }

}
