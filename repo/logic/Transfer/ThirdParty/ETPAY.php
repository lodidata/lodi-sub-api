<?php

namespace Logic\Transfer\ThirdParty;

use Logic\Recharge\Recharge;
use Utils\Curl;

/**
 *
 * etpay代付
 */
class ETPAY extends BASES
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
        $channel = 'PHP_DF';
        $config_params = !empty($this->thirdConfig['params']) ? json_decode($this->thirdConfig['params'], true) : [];
        if (!empty($config_params) && isset($config_params['channel'])) {
            $channel = $config_params['channel'];
        }

        $params = [
            "number"       => $this->partnerID, //商户号
            'channel'      => $channel,
            "order_id"     => $this->orderID,   //订单号
            'bank_code'    => trim($this->getBankName()),
            'bank_account' => trim($this->bankCard),
            'account_name' => trim($this->bankUserName),
            'notify_url'   => $this->payCallbackDomain . '/thirdAdvance/callback/etpay',
            "amount"       => bcdiv($this->money, 100, 2),   //支付金额
            'timestamp'    => time(),
        ];
        $this->parameter = $params;
        //判断 额外参数是否为空 Determine whether the extra parameter is empty
        $this->payUrl .= '/api/gateway/withdraw';

        $this->initParam($params);
        $this->basePostNew();
        $result = isset($this->re) ? json_decode($this->re, true) : '';
        $code = isset($result['status']) ? $result['status'] : 0;
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;
        if ($this->httpCode == 200) {
            //true成功,false失败
            if ($code === true) {
                $this->return['code'] = 10500;
                $this->return['balance'] = $this->money;
                $this->return['msg'] = $message;
                //成功就直接返回了
                return;
            } else {
                $message = 'http_code:' . $this->httpCode . 'errorMsg:' . json_encode($result, JSON_UNESCAPED_UNICODE);
                $this->updateTransferOrder($this->money, 0, '', '', 'failed', null, $message);
                $this->return['code'] = 886;
                $this->return['balance'] = 0;
                $this->return['msg'] = 'ETPAY:' . $message ?? '代付失败';
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
            'number'    => $this->partnerID,
            'currency'  => 'PHP',
            'timestamp' => time()
        ];
        $this->payUrl .= "/api/gateway/wallet";
        $this->initParam($params);
        $this->basePostNew();
        $result = json_decode($this->re, true);

        if ($this->httpCode == 200) {
            $this->return['code'] = 10509;
            $this->return['balance'] = bcmul($result['balance'], 100);
            $this->return['msg'] = 'success';
            return;
        }
        $this->_end();
        throw new \Exception('http_code:' . $this->httpCode);
    }

    //代付订单查询
    public function getTransferResult()
    {
        $params = [
            'number'    => $this->partnerID,
            'order_id'  => $this->orderID,
            'timestamp' => time()
        ];
        $this->payUrl .= "/api/gateway/withdraw/getOrderInfo";
        $this->initParam($params);
        $this->basePostNew();

        $result = json_decode($this->re, true);
        $code = isset($result['execution']) ? $result['execution'] : 0;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;

        if ($this->httpCode == 200) {
            //订单状态：0 处理中 1 成功 2 失败
            if ($code == '1') {
                $status = 'paid';
                $this->return = ['code' => 1, 'msg' => $message];
            } else {
                $status = "pending";
                $this->return = ['code' => 0, 'msg' => $message];
            }

            $real_money = bcmul($result['amount'], 100);
            $fee = $this->money - $real_money;
            $this->updateTransferOrder($this->money, $real_money, $result['order_id'], '', $status, $fee, $message);
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
    public function sign($requestData)
    {
        if (empty($requestData)) {
            return false;
        }
        ksort($requestData);
        foreach ($requestData as $key => $value) {
            if ($key != 'sign') {
                $array[] = "$key=$value";
            }
        }
        $signStr = implode('&', $array);
        $signStr = $signStr . "&key=" . $this->key;
        return md5($signStr);
    }

    public function basePostNew($referer = null)
    {

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
        if ($this->parameter['execution'] == '1') {
            $status = 'paid';
            $this->return = ['code' => 1, 'msg' => ''];
        } else {
            $this->return = ['code' => 0, 'msg' => 'error'];
            return;
        }

        $this->re = $this->return;
        $this->updateTransferOrder($this->money, $real_money, $params['order_id'],//第三方转账编号
            '', $status);
    }

    /**
     * 银行名称
     * @return mixed
     */
    private function getBankName()
    {

        $banks = [
            "AUB"       => "AUB",
            "PNB"       => "PNB",
            "BOC"       => "BankofCommerce",
            "Chinabank" => "Chinabank",
            "Gcash"     => "GCASH",
            "BPI"       => "BPI",
        ];
        return $banks[$this->bankCode];
    }
}
