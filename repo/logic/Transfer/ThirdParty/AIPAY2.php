<?php

namespace Logic\Transfer\ThirdParty;

/**
 *
 * AIPAY2代付
 */
class AIPAY2 extends BASES
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
            "mer_no"       => $this->partnerID, //商户号
            "order_no"     => $this->orderID,   //订单号
            "method"       => "fund.apply",
            "order_amount" => bcdiv($this->money, 100, 2),   //代付金额
            "currency"     => "PHP",            //币种
            "acc_code"     => $this->getBankName(),   //收款银行编号
            "acc_name"     => $this->bankUserName,   //收款人姓名
            "acc_no"       => $this->bankCard,   //收款人账号
            "returnurl"    => $this->payCallbackDomain . '/thirdAdvance/callback/aipay2',   //回调地址
        ];

        $this->parameter = $params;

        $this->initParam($params);
        $this->basePostNew();
        $result = isset($this->re) ? json_decode($this->re, true) : '';
        $status = isset($result['status']) ? $result['status'] : 'fail';
        $message = isset($result['status_mes']) ? $result['status_mes'] : 'errorMsg:' . (string)$this->re;

        if ($this->httpCode == 200) {
            //true成功,false失败
            if ($status === 'success') {
                $this->return['code'] = 10500;
                $this->return['balance'] = $this->money;
                $this->return['msg'] = $message;
                $this->transferNo = $result['sys_no'];//第三方订单号
                //成功就直接返回了
                return;
            } else {
                $message = 'http_code:' . $this->httpCode . 'errorMsg:' . json_encode($result, JSON_UNESCAPED_UNICODE);
                $this->updateTransferOrder($this->money, 0, '', '', 'failed', null, $message);
                $this->return['code'] = 886;
                $this->return['balance'] = 0;
                $this->return['msg'] = 'AIPAY2:' . $message ?? '代付失败';
                return;
            }
        }

        $this->return['code'] = 886;
        $this->return['balance'] = $this->money;
        $this->return['msg'] = $message;
        $this->transferNo = '';//第三方订单号
    }

    //查询余额
    public function getThirdBalance()
    {
        $params = [
            'mer_no' => $this->partnerID,
            'method' => 'fund.query',
        ];

        $this->initParam($params);
        $this->basePostNew();
        $result = json_decode($this->re, true);
        $checkstatus = isset($result['checkstatus']) ? $result['checkstatus'] : 'fail';
        $message = isset($result['status_mes']) ? $result['status_mes'] : 'errorMsg:' . (string)$this->re;

        if ($this->httpCode == 200) {
            if ($checkstatus === 'success') {
                $this->return['code'] = 10509;
                $this->return['balance'] = bcmul($result['currency']['PHP'], 100);
                $this->return['msg'] = $message;
                return;
            }
        }
        $this->_end();
        throw new \Exception('http_code:' . $this->httpCode . ' code:' . $checkstatus . ' message:' . $message);
    }

    //代付订单查询
    public function getTransferResult()
    {
        $params = [
            'mer_no'   => $this->partnerID,
            'order_no' => $this->orderID,
            'method'   => 'fund.apply.check'
        ];
        $this->initParam($params);
        $this->basePostNew();

        $result = json_decode($this->re, true);
        $checkstatus = isset($result['checkstatus']) ? $result['checkstatus'] : 1;
        $message = isset($result['status_mes']) ? $result['status_mes'] : 'errorMsg:' . (string)$this->re;

        if ($this->httpCode == 200) {
            if ($checkstatus === 'success') {

                //订单状态：waiting 处理中 fail 失败 success 成功
                if ($result['resultstatus'] == 'success') {
                    $status = 'paid';
                    $this->return = ['code' => 1, 'msg' => $message];

                } elseif ($result['resultstatus'] == 'fail') {
                    $status = 'failed';
                    $this->return = ['code' => 0, 'msg' => $message];
                } elseif ($result['resultstatus'] == 'waiting') {
                    $status = "pending";
                    $this->return = ['code' => 0, 'msg' => $message];
                } else {
                    $this->return = ['code' => 0, 'msg' => $message];
                    return;
                }

                $real_money = bcmul($result['order_realityamount'], 100);
                $fee = $this->money - $real_money;
                $this->updateTransferOrder(
                    $this->money,
                    $real_money,
                    $result['order_no'],
                    '',
                    $status,
                    $fee,
                    $message
                );
                return;
            } else if ($checkstatus === 'fail') {
                //fail 没有单号改为失败
                $this->updateTransferOrder($this->money, 0, '', '', 'failed', null, $message);
                return;
            }

        }
        $this->return = ['code' => 886, 'transfer' => '', 'msg' => 'http_code:' . $this->httpCode . ' code:' . $checkstatus . ' message:' . $message];
    }

    //组装数组
    public function initParam($params = [])
    {
        $data = $params;
        $data['sign'] = $this->sign($params);  //校验码
        $this->parameter = $data;
    }

    //验证回调签名
    public function sign($data)
    {
        unset($data['sign']);
        ksort($data);
        reset($data);

        $str = '';
        foreach ($data as $k => $v) {
            if (is_null($v) || $v === '') continue;
            $str .= $k . '=' . $v . '&';
        }
        $str = trim($str, '&') . $this->key;
        return md5($str);
    }


    //验证回调签名
    public function verifySign($data)
    {
        $sign = $data['sign'];
        unset($data['sign']);
        ksort($data);
        reset($data);

        $str = '';
        foreach ($data as $k => $v) {
            if (is_null($v) || $v === '') continue;
            $str .= $k . '=' . $v . '&';
        }

        $str = trim($str, '&') . $this->pubKey;
        $sign_new = md5($str);

        if ($sign === $sign_new) {
            return true;
        }
        return false;
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
            'Content-Length:' . strlen($params_data),
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

        if (!$this->verifySign($params)) {
            throw new \Exception('Sign error');
        }

        //订单状态已经成功 或者 消息与订单状态一致
        if ($this->order['status'] == 'paid') {
            return;
        }
        $amount = bcmul($params['order_realityamount'], 100);//以分为单位
        $real_money = $amount;//实际到账金额

        //订单状态付款状态：(//订单状态：waiting 处理中 fail 失败 success 成功)
        if ($this->parameter['result'] == 'success') {
            $status = 'paid';
            $this->return = ['code' => 1, 'msg' => ''];
        } elseif ($this->parameter['result'] == 'waiting') {
            $status = 'failed';
            $this->return = ['code' => 0, 'msg' => '代付处理中'];
        } elseif ($this->parameter['result'] == 'fail') {
            $status = 'failed';
            $this->return = ['code' => 0, 'msg' => '代付失败'];
        } else {
            $this->return = ['code' => 0, 'msg' => 'error'];
            return;
        }

        $this->re = $this->return;
        $this->updateTransferOrder($this->money, $real_money, $params['order_no'],//第三方转账编号
            '', $status);
    }

    private function getBankName() {
        $banks = [
            "AUB" => "AUB",
            "ESB" => "EQB",
            "MB" => "MSB",
            "PB" => "PRB",
            "PBB" => "PBB",
            "PNB" => "PNB",
            "PTC" => "PTC",
            "PVB" => "PVB",
            "RBG" => "RBG",
            "RB" => "RSB",
            "SBC" => "SBC",
            "SBA" => "SBA",
            "SSB" => "SSB",
            "Queen City Development Bank, Inc." => "QCB",
            "Wealth Development Bank, Inc." => "WDB",
            "Yuanta Savings Bank, Inc." => "YUANSB",
            "GrabPay" => "GRABPAY",
            "BOC" => "BOC",
            "CTBC" => "CTBC",
            "CBS" => "CBS",
            "CBC" => "CBC",
            "Camalig" => "CMG",
            "DBI" => "DBI",
            "Gcash" => "GCASH",
            "Landbank of the Philippines" => "LBP",
            "Omnipay" => "OMNIPAY",
            "ING" => "ING",
            "BPI Direct Banko, Inc., A Savings Bank" => "BPIDB",
            "BPI" => "BPI",
            "UBPHPH" => "UBP",
            "Paymaya Philippines, Inc." => "PAYMAYA"
        ];
        if (isset($banks[$this->bankCode])) {
            return $banks[$this->bankCode];
        } else {
            return 'GCASH';
        }
    }

}
