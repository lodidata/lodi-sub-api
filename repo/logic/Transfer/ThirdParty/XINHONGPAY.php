<?php

namespace Logic\Transfer\ThirdParty;

/**
 *
 * XINHONGPAY代付
 */
class XINHONGPAY extends BASES
{
    private $httpCode = '';

    //回调，不处理逻辑
    public function callbackMsg()
    {
        return 'success';
    }

    //请求代付接口
    public function runTransfer()
    {
        $params = [
            "merchantNo"  => $this->partnerID, //商户号
            "outTradeNo"  => $this->orderID,   //订单号
            "amount"      => bcdiv($this->money, 100, 2),   //支付金额
            "type"        => $this->getBankName(),
            'name'        => trim($this->bankUserName),
            "bankCode"    => $this->getBankName(),
            'bankAccount' => trim($this->bankCard),
            'notifyUrl'   => $this->payCallbackDomain . '/thirdAdvance/callback/xinhongpay',
        ];
        $this->parameter = $params;
        //判断 额外参数是否为空 Determine whether the extra parameter is empty
        $this->payUrl .= '/payout/create';

        $this->initParam($params);
        $this->basePostNew();
        $result = isset($this->re) ? json_decode($this->re, true) : '';
        $code = isset($result['status']) ? $result['status'] : '';
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;
        if ($this->httpCode == 200) {
            //0成功,1失败
            if ($code === 0) {
                $this->return['code'] = 10500;
                $this->return['balance'] = $this->money;
                $this->return['msg'] = $message;
                $this->transferNo = $result['data']['tradeId'];   //第三方订单号
                //成功就直接返回了
                return;
            } else {
                $message = 'http_code:' . $this->httpCode . 'errorMsg:' . json_encode($result, JSON_UNESCAPED_UNICODE);
                $this->updateTransferOrder($this->money, 0, '', '', 'failed', null, $message);
                $this->return['code'] = 886;
                $this->return['balance'] = 0;
                $this->return['msg'] = 'XINHONGPAY:' . $message ?? '代付失败';
                return;
            }
        }

        $this->return['code'] = 886;
        $this->return['balance'] = $this->money;
        $this->return['msg'] = $message;
        $this->transferNo = '';   //第三方订单号
    }

    //查询余额
    public function getThirdBalance()
    {
        $params = [
            'merchantNo' => $this->partnerID,
        ];
        $this->payUrl .= "/payout/balance";
        $this->initParam($params);
        $this->basePostNew();
        $result = json_decode($this->re, true);
        $code = isset($result['status']) ? $result['status'] : 1;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;

        if ($this->httpCode == 200) {
            if ($code === 0) {
                $this->return['code'] = 10509;
                $this->return['balance'] = bcmul($result['data']['balance'], 100);
                $this->return['msg'] = 'success';
                return;
            }
        }
        $this->_end();
        throw new \Exception('http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message);
    }

    //代付订单查询
    public function getTransferResult()
    {
        $params = [
            'merchantNo' => $this->partnerID,
            'outTradeNo' => $this->orderID
        ];
        $this->payUrl .= "/payout/query";
        $this->initParam($params);
        $this->basePostNew();

        $result = json_decode($this->re, true);
        $code = isset($result['status']) ? $result['status'] : 1;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;

        if ($this->httpCode == 200 && $code === 0) {
            //订单状态：0 处理中 1 成功 2 失败
            if ($result['data']['status'] == 1) {
                $status = 'paid';
                $this->return = ['code' => 1, 'msg' => $message];
            } elseif ($result['data']['status'] == 2) {
                $status = 'failed';
                $this->return = ['code' => 0, 'msg' => $message];
            } else {
                $status = "pending";
                $this->return = ['code' => 0, 'msg' => $message];
            }

            $real_money = bcmul($result['data']['amount'], 100);
            $fee = $this->money - $real_money;
            $this->updateTransferOrder($this->money, $real_money, $result['data']['sn'], $result['data']['createTime'], $status, $fee, $message);
            return;
        }
        $this->return = ['code' => 886, 'transfer' => '', 'msg' => 'http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message];
    }

    //组装数组
    public function initParam($params = [])
    {
        $data = $params;
        $data['sign'] = $this->sign($params);  //校验码
        $this->parameter = $data;
    }

    //验证回调签名
    public function sign($param)
    {
        unset($param['sign']);
        ksort($param);

        $originalString = '';

        foreach ($param as $key => $val) {

            if (!empty($val)) {
                $originalString = $originalString . $key . "=" . $val . "&";
            }
        }
        $originalString .= "signKey=" . $this->key;

        return strtoupper(md5($originalString));
    }

    public function basePostNew($referer = null)
    {
        $ch = curl_init();
        $this->payRequestUrl = $this->payUrl;
        curl_setopt($ch, CURLOPT_URL, $this->payUrl);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->arrayToURL($this->parameter));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
        ]);
        if ($referer) {
            curl_setopt($ch, CURLOPT_REFERER, $referer);
        }
        $response = curl_exec($ch);
        $this->curlError = curl_error($ch);
        $this->httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->re = $response;
        curl_close($ch);
    }

    /**
     * 代付异步回调
     * @param array $params 异步参数
     * @return mixed
     */
    public function callbackResult($params)
    {
        $this->parameter = $params;

        if ($this->sign($params) != $params['sign']) {
            throw new \Exception('Sign error');
        }

        //订单状态已经成功 或者 消息与订单状态一致
        if ($this->order['status'] == 'paid') {
            return;
        }
        $amount = bcmul($params['amount'], 100);//以分为单位
        $real_money = $amount;//实际到账金额

        //订单状态付款状态：(//订单状态：0 处理中 1 成功 2 失败)
        if ($this->parameter['status'] == 1) {
            $status = 'paid';
            $this->return = ['code' => 1, 'msg' => ''];
        } elseif ($this->parameter['status'] == 2) {
            $status = 'failed';
            $this->return = ['code' => 0, 'msg' => '代付失败'];
        } else {
            $this->return = ['code' => 0, 'msg' => 'error'];
            return;
        }

        $this->re = $this->return;
        $this->updateTransferOrder($this->money, $real_money, $params['sn'],//第三方转账编号
            $params['createTime'], $status);
    }

    /**
     * 银行名称
     * @return mixed
     */
    private function getBankName()
    {
        $banks = [
            'Gcash'                     => 'gcash',
            'Paymaya Philippines, Inc.' => 'paymaya'
        ];
        if (isset($banks[$this->bankCode])) {
            return $banks[$this->bankCode];
        } else {
            return 'gcash';
        }
    }

}
