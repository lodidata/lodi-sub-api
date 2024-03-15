<?php

namespace Logic\Transfer\ThirdParty;

use Logic\Recharge\Recharge;

class RPAY extends BASES {
    private $httpCode = '';

    //回调，不处理逻辑
    public function callbackMsg() {
        return 'success';
    }

    //请求代付接口
    public function runTransfer() {
        //组装参数
        $data                 = [
            'merchant'          => $this->partnerID,
            'total_amount'      => floatval(bcdiv($this->money, 100, 2)),
            'order_id'          => $this->orderID,
            'callback_url'      => $this->payCallbackDomain . '/thirdAdvance/callback/rpay',
            'bank'              => $this->getBankName(),            //银行名称
            'bank_card_name'    => $this->bankUserName,             //收款人姓名
            'bank_card_account' => $this->bankCard,                 //收款人账号
            'bank_card_remark'  => 'no'
        ];
        $data['sign'] = $this->sign($data);

        $this->payUrl         .= '/api/daifu';
        $this->parameter      = $data;
        $this->basePostNew();

        $result = json_decode($this->re, true);

        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;
        if($this->httpCode == 200) {
            if($result['status'] == 1) {
                $this->return['code']    = 10500;
                $this->return['balance'] = $this->money;
                $this->return['msg']     = $message;
                $this->transferNo        = $this->orderID;//第三方订单号
                //成功就直接返回了
                return;
            } else {
                $message = 'http_code:' . $this->httpCode . 'errorMsg:' . json_encode($result, JSON_UNESCAPED_UNICODE);
                $this->updateTransferOrder($this->money, 0, '', '', 'failed', null, $message);
                $this->return['code']    = 886;
                $this->return['balance'] = 0;
                $this->return['msg']     = 'rpay:' . $message ?? '代付失败';
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
        $params                 = [
            'merchant' => $this->partnerID,
        ];
        $params['sign'] = $this->sign($params);

        $this->parameter = $params;

        $this->payUrl .= "/api/me";
        $this->basePostNew();
        $result  = json_decode($this->re, true);

        $code    = isset($result['status']) ? $result['status'] : 1;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;

        if($this->httpCode == 200) {
            if($code == 1 && $this->sign($result) == $result['sign']) {
                $this->return['code']    = 10509;
                $this->return['balance'] = bcmul($result['balance'], 100);
                $this->return['msg']     = $message;
                return;
            }
        }

        $this->_end();
        throw new \Exception('http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message);
    }

    //查询代付结果
    public function getTransferResult() {
        $data                 = [
            'merchant'  => $this->partnerID,
            'order_id'  => $this->orderID,
        ];
        $data['sign'] = $this->sign($data);

        $this->payUrl    .= '/api/query';
        $this->parameter = $data;

        $this->basePostNew();

        $result = json_decode($this->re, true);

        $code    = isset($result['status']) ? $result['status'] : 5;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;

        if($this->httpCode == 200) {
            //订单状态 status (0-错误 1-等待中，2,6-进行中，3-失败，5-成功)
            $third_no = $this->orderID;
            if($code == 5) {
                $status       = 'paid';
                $this->return = ['code' => 1, 'msg' => $message];
            } elseif($code == 3) {
                $status       = 'failed';
                $this->return = ['code' => 0, 'msg' => $message];
            } else {
                $this->return = ['code' => 0, 'msg' => $message];
                return;
            }

            $real_money = bcmul($result['amount'], 100);
            $fee        = $this->money - $real_money;
            $this->updateTransferOrder($this->money, $real_money, $third_no,//第三方转账编号
                '', $status, $fee, $message);
            return;
        }
        $this->return = ['code' => 886, 'transfer' => '', 'msg' => 'http_code:' . $this->httpCode . ' message:' . $message];
    }

    public function basePostNew($referer = null) {
        $this->payRequestUrl = $this->payUrl;
        $params_data = json_encode($this->parameter, JSON_UNESCAPED_UNICODE);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->payUrl);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params_data);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        $response = curl_exec($ch);
        $this->httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->re = $response;
    }

    //生成签名
    public function sign($data)
    {
        unset($data['sign']);
        ksort($data);

        $str = '';
        foreach ($data as $k => $v) {
            if(is_null($v) || $v === '') continue;     //值为 null 则不加入签名
            $str .= $k . '=' . $v . '&';
        }

        $sign_str = $str . 'key=' . $this->key;

        return md5($sign_str);
    }

    /**
     * 代付异步回调
     * @param array $params 异步参数
     * @return mixed
     */
    public function callbackResult($params) {
        $this->parameter = $params;
        $message = $params['message'];

        if($this->sign($params) != $params['sign']) {
            throw new \Exception('Sign error');
        }

        //订单状态已经成功 或者 消息与订单状态一致
        if($this->order['status'] == 'paid') {
            return;
        }
        $amount     = bcmul($params['amount'], 100);//以分为单位
        $real_money = $amount;//实际到账金额

        //订单状态付款状态：(5 - 成功 : 3 – 失败)
        if($params['status'] == 5) {
            $status       = 'paid';
            $this->return = ['code' => 1, 'msg' => ''];
        } elseif($params['status'] == 3) {
            $status       = 'failed';
            $this->return = ['code' => 0, 'msg' => '代付失败'];
        } else {
            $this->return = ['code' => 0, 'msg' => 'error'];
            return;
        }

        $this->re = $this->return;
        $this->updateTransferOrder($this->money, $real_money, $params['order_id'], //第三方转账编号
            '', $status, 0, $message);
    }

    /**
     * 银行名称
     * @return mixed
     */
    private function getBankName() {

        $banks = [
            "Gcash" => "gcash",
            "Metropolitan Bank and Trust Co" => "mbt",
            "SBC" => "SBC",
            "PNB" => "PNB",
            "PSB" => "PSB",
            "PBC" => "PBC",
            "BOC" => "BC",
            "Camalig" => "CB",
            "ESB" => "ESB",
            "GrabPay" => "GP",
            "ING" => "IB",
            "OmniPay" => "OP",
            "Paymaya Philippines, Inc." => "PMP",
            "PTC" => "PTC",
            "PB" => "PDB",
            "Starpay" => "STP",
            "SBA" => "SLB",
            "SSB" => "SSB",
            "UCPB Savings Bank" => "USB",
            "Wealth Development Bank, Inc." => "WDB"
        ];
        return $banks[$this->bankCode];
    }

}