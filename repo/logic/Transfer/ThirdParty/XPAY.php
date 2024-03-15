<?php

namespace Logic\Transfer\ThirdParty;

/**
 *
 * XPAY代付
 */
class XPAY extends BASES
{
    private $httpCode = '';

    //回调
    public function callbackMsg()
    {
        return 'OK';
    }

    //请求代付接口
    public function runTransfer()
    {
        //请求参数 Request parameter
        $data = array(
            'mer_no'       => $this->partnerID,
            'mer_order_no' => $this->orderID,
            'acc_no'       => $this->bankCard,
            'acc_name'     => $this->bankUserName,
            'ccy_no'       => 'PHP',
            'order_amount' => bcdiv($this->money, 100, 2),
            'bank_code'    => $this->getBankName(),
            'mobile_no'    => '',
            'notifyUrl'    => $this->payCallbackDomain . '/thirdAdvance/callback/xpay',
            'summary'      => '',
        );

        $data['sign'] = $this->sign($data);
        $this->parameter = $data;

        $this->basePostNew('/withdraw/singleOrder');
        $result = json_decode($this->re, true);
        $status = isset($result['status']) ? $result['status'] : 'FAIL';
        if ($status == 'SUCCESS') {
            $this->return['code'] = 10500;
            $this->return['balance'] = $this->money;
            $this->return['msg'] = '';
        } else {
            $message = isset($result['err_msg']) ? $result['err_msg'] : 'errorMsg:' . (string)$this->re;
            $this->updateTransferOrder(
                $this->money,
                0,
                '',
                '',
                'failed',
                null,
                $message
            );
            $this->return['code'] = 886;
            $this->return['balance'] = 0;
            $this->return['msg'] = 'XPAY:' . $message ?? '查询失败';
        }
    }


    //查询余额
    public function getThirdBalance()
    {
        $data = array(
            'mer_no'       => $this->partnerID,
            'request_time' => date('YmdHis', time()),
            'request_no'   => (string)(microtime(true) * 10000)

        );
        $data['sign'] = $this->querySign($data);
        $this->parameter = $data;
        $this->basePostNew('/withdraw/balanceQuery');

        $result = json_decode($this->re, true);
        $status = isset($result['query_status']) ? $result['query_status'] : 'FAIL';
        if ($status == 'SUCCESS') {
            $balanceData = [];
            foreach ($result['list'] as $currencyBalance) {
                if ($currencyBalance['ccy_no'] == 'PHP') {
                    $balanceData = $currencyBalance;
                }
            }
            if (empty($balanceData)) {
                $message = isset($result['results']) ? $result['results'] : 'errorMsg:' . (string)$this->re;
                $this->return['code'] = 886;
                $this->return['msg'] = 'XPAY:' . $message ?? '查询失败';
                $this->return['balance'] = 'balance';
            } else {
                $this->return['code'] = 10509;
                $this->return['balance'] = $balanceData['balance'] * 100;
                $this->return['msg'] = '';
            }
        } else {
            $message = isset($result['results']) ? $result['results'] : 'errorMsg:' . (string)$this->re;
            $this->return['code'] = 886;
            $this->return['msg'] = 'XPAY:' . $message ?? '查询失败';
            $this->return['balance'] = 'balance';
        }
    }

    //查询代付结果
    public function getTransferResult()
    {
        $data = [
            'mer_no'       => $this->partnerID,
            'mer_order_no' => $this->orderID,
            'request_time' => date('YmdHis', time()),
            'request_no'   => (string)(microtime(true) * 10000)
        ];
        $data['sign'] = $this->sign($data);
        $this->parameter = $data;

        $this->basePostNew('/withdraw/singleQuery');


        $re = json_decode($this->re, true);
        $status = isset($re['query_status']) ? $re['query_status'] : 'FAIL';
        $pay_no = '';
        $real_money = $this->money;
        $fee = null;
        $success_time = '';
        if ($status == 'SUCCESS') {
            $results = json_decode($re, true);
            $pay_no = $results['order_no'];
            $real_money = $results['order_amount'] * 100;//以分为单位
            $fee = $results['fee'] * 100;
            $success_time = date('Y-m-d H:i:s');
            //存款订单状态: 订单状态 0未结算,1已结算,2结算中,3结算中(人工复查处理),4已撤销
            switch ($results['status']) {
                case 'SUCCESS'://交易成功
                    $status = 'paid';
                    break;
                case 'FAIL'://交易失败
                    $status = 'failed';
                    break;
                default:
                    $status = 'pending';
                    break;
            }
        } else {
            $status = 'pending';//支付状态设置的宽泛一些！
            $message = isset($re['results']) ? $re['results'] : 'errorMsg:' . (string)$this->re;
        }
        if ($status == 'SUCCESS') {//支付成功
            $message = '代付成功';
            $this->return = ['code' => 10508, 'balance' => $real_money, 'msg' => ''];
        } else {
            $real_money = 0;
            $message = $status == 'UNKNOW' ? '代付中-' . $message : '代付失败-' . $message;
            $this->return = ['code' => 886, 'balance' => 0, 'msg' => 'XPAY:' . $message];
        }
        if (in_array($status, ['SUCCESS', 'FAIL'])) {
            $this->updateTransferOrder(
                $this->money,
                $real_money,
                $pay_no,
                $success_time,
                $status,
                $fee,
                $message
            );
        }
    }

    //生成签名
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
        $str = trim($str, '&');
        $prikey = "-----BEGIN RSA PRIVATE KEY-----\n" . wordwrap($this->key, 64, "\n", true) . "\n-----END RSA PRIVATE KEY-----";
        $sign = '';
        $key = openssl_pkey_get_private($prikey);
        foreach (str_split($str, 117) as $temp) {
            openssl_private_encrypt($temp, $encrypted, $key);
            $sign .= $encrypted;
        }

