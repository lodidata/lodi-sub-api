<?php

namespace Logic\Transfer\ThirdParty;


class KKPAY extends BASES
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
            'mchid'        => $this->partnerID,
            'out_trade_no' => $this->orderID,
            'money'        => bcdiv($this->money, 100, 2),
            'bankname'     => $this->getBankName(),
            'subbranch'    => $this->bankName,
            'accountname'  => $this->bankUserName,
            'cardnumber'   => $this->bankCard,
            'notifyurl'    => $this->payCallbackDomain . '/thirdAdvance/callback/kkpay',
        ];
        $data['pay_md5sign'] = $this->sign($data);
        $this->payUrl .= '/Payment_Dfpay_add.html';
        $this->parameter = $data;
        $this->basePostNew();

        $result = json_decode($this->re, true);

        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;
        if ($this->httpCode == 200) {
            if ($result['status'] == 'success') {
                $this->return['code'] = 10500;
                $this->return['balance'] = $this->money;
                $this->return['msg'] = $message;
                $this->transferNo = $result['transaction_id'];//第三方订单号
                //成功就直接返回了
                return;
            } else {
                $message = 'http_code:' . $this->httpCode . 'errorMsg:' . json_encode($result, JSON_UNESCAPED_UNICODE);
                $this->updateTransferOrder($this->money, 0, '', '', 'failed', null, $message);
                $this->return['code'] = 886;
                $this->return['balance'] = 0;
                $this->return['msg'] = 'KKPAY:' . $message ?? '代付失败';
                return;
            }
        }

        //$message = json_encode($result);
        $this->return['code'] = 886;
        $this->return['balance'] = $this->money;
        $this->return['msg'] = $message;
        $this->transferNo = '';//第三方订单号
    }

    //查询余额
    public function getThirdBalance()
    {
        $params = [
            'mchid' => $this->partnerID,
        ];
        $params['pay_md5sign'] = $this->sign($params);

        $this->parameter = $params;

        $this->payUrl .= "/Payment_Dfpay_balance.html";
        $this->basePostNew();
        $result = json_decode($this->re, true);
        $code = isset($result['status']) ? $result['status'] : 'error';
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;

        if ($this->httpCode == 200 && $code == 'success') {
            $this->return['code'] = 10509;
            $this->return['balance'] = bcmul($result['balance'], 100);
            $this->return['msg'] = $message;
            return;
        }

        $this->_end();
        throw new \Exception('http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message);

    }

    //查询代付结果
    public function getTransferResult()
    {
        $data = [
            'mchid'        => $this->partnerID,
            'out_trade_no' => $this->orderID,
        ];
        $data['pay_md5sign'] = $this->sign($data);

        $this->payUrl .= '/Payment_Dfpay_query.html';
        $this->parameter = $data;

        $this->basePostNew();

        $result = json_decode($this->re, true);

        $code = isset($result['status']) ? $result['status'] : 'error';
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;

        if ($this->httpCode == 200) {
            if ($code == 'success') {
                //订单状态 refCode (1成功，2失败，3处理中)
                if ($result['refCode'] == '1') {
                    $status = 'paid';
                    $this->return = ['code' => 1, 'msg' => $message];
                } elseif ($result['status'] == '2') {
                    $status = 'failed';
                    $this->return = ['code' => 0, 'msg' => $message];
                } else {
                    $this->return = ['code' => 0, 'msg' => $message];
                    return;
                }

                $real_money = bcmul($result['amount'], 100);
                $fee = $this->money - $real_money;
                $this->updateTransferOrder($this->money, $real_money, $result['transaction_id'],//第三方转账编号
                    '', $status, $fee, $message);
                return;
            }
        }
        $this->return = ['code' => 886, 'transfer' => '', 'msg' => 'http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message];
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
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->parameter));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
        ]);

        $response = curl_exec($ch);
        $this->curlError = curl_error($ch);
        $this->httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->re = $response;
        curl_close($ch);
    }

    //生成签名
    public function sign($param)
    {
        unset($param['pay_md5sign'], $param['extends'], $param['sign']);
        $newParam = array_filter($param);
        ksort($newParam);
        if (!empty($newParam)) {
            $sortParam = [];
            foreach ($newParam as $k => $v) {
                if (empty($v)) {
                    continue;
                }
                $sortParam[] = $k . '=' . $v;
            }
            $originalString = implode('&', $sortParam) . '&key=' . $this->key;
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

        //订单状态付款状态：(“00” 为成功，“11” 为失败)
        if ($this->parameter['returncode'] == '00') {
            $status = 'paid';
            $this->return = ['code' => 1, 'msg' => ''];
        } elseif ($this->parameter['returncode'] == '11') {
            $status = 'failed';
            $this->return = ['code' => 0, 'msg' => '代付失败'];
        } else {
            $this->return = ['code' => 0, 'msg' => 'error'];
            return;
        }

        $this->re = $this->return;
        $this->updateTransferOrder($this->money, $real_money, $this->parameter['transaction_id'],//第三方转账编号
            '', $status);
    }


    private function getBankName()
    {

        $banks = [
            "AUB"                                    => "PH_AUB",
            "PB"                                     => "PH_PRB",
            "PBC"                                    => "PH_PBC",
            "PBB"                                    => "PH_PBB",
            "PNB"                                    => "PH_PNB",
            "PSB"                                    => "PH_PSB",
            "PTC"                                    => "PH_PTC",
            "PVB"                                    => "PH_PVB",
            "RBG"                                    => "PH_RBG",
            "RB"                                     => "PH_ROB",
            "SBC"                                    => "PH_SEC",
            "SBA"                                    => "PH_SBA",
            "SSB"                                    => "PH_SSB",
            "UCPB SAVINGS BANK"                      => "PH_UCBSB",
            "Queen City Development Bank, Inc."      => "PH_QCB",
            "GrabPay"                                => "PH_GRABPAY",
            "Banco De Oro Unibank, Inc."             => "PH_BDO",
            "Bangko Mabuhay (A Rural Bank), Inc."    => "PH_BMB",
            "BOC"                                    => "PH_BOC",
            "CTBC"                                   => "PH_CTBC",
            "CBS"                                    => "PH_CBS",
            "CBC"                                    => "PH_CBC",
            "BDO Network Bank, Inc."                 => "PH_ONB",
            "Camalig"                                => "PH_CMG",
            "DBI"                                    => "PH_DBI",
            "Gcash"                                  => "PH_GCASH",
            "Cebuana Lhuillier Rural Bank, Inc."     => "PH_CEBRUR",
            "Landbank of the Philippines"            => "PH_LBP",
            "Maybank Philippines, Inc."              => "PH_MPI",
            "Partner Rural Bank (Cotabato), Inc."    => "PH_PAR",
            "BPI Direct Banko, Inc., A Savings Bank" => "PH_BPIDB",
            "BPI"                                    => "PH_BPI",
            "SCB"                                    => "PH_SCB",
            "CIMB"                                   => "PH_CIMB",
            "Paymaya Philippines, Inc."              => "PH_PAYMAYA",
            "ALLBANK (A Thrift Bank), Inc."          => "PH_ABP"
        ];
        return $banks[$this->bankCode];
    }

}
