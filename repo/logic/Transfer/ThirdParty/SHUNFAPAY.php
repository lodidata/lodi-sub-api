<?php

namespace Logic\Transfer\ThirdParty;

use Logic\Recharge\Recharge;

class SHUNFAPAY extends BASES
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
        //组装参数
        $data = [
            'client_id' => $this->partnerID,
            'bill_number' => $this->orderID,
            'amount' => bcdiv($this->money, 100, 2),
            'receiver_name' => $this->bankUserName,
            'receiver_account' => $this->bankCard,
            'bank' => $this->getBankName(),
            'bank_branch' => 'N/A',
            'notify_url' => $this->payCallbackDomain . '/thirdAdvance/callback/shunfapay',
            'remark' => 'no'
        ];

        $data['sign'] = $this->sign($data);
        $this->payUrl .= '/api/v3/withdrawals';
        $this->parameter = $data;
        $this->basePostNew();

        $result = json_decode($this->re, true);

        $status = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;

        $code = 886;
        $this->transferNo = '';
        $this->return['balance'] = $this->money;
        if ($this->httpCode == 200) {
            if ($status === 0) {
                $code = 10500;
                $this->transferNo = $result['bill_number'];//第三方订单号
                $this->fee = $result['fee'];
                $this->return['balance'] = $result['amount'];
            }
        } else {
            $message = 'http_code:' . $this->httpCode . 'errorMsg:' . json_encode($result, JSON_UNESCAPED_UNICODE);
        }

        if ($code != 10500) {
            $this->updateTransferOrder($this->money, 0, '', '', 'failed', null, $message);
        }
        $this->return['code'] = $code;
        $this->return['msg'] = $message;
    }

    //查询余额
    public function getThirdBalance()
    {
        $data = [
            'client_id' => $this->partnerID,
        ];
        $data['sign'] = $this->sign($data);
        $this->payUrl .= '/api/v3/balance';
        $this->parameter = $data;
        $this->basePostNew();
        $result = json_decode($this->re, true);

        $code = isset($result['code']) ? $result['code'] : false;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;


        if ($this->httpCode == 200) {
            if ((int)$code === 0) {
                $this->return['code'] = 10509;
                $this->return['balance'] = bcmul($result['available_amount'], 100, 0);
                $this->return['msg'] = $message;
                return;
            }
        }
        $this->_end();
        throw new \Exception('http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message);
    }

    //查询代付结果
    public function getTransferResult()
    {
        $data = [
            'client_id' => $this->partnerID,
            'bill_number' => $this->orderID,
        ];
        $data['sign'] = $this->sign($data);
        $this->payUrl .= '/api/v3/withdrawals/query';
        $this->parameter = $data;

        $this->basePostNew();

        $result = json_decode($this->re, true);
        $code = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;
        $message .= " http_code:  {$this->httpCode} ";
        if ($this->httpCode == 200 && (int)$code === 0) {
            //订单状态 status (等待 处理中 已完成  失败 订单不存在 )
            switch ($result['status']) {
                case '等待':
                case '处理中':
                    $status = 'pending';
                    $this->return = ['code' => 0, 'msg' => $message];
                    break;
                case '已完成':
                    $status = 'paid';
                    $this->return = ['code' => 1, 'msg' => $message];
                    break;
                default:
                    $status = 'failed';
                    $this->return = ['code' => 0, 'msg' => $message];
                    break;
            }

            $real_money = bcmul($result['amount'], 100);
            $this->updateTransferOrder($this->money, $real_money, $result['bill_number'],//第三方转账编号
                '', $status, $result['fee'], $message);
            return;
        }

        $this->return = ['code' => 886, 'transfer' => '', 'msg' => 'http_code:' . $this->httpCode . ' message:' . $message];
    }

    public function basePostNew()
    {
        $this->payRequestUrl = $this->payUrl;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->payUrl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($this->parameter));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);

        $response = curl_exec($ch);
        $this->curlError = curl_error($ch);
        $this->httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->re = $response;
        curl_close($ch);
    }

    //生成签名
    public function sign($data)
    {
        if (empty($data)) {
            return false;
        }
        unset($data['sign']);
        ksort($data);
        $str = urldecode(http_build_query($data)) . '&key=' . $this->key;
        return md5($str);
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

        //订单状态 status (等待 处理中 已完成  失败 订单不存在 )
        switch ($params['status']) {
            case '等待':
            case '处理中':
                $status = 'pending';
                $this->return = ['code' => 0, 'msg' => '处理中'];
                break;
            case '已完成':
                $status = 'paid';
                $this->return = ['code' => 1, 'msg' => ''];
                break;
            default:
                $status = 'failed';
                $this->return = ['code' => 0, 'msg' => 'error'];
                break;
        }

        $this->re = $this->return;
        $amount = bcmul($params['amount'], 100);//以分为单位
        $real_money = $amount;//实际到账金额
        $fee = bcmul($params['fee'], 100);//以分为单位
        $this->updateTransferOrder($this->money, $real_money, $params['bill_number'],//第三方转账编号
            '', $status, $fee);
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
        if (isset($ci->get('settings')['website']['site_type'])) {
            $country_code = $ci->get('settings')['website']['site_type'];
        }

        if ($country_code == 'ncg') {
            return 'gcash';
        } else {
            $banks = [
                "Starpay" => "Starpay",
                "PB" => "Producers Bank",
                "PBC" => "Philippine Bank of Communications",
                "PBB" => "Philippine Business Bank",
                "PNB" => "Philippine National Bank",
                "PSB" => "Philippine Savings Bank",
                "PTC" => "Philippine Trust Company",
                "SBC" => "Security Bank Corporation",
                "SBA" => "Sterling Bank of Asia",
                "SSB" => "Sun Savings Bank",
                "UCPB SAVINGS BANK" => "UCPB Savings Bank",
                "GrabPay" => "GrabPay",
                "BOC" => "Bank of Commerce",
                "Camalig" => "Camalig Bank",
                "Gcash" => "gcash",
                "Metropolitan Bank and Trust Co" => "Metropolitan Bank and Trust Co",
                "Partner Rural Bank (Cotabato), Inc." => "Partner Rural Bank (Cotabato), Inc."
            ];
            return $banks[$this->bankCode];
        }
    }

}
