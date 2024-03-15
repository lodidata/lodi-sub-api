<?php

namespace Logic\Transfer\ThirdParty;

use Logic\Recharge\Recharge;
use Utils\Curl;

/**
 *
 * MATRIXPAY
 */
class MATRIXPAY extends BASES {
    private $httpCode = '';

    //回调，不处理逻辑
    public function callbackMsg() {
        return 'success';
    }

    //请求代付接口
    public function runTransfer() {
        $params                 = [
            "merchantNum" => $this->partnerID, //商户号
            "orderNo"     => $this->orderID,   //订单号
            "amount"      => bcdiv($this->money, 100, 2),   //代付金额
            "notifyUrl"   => $this->payCallbackDomain . '/thirdAdvance/callback/matrixpay',   //回调地址
        ];
        $bankInfo               = [
            'accountHolder'=>$this->bankUserName,
            'bankCode'     =>'gcash',
            'openAccountBank'=>'gcash',
            'bankCardAccount'=>$this->bankCard
        ];
        $params['payeeAccInfo'] = json_encode($bankInfo);
        $params['sign']         = $this->sign($params);
        $params['channelCode']  = 'phlp_wallet';
        $params['currency']     = 'php';

        $this->parameter = $params;
        //判断 额外参数是否为空 Determine whether the extra parameter is empty
        $this->payUrl .= '/startPayFiatForAnotherOrder';

        $this->basePostNew();
        $result  = isset($this->re) ? json_decode($this->re, true) : '';
        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;
        if($this->httpCode == 200) {
            //true成功,false失败
            if($code == '200') {
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
                $this->return['msg']     = 'MATRIXPAY:' . $message ?? '代付失败';
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
            'merchantNum' => $this->partnerID,
        ];
        $this->payUrl .= "/getMerchantBalance";
        $this->initParam($params);
        $this->baseGetNew();
        $result  = json_decode($this->re, true);
        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;


        if($this->httpCode == 200) {
            if($code == 200) {
                $this->return['code']    = 10509;
                $this->return['balance'] = bcmul($result['data']['balance'], 100);
                $this->return['msg']     = $message;
                return;
            }
        }
        $this->_end();
        throw new \Exception('http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message);
    }

    //代付订单查询
    public function getTransferResult() {
        $params       = [
            'merchantNum'      => $this->partnerID,
            'merchantOrderNo' => $this->orderID
        ];
        $this->payUrl .= "/getPayForAnotherOrderInfo";
        $this->initParam($params);
        $this->baseGetNew();

        $result  = json_decode($this->re, true);
        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;

        if($this->httpCode == 200) {
            if($code == '200') {
                //订单状态：3 成功,其它状态文档没写
                if($result['orderState'] == 3) {
                    $status       = 'paid';
                    $this->return = ['code' => 1, 'msg' => $message];
                }  else {
                    $this->return = ['code' => 0, 'msg' => $message];
                    return;
                }

                $real_money = bcmul($result['amount'], 100);
                $fee        = $this->money - $real_money;
                $this->updateTransferOrder($this->money, $real_money, $result['orderNo'], '', $status, $fee, $message);
                return;
            }
        }
        $this->return = ['code' => 886, 'transfer' => '', 'msg' => 'http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message];
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
        $str = '';
        foreach($data as $k => $v) {
            if(is_null($v) || $v === '')
                continue;
            $str .= $v;
        }
        $str = $str . $this->key;
        return md5($str);
    }

    public function basePostNew($referer = null) {
        $this->payRequestUrl = $this->payUrl;
        $ch                  = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->payUrl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->parameter);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if($referer) {
            curl_setopt($ch, CURLOPT_REFERER, $referer);
        }
        $response        = curl_exec($ch);
        $this->curlError = curl_error($ch);
        $this->httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->re        = $response;
        curl_close($ch);
    }

    public function baseGetNew() {
        //        echo '<pre>';print_r($this->parameter);exit;
        //初始化
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->payUrl . '?' . http_build_query($this->parameter));
        // 执行后不直接打印出来
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        // 跳过证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // 不从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        //执行并获取HTML文档内容
        $output = curl_exec($ch);
        $this->httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        //释放curl句柄
        curl_close($ch);
        $this->re = $output;
    }

    /**
     * 代付异步回调
     * @param array $params 异步参数
     * @return mixed
     */
    public function callbackResult($params) {
        $this->parameter = $params;

        $sign=md5($params['state'].$params['merchantNum'].$params['orderNo'].$params['amount'].$this->key);
        if($params['sign'] != $sign) {
            throw new \Exception('Sign error');
        }

        //订单状态已经成功 或者 消息与订单状态一致
        if($this->order['status'] == 'paid') {
            return;
        }
        $amount     = bcmul($params['amount'], 100);//以分为单位
        $real_money = $amount;//实际到账金额

        //订单状态付款状态：(//订单状态：1成功,其它文档没写)
        if($this->parameter['state'] ==  1) {
            $status       = 'paid';
            $this->return = ['code' => 1, 'msg' => ''];
        } else {
            $this->return = ['code' => 0, 'msg' => 'error'];
            return;
        }

        $this->re = $this->return;
        $this->updateTransferOrder($this->money, $real_money, $params['platformOrderNo'],//第三方转账编号
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
