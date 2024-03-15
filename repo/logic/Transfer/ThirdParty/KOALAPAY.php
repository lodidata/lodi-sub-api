<?php

namespace Logic\Transfer\ThirdParty;

class KOALAPAY extends BASES
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
            'app_key'     => $this->partnerID,
            'balance'     => bcdiv($this->money, 100, 2),
            'ord_id'      => $this->orderID,
            'card'        => $this->bankCard,
            'name'        => $this->bankUserName,
            'p_bank_code' => $this->getBankName(),
            'notify_url'  => $this->payCallbackDomain . '/thirdAdvance/callback/koalapay',
        ];

        $data['sign'] = $this->sign($data);
        $this->payUrl .= '/api/withdraw';
        $this->parameter = $data;
        $this->basePostNew();

        $result = json_decode($this->re, true);
        $status = isset($result['err']) ? $result['err'] : '1';
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;

        $code = 886;
        $this->transferNo = '';
        if ($this->httpCode == 200) {
            if ($status === 0) {
                $code = 10500;
            }
        } else {
            $message = 'http_code:' . $this->httpCode . 'errorMsg:' . json_encode($result, JSON_UNESCAPED_UNICODE);
        }

        if ($code != 10500) {
            $this->updateTransferOrder($this->money, 0, '', '', 'failed', null, $message);
        }
        $this->return['code'] = $code;
        $this->return['balance'] = $this->money;
        $this->return['msg'] = $message;
    }

    //查询余额
    public function getThirdBalance()
    {

        $data['app_key'] = $this->partnerID;
        $data['sign'] = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl .= "/api/balance";
        $this->basePostNew();
        $result = json_decode($this->re, true);
        $code = isset($result['err']) ? $result['err'] : "1";
        $message = isset($result['err_msg']) ? $result['err_msg'] : 'errorMsg:' . (string)$this->re;

        if ($this->httpCode == 200) {
            if ($code === 0) {
                $this->return['code'] = 10509;
                $this->return['balance'] = bcmul($result['balance'], 100);
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
            'ord_id'  => $this->transferNo,
            'app_key' => $this->partnerID
        ];

        $data['sign'] = $this->sign($data);
        $this->payUrl .= '/api/withdraw_query';

        $this->parameter = $data;
        $this->basePostNew();
        $result = json_decode($this->re, true);
        $code = isset($result['err']) ? $result['err'] : '1';
        $message = isset($result['err_msg']) ? $result['err_msg'] : 'errorMsg:' . (string)$this->re;
        $message .= " http_code:  {$this->httpCode} ";
        if ($this->httpCode == 200) {
            if ($code === 0) {
                //订单状态 refCode (1： 成功)
                switch ($result['data']['status']) {
                    case '0':
                        $status = 'pending';
                        $this->return = ['code' => 0, 'msg' => $message];
                        break;
                    case '1':
                        $status = 'paid';
                        $this->return = ['code' => 1, 'msg' => ''];
                        break;
                    case '2':
                        $status = 'failed';
                        $this->return = ['code' => 0, 'msg' => $message];
                        break;
                    default:
                        $status = 'pending';
                        $this->return = ['code' => 0, 'msg' => $message];
                        break;
                }

                $real_money = bcmul($result['balance'], 100);
                $fee = $this->money - $real_money;
                $this->updateTransferOrder($this->money, $real_money, $result['orderNo'],//第三方转账编号
                    '', $status, $fee, $message);
                return;
            }
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
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($this->parameter));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json;charset=utf-8',
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
        $str = implode(array_values($data), '');
        $str .= $this->key;
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

        switch ($params['status']) {
            case '1':
                $status = 'paid';
                $this->return = ['code' => 1, 'msg' => '成功'];
                break;
            default:
                $status = 'failed';
                $this->return = ['code' => 0, 'msg' => $params['msg']];
                break;
        }

        $this->re = $this->return;
        $realMoney = bcmul($params['amount'], 100);//以分为单位
        $fee = $this->money - $realMoney;

        $this->updateTransferOrder($this->money, $realMoney, $params['order'],//第三方转账编号
            '', $status, $fee);
    }


    /**
     * 银行名称
     * @return mixed
     */
    private function getBankName()
    {
        $banks = [
            "AUB"                                    => "AUB",
            "Starpay"                                => "STP",
            "ESB"                                    => "ESB",
            "PB"                                     => "PDB",
            "PBC"                                    => "PBC",
            "PBB"                                    => "PBB",
            "PNB"                                    => "PNB",
            "PSB"                                    => "PSB",
            "PTC"                                    => "PTC",
            "SBC"                                    => "SBC",
            "SBA"                                    => "SLB",
            "SSB"                                    => "SSB",
            "UCPB SAVINGS BANK"                      => "USB",
            "GrabPay"                                => "GP",
            "BOC"                                    => "BC",
            "CTBC"                                   => "CTBC",
            "CBS"                                    => "CBS",
            "CBC"                                    => "CBC",
            "Camalig"                                => "CB",
            "Gcash"                                  => "gcash",
            "Metropolitan Bank and Trust Co"         => "mbt",
            "Omnipay"                                => "OP",
            "ING"                                    => "IB",
            "BPI"                                    => "bpi",
            "STP"                                    => "STP",
            "SCB"                                    => "SCB",
            "UBPHPH"                                 => "UBP",
            "ALLBANK (A Thrift Bank), Inc."          => "AB",
            "Bangko Mabuhay (A Rural Bank), Inc."    => "BM",
            "BPI Direct Banko, Inc., A Savings Bank" => "BK",
            "BDO Network Bank, Inc."                 => "BNB",
            "ISLA Bank (A Thrift Bank), Inc."        => "ISLA",
            "Partner Rural Bank (Cotabato), Inc."    => "PRB",
            "Paymaya Philippines, Inc."              => "PMP",
            "RB"                                     => "RBB",
            "SBA"                                    => "SLB"
        ];
        return $banks[$this->bankCode];
    }

}
