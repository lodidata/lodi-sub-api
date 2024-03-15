<?php

namespace Logic\Transfer\ThirdParty;

use Logic\Recharge\Recharge;

class RGPAY extends BASES {
    private $httpCode = '';

    //回调，不处理逻辑
    public function callbackMsg() {
        return 'success';
    }

    //请求代付接口
    public function runTransfer() {
        //组装参数
        $data                 = [
            'account'     => $this->bankCard,
            'accountMark' => 'GCash',
            'accountType' => '2',
            'amount'      => floatval(bcdiv($this->money, 100, 2)),
            'noticeUrl'   => $this->payCallbackDomain . '/thirdAdvance/callback/rgpay',
            'orderId'     => $this->orderID,
            'user'        => $this->partnerID
        ];
        $data['sign'] = $this->sign($data);
        $data['name'] = $this->bankUserName;
        $this->payUrl         .= '/api/daifu';
        $this->parameter      = $data;
        $this->basePostNew();

        $result = json_decode($this->re, true);

        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;
        if($this->httpCode == 200) {
            if($result['code'] == 0) {
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
                $this->return['msg']     = 'rgpay:' . $message ?? '代付失败';
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
            'user' => $this->partnerID,
        ];
        $params['sign'] = $this->sign($params);

        $this->parameter = $params;

        $this->payUrl .= "/api/balance";
        $this->basePostNew();
        $result  = json_decode($this->re, true);

        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;

        if($this->httpCode == 200 && $code == 0) {
            $this->return['code']    = 10509;
            $this->return['balance'] = bcmul($result['amount'], 100);
            $this->return['msg']     = $message;
            return;
        }

        $this->_end();
        throw new \Exception('http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message);
    }

    //查询代付结果
    public function getTransferResult() {
        $data                 = [
            'user' => $this->partnerID,
            'orderId'    => $this->orderID,
        ];
        $data['sign'] = $this->sign($data);

        $this->payUrl    .= '/api/daifu/query';
        $this->parameter = $data;

        $this->basePostNew();

        $result = json_decode($this->re, true);

        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;

        if($this->httpCode == 200 && $code == 0) {
            //订单状态 status (1代表等待处理,2代表处理成功,3代表处理失败)
            $third_no = $this->orderID;
            if($result['status'] == '2') {
                $status       = 'paid';
                $this->return = ['code' => 1, 'msg' => $message];
            } elseif($result['status'] == '3') {
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

    public function basePostNew() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->payUrl);
        // 执行后不直接打印出来
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // 设置请求方式为post
        curl_setopt($ch, CURLOPT_POST, true);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->parameter));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
        ]);
        // 请求头，可以传数组
        // curl_setopt($ch, CURLOPT_HEADER, $header);
        // curl_setopt($ch, CURLOPT_HEADER, 1);
        // 跳过证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // 不从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response       = curl_exec($ch);
        $this->httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $this->re = $response;
    }

    //生成签名
    public function sign($data) {
        unset($data['sign']);
        unset($data['s']);
        ksort($data);
        reset($data);

        $str = '';
        foreach($data as $k => $v) {
//            if(is_null($v) || $v === '')
//                continue;
            $str .= $k . '=' . $v . '&';
        }
        $str = trim($str, '&');

        $sign_str = $str . '&token=' . $this->key;
        return strtoupper(md5($sign_str));
    }

    /**
     * 代付异步回调
     * @param array $params 异步参数
     * @return mixed
     */
    public function callbackResult($params) {
        $this->parameter = $params;
        $message = $params['statusMsg'];
        unset($params['statusMsg']);
        if($this->sign($params) != $params['sign']) {
            throw new \Exception('Sign error');
        }

        //订单状态已经成功 或者 消息与订单状态一致
        if($this->order['status'] == 'paid') {
            return;
        }
        $amount     = bcmul($params['amount'], 100);//以分为单位
        $real_money = $amount;//实际到账金额

        //订单状态付款状态：(1代表等待处理,2代表处理成功,3代表处理失败。)
        if($this->parameter['status'] == '2') {
            $status       = 'paid';
            $this->return = ['code' => 1, 'msg' => ''];
        } elseif($this->parameter['status'] == '3') {
            $status       = 'failed';
            $this->return = ['code' => 0, 'msg' => '代付失败'];
        } else {
            $this->return = ['code' => 0, 'msg' => 'error'];
            return;
        }

        $this->re = $this->return;
        $this->updateTransferOrder($this->money, $real_money, $params['orderId'],//第三方转账编号
            '', $status, 0, $message);
    }

    /**
     * 银行名称
     * @return mixed
     */
    private function getBankName() {

        $banks = [
            "ALLBANK (A Thrift Bank), Inc."       => "ALKBP",
            "BDO Network Bank, Inc."              => "BDONP",
            "Camalig"                             => "RUCAP",
            "Cebuana Lhuillier Rural Bank, Inc."  => "CELRP",
            "Chinabank"                           => "CHBKP",
            "Gcash"                               => "GCASH",
            "ING"                                 => "INGBP",
            "Landbank of the Philippines"         => "TLBPP",
            "Maybank Philippines, Inc."           => "MBBEP",
            "PNB"                                 => "PNBMP",
            "Queen City Development Bank, Inc."   => "QCDFP",
            "Wealth Development Bank, Inc."       => "WEDVP",
            "Yuanta Savings Bank, Inc."           => "TYBKP",
            "Partner Rural Bank (Cotabato), Inc." => "PRTOP",
            "Starpay"                             => "STARP",
            "Omnipay"                             => "OMNIP",
            "Paymaya Philippines, Inc."           => "PAYMP",
            "GrabPay"                             => "GRABP",
        ];
        return $banks[$this->bankCode];
    }

}
