<?php

namespace Logic\Transfer\ThirdParty;

use Logic\Recharge\Recharge;

class ZINPAY extends BASES {
    private $httpCode = '';

    //回调，不处理逻辑
    public function callbackMsg() {
        return 'success';
    }

    //请求代付接口
    public function runTransfer() {
        //组装参数
        $data            = [
            'pay_customer_id'   => $this->partnerID,
            'pay_apply_date'   => time(),
            'pay_amount'       => bcdiv($this->money, 100, 2),
            'pay_order_id'     => $this->orderID,
            'pay_account_name' => $this->bankCard,
            'pay_card_no'      => $this->bankCard,
            'pay_bank_name'    => $this->getBankName(),
            'pay_notify_url'   => $this->payCallbackDomain . '/thirdAdvance/callback/zinpay',
        ];
        $data['pay_md5_sign']    = $this->sign($data);
        $this->payUrl    .= '/api/payments/pay_order';
        $this->parameter = $data;
        $this->basePostNew();

        $result = json_decode($this->re, true);

        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;
        if($this->httpCode == 200) {
            if($result['code'] == '0') {
                $this->return['code']    = 10500;
                $this->return['balance'] = $this->money;
                $this->return['msg']     = $message;
                $this->transferNo        = $result['data']['transaction_id'];//第三方订单号
                //成功就直接返回了
                return;
            }else{
                $message = 'http_code:' . $this->httpCode . 'errorMsg:' . json_encode($result, JSON_UNESCAPED_UNICODE);
                $this->updateTransferOrder($this->money, 0, '', '', 'failed', null, $message);
                $this->return['code']    = 886;
                $this->return['balance'] = 0;
                $this->return['msg']     = 'ZINPAY:' . $message ?? '代付失败';
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
        $params         = [
            'pay_customer_id' => $this->partnerID,
            'pay_apply_date'  => time(),
        ];
        $params['pay_md5_sign'] = $this->sign($params);

        $this->parameter = $params;

        $this->payUrl .= "/api/payments/balance";
        $this->basePostNew();
        $result  = json_decode($this->re, true);
        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;

        if($this->httpCode == 200 && $code == '0') {
            $this->return['code']    = 10509;
            $this->return['balance'] = bcmul($result['data']['balance'], 100);
            $this->return['msg']     = $message;
            return;
        }

        $this->_end();
        throw new \Exception('http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message);
    }

    //查询代付结果
    public function getTransferResult() {
        $data         = [
            'pay_customer_id' => $this->partnerID,
            'pay_apply_date'  => time(),
            'pay_order_id'    => $this->orderID,
        ];
        $data['pay_md5_sign'] = $this->sign($data);

        $this->payUrl    .= '/api/payments/query_transaction';
        $this->parameter = $data;

        $this->basePostNew();

        $result = json_decode($this->re, true);

        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;

        if($this->httpCode == 200 && $code == '0') {
            //订单状态 status (0-未处理 1-处理中，2-已打款,3-已驳回冲正，4-核实不成功，5-余额不⾜ )
            $third_no = $result['data']['payment_id'];
            if($result['data']['status'] == '2') {
                $status       = 'paid';
                $this->return = ['code' => 1, 'msg' => $message];
            } elseif($result['data']['status'] == '3' || $result['data']['status'] == '4' || $result['data']['status'] == '5') {
                $status       = 'failed';
                $this->return = ['code' => 0, 'msg' => $message];
            } else {
                $this->return = ['code' => 0, 'msg' => $message];
                return;
            }

            $real_money = bcmul($result['data']['amount'], 100);
            $fee        = $this->money - $real_money;
            $this->updateTransferOrder($this->money, $real_money, $third_no,//第三方转账编号
                '', $status, $fee, $message);
            return;
        }
        $this->return = ['code' => 886, 'transfer' => '', 'msg' => 'http_code:' . $this->httpCode . ' message:' . $message];
    }

    public function basePostNew() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->payUrl);
        // 执行后不直接打印出来
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // 设置请求方式为post
        curl_setopt($ch, CURLOPT_POST, true);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->parameter);
        // 请求头，可以传数组
        // curl_setopt($ch, CURLOPT_HEADER, $header);
        // curl_setopt($ch, CURLOPT_HEADER, 1);
        // 跳过证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // 不从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        $this->httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $this->re = $response;
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

        if($this->sign($params) != $params['sign']) {
            throw new \Exception('Sign error');
        }

        //订单状态已经成功 或者 消息与订单状态一致
        if($this->order['status'] == 'paid') {
            return;
        }
        $amount     = bcmul($params['amount'], 100);//以分为单位
        $real_money = $amount;//实际到账金额

        //订单状态付款状态：(：5 - 成功 : 3 – 失敗。)
        if($this->parameter['transaction_code'] == '30000') {
            $status       = 'paid';
            $this->return = ['code' => 1, 'msg' => ''];
        } elseif($this->parameter['transaction_code'] == '40000') {
            $status       = 'failed';
            $this->return = ['code' => 0, 'msg' => '代付失败'];
        } else {
            $this->return = ['code' => 0, 'msg' => 'error'];
            return;
        }

        $this->re = $this->return;
        $this->updateTransferOrder($this->money, $real_money, $params['transaction_id'],//第三方转账编号
            '', $status);
    }

    /**
     * 银行名称
     * @return mixed
     */
    private function getBankName() {

        $banks = [
            "ALLBANK (A Thrift Bank), Inc."       =>"ALKBP",
            "BDO Network Bank, Inc."              =>"BDONP",
            "Camalig"                             =>"RUCAP",
            "Cebuana Lhuillier Rural Bank, Inc."  =>"CELRP",
            "Chinabank"                           =>"CHBKP",
            "Gcash"                               =>"GCASH",
            "ING"                                 =>"INGBP",
            "Landbank of the Philippines"         =>"TLBPP",
            "Maybank Philippines, Inc."           =>"MBBEP",
            "PNB"                                 =>"PNBMP",
            "Queen City Development Bank, Inc."   =>"QCDFP",
            "Wealth Development Bank, Inc."       =>"WEDVP",
            "Yuanta Savings Bank, Inc."           =>"TYBKP",
            "Partner Rural Bank (Cotabato), Inc." =>"PRTOP",
            "Starpay"                             =>"STARP",
            "Omnipay"                             =>"OMNIP",
            "Paymaya Philippines, Inc."           =>"PAYMP",
            "GrabPay"                             =>"GRABP",
        ];
        return $banks[$this->bankCode];
    }

}
