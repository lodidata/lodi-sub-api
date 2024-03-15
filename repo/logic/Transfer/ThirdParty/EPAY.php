<?php

namespace Logic\Transfer\ThirdParty;

use Logic\Recharge\Recharge;
use Utils\Curl;

/**
 *
 * EPAY 代付
 */
class EPAY extends BASES {
    private $httpCode = '';

    //回调，不处理逻辑
    public function callbackMsg() {
        return 'success';
    }

    //请求代付接口
    public function runTransfer() {

        $params        = [
            "order_no"      => $this->orderID,   //订单号
            "mch_account"   => $this->partnerID,   //商户号
            "amount"        => $this->money,
            'account_type'  => 0,
            "account_no"    => $this->bankCard,
            "account_name"  => $this->bankUserName,
            'bank_code'     => $this->bankCode,
            'bank_province' => '1',
            'bank_city'     => '1',
            'bank_name'     => '1',
            "call_back_url" => $this->payCallbackDomain . '/thirdAdvance/callback/epay',   //回调地址
            'pay_type'      => '1',
            'verify_url'    => '',
        ];

        $this->parameter = $params;
        //判断 额外参数是否为空 Determine whether the extra parameter is empty
        $this->payUrl .= '/proxypay/order';

        $this->initParam($params);
        $this->basePostNew();
        $result  = isset($this->re) ? json_decode($this->re, true) : '';
        $code    = isset($result['ret']) ? $result['ret'] : 1;
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;
        if($this->httpCode == 200) {
            //true成功,false失败
            if($code === 200) {
                $this->return['code']    = 10500;
                $this->return['balance'] = $this->money;
                $this->return['msg']     = $message;
                $this->transferNo        = $result['mch_order_no'];//第三方订单号
                //成功就直接返回了
                return;
            } else {
                $message = 'http_code:' . $this->httpCode . 'errorMsg:' . json_encode($result, JSON_UNESCAPED_UNICODE);
                $this->updateTransferOrder($this->money, 0, '', '', 'failed', null, $message);
                $this->return['code']    = 886;
                $this->return['balance'] = 0;
                $this->return['msg']     = 'EPAY:' . $message ?? '代付失败';
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
            'mch_account'=>$this->partnerID
        ];
        $this->payUrl .= "/proxypay/balance";
        $this->initParam($params);
        $this->basePostNew();
        $result  = json_decode($this->re, true);
        $code    = isset($result['ret']) ? $result['ret'] : 1;
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;

        if($this->httpCode == 200) {
            if($code === 200) {

                $this->return['code']    = 10509;
                $this->return['balance'] = $result['balance'];
                $this->return['msg']     = json_encode($result);
                return;
            }
        }
        $this->_end();
        throw new \Exception('http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message);
    }

    //代付订单查询
    public function getTransferResult() {
        $params       = [
            'mch_account' => $this->partnerID,
            'order_no' => $this->orderID,
        ];
        $this->payUrl .= "/proxypay/query";
        $this->initParam($params);
        $this->basePostNew();

        $result  = json_decode($this->re, true);
        $code    = isset($result['ret']) ? $result['ret'] : 1;
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;

        if($this->httpCode == 200) {
            if($code === 200) {
                //转账状态:订单状态: 0.代付中 1.代付中 2.代付成功 3.代付失败 4.审核失败
                if($result['status'] === 2) {
                    $status       = 'paid';
                    $this->return = ['code' => 1, 'msg' => $message];
                } elseif($result['status'] === 3 || $result['status'] == 4) {
                    $status       = 'failed';
                    $this->return = ['code' => 0, 'msg' => $message];
                } elseif($result['status'] === 1) {
                    $status       = "pending";
                    $this->return = ['code' => 0, 'msg' => $message];
                } else {
                    $this->return = ['code' => 0, 'msg' => $message];
                    return;
                }

                $real_money = $result['amount'];
                $fee        = $this->money - $real_money;
                $this->updateTransferOrder($this->money, $real_money, $result['mch_order_no'], '', $status, $fee, $message);
                return;
            }
        }
        $this->return = ['code' => 886, 'transfer' => '', 'msg' => 'http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message];
    }

    //组装数组
    public function initParam($params = []) {
        $data             = $params;
        $data['sign']     = $this->sign($data);  //校验码

        $this->parameter = $data;
    }

    //签名
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
        $str  .= 'key=' . $this->key;
        $sign = strtoupper(md5($str));
        return $sign;
    }

    //验证回调签名
    public function verifySign($data) {
        $sign = $data['sign'];
        unset($data['sign']);
        ksort($data);
        reset($data);

        $str = '';
        foreach($data as $k => $v) {
            $str .= $k . '=' . $v . '&';
        }
        $str      .= 'key=' . $this->pubKey;
        $sign_str = strtoupper(md5($str));

        if($sign === $sign_str) {
            return true;
        }
        return false;
    }

    public function basePostNew($referer = null) {
        $this->payRequestUrl = $this->payUrl;
        $params_data         = json_encode($this->parameter, JSON_UNESCAPED_UNICODE);
        $ch                  = curl_init();

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
            'Content-Length:' . strlen($params_data),
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
        $amount     = $params['amount'];//以分为单位
        $real_money = $amount;//实际到账金额

        //订单状态付款状态：(// 0.代付中 1.代付中 2.代付成功 3.代付失败 4.审核失败)
        if($this->parameter['status'] == 2) {
            $status       = 'paid';
            $this->return = ['code' => 1, 'msg' => ''];
        } elseif($this->parameter['status'] == 3 || $this->parameter['status'] == 4) {
            $status       = 'failed';
            $this->return = ['code' => 0, 'msg' => '代付失败'];
        } else {
            $this->return = ['code' => 0, 'msg' => 'error'];
            return;
        }

        $this->re = $this->return;
        $this->updateTransferOrder($this->money, $real_money, $params['mch_order_no'],//第三方转账编号
            '', $status);
    }

    /**
     * 银行名称
     * @return mixed
     */
    private function getBankName() {

        $banks = [
            "STP"        => "MXNSTP",
            "HSBC"       => "MXNHSBC",
            "AZTECA"     => "MXNAZTECA",
            "BANAMEX"    => "MXNBANAMEX",
            "BANORTE"    => "MXNBANORTE",
            "BANREGIO"   => "MXNBANREGIO",
            "BANCOPPEL"  => "MXNBANCOPPEL",
            "SANTANDER"  => "MXNSANTANDER",
            "SCOTIABANK" => "MXNSCOTIABANK",
            "BANCOMEXT"  => "MXNBCT",
            "INBURSA"    => "MXNIBA",
        ];
        return $banks[$this->bankCode];
    }

    private function PHLBabnkName() {
        $banks = [
            'Gcash'                     => [
                'bankCode'     => 'GCASH',
                'transferType' => 902410175001
            ],
            'Paymaya Philippines, Inc.' => [
                'bankCode'     => 'PAYMAYA',
                'transferType' => 902410175002
            ]
        ];
        return $banks[$this->bankCode] ?? $banks['Gcash'];
    }
}