        $sign = base64_encode($sign);
        $sign = urlencode($sign);
        return $sign;
    }

    //查询签名
    public function querySign($data)
    {
        unset($data['sign']);
        ksort($data);
        reset($data);

        $str = '';
        foreach ($data as $k => $v) {
            if (is_null($v) || $v === '') continue;
            $str .= $k . '=' . $v . '&';
        }

        $str .= 'key=' . $this->pubKey;

        return md5($str);
    }


    public function basePostNew($str)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($curl, CURLOPT_TIMEOUT, 15);
        curl_setopt($curl, CURLOPT_URL, $this->payUrl . $str);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        $post_data = json_encode($this->parameter);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
            'Content-Length:' . strlen($post_data),
            'Cache-Control: no-cache',
            'Pragma: no-cache'
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($curl);

        $this->http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $this->re = $response;

        curl_close($curl);
    }

    /**
     * 代付异步回调
     * @param array $params
     * @return mixed
     */
    public function callbackResult($params)
    {
        $this->parameter = $params;
        $field = [
            'mer_no'       => $params['mer_no'],
            'order_no'     => $params['order_no'],
            'create_time'  => $params['create_time'],
            'order_amount' => $params['order_amount'],
            'ccy_no'       => $params['ccy_no'],
            'mer_order_no' => $params['mer_order_no'],
            'pay_time'     => $params['pay_time'],
            'status'       => $params['status'],
        ];
        $change = 0;
        $pay_no = $field['order_no'];
        $real_money = $field['order_amount'] * 100;//以分为单位
        $success_time = $field['pay_time'];

        $sign = $this->querySign($field);
        if ($sign != $params['sign']) {
            $this->return = ['code' => 886, 'msg' => 'Sign error'];
        }//金额不一致
        elseif ($this->order['money'] != $real_money) {
            $this->return = ['code' => 886, 'msg' => 'money error'];
        } elseif ($field['status'] == 'UNKNOW') {
            $status = 'failed';
            $this->return = ['code' => 0, 'msg' => '代付处理中'];
        }//订单状态
        elseif ($field['status'] == 'SUCCESS') {
            $change = 1;
            $status = 'paid';
            $this->return = ['code' => 0, 'msg' => ''];
        } else {
            $change = 1;
            $status = 'failed';
            $this->return = ['code' => 0, 'msg' => 'TUPAY:代付失败'];
        }

        $this->re = $this->return;
        if ($change) {
            $this->updateTransferOrder(
                $this->money,
                $real_money,
                $pay_no,
                $success_time,
                $status,
            );
        }
    }

    /**
     * 银行名称
     * @return mixed
     */
    private function getBankName()
    {
        $banks = [
            "Gcash"             => "GCASH",
            "AUB"               => "Asia United Bank",
            "UnionBankEON"      => "UnionBank EON",
            "Starpay"           => "Starpay",
            "EB"                => "Eastwest Bank",
            "ESB"               => "Equicom Savings Bank",
            "MB"                => "Malayan Bank",
            "ERB"               => "EastWest Rural Bank",
            "PB"                => "Producers Bank",
            "PBC"               => "Philippine Bank of Communications",
            "PBB"               => "Philippine Business Bank",
            "PNB"               => "Philippine National Bank",
            "PSB"               => "Philippine Savings Bank",
            "PTC"               => "Philippine Trust Company",
            "PVB"               => "Philippine Veterans Bank",
            "RBG"               => "Rural Bank of Guinobatan, Inc.",
            "RCBC"              => "Rizal Commercial Banking Corporation",
            "RB"                => "Robinsons Bank",
            "SBC"               => "Security Bank Corporation",
            "SBA"               => "Sterling Bank Of Asia",
            "SSB"               => "Sun Savings Bank",
            "UCPBSAVINGSBANK"   => "UCPB SAVINGS BANK",
            "QCDBI"             => "Queen City Development Bank, Inc.",
            "UCPB"              => "United Coconut Planters Bank",
            "WDBI"              => "Wealth Development Bank, Inc.",
            "YSBI"              => "Yuanta Savings Bank, Inc.",
            "GrabPay"           => "GrabPay Philippines",
            "BDOUI"             => "Banco De Oro Unibank, Inc.",
            "BMI"               => "Bangko Mabuhay (A Rural Bank), Inc.",
            "BOC"               => "Bank Of Commerce",
            "CTBC"              => "CTBC Bank (Philippines), Inc.",
            "Chinabank"         => "Chinabank",
            "CBS"               => "Chinabank Savings",
            "CBC"               => "Chinatrust Banking Corp",
            "ALLBANK"           => "ALLBANK (A Thrift Bank), Inc.",
            "BNBI"              => "BDO Network Bank, Inc.",
            "BRBI"              => "Binangonan Rural Bank Inc",
            "Camalig"           => "Camalig Bank",
            "DBI"               => "Dungganun Bank, Inc.",
            "GlobeGcash"        => "Globe Gcash",
            "CLRBI"             => "Cebuana Lhuillier Rural Bank, Inc.",
            "ISLABANK"          => "ISLA Bank (A Thrift Bank), Inc.",
            "LOTP"              => "Landbank of the Philippines",
            "MPI"               => "Maybank Philippines, Inc.",
            "MBATC"             => "Metropolitan Bank and Trust Co",
            "Omnipay"           => "Omnipay",
            "PRBI"              => "Partner Rural Bank (Cotabato), Inc.",
            "PPI"               => "Paymaya Philippines, Inc.",
            "AlliedBankingCorp" => "Allied Banking Corp",
            "ING"               => "ING Bank N.V.",
            "BDBIASB"           => "BPI Direct Banko, Inc., A Savings Bank",
            "CSB"               => "Citystate Savings Bank Inc.",
            "BPI"               => "Bank Of The Philippine Islands"
        ];
        return $banks[$this->bankCode];
    }
}
