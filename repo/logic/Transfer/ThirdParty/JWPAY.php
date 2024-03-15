<?php

namespace Logic\Transfer\ThirdParty;

use Logic\Recharge\Recharge;
use Utils\Curl;

/**
 *
 * jwpay代付
 */
class JWPAY extends BASES
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

        $isTest = false;
        $configParams = !empty($this->thirdConfig['params']) ? json_decode($this->thirdConfig['params'],true) : [];
        if (isset($configParams['isTest'])) {
            $isTest = $configParams['isTest'];
        }

        $params = [
            'Amount'             => floatval(bcdiv($this->money, 100, 2)),
            'CurrencyId'         => 5,
            'IsTest'             => $isTest,
            'PayeeAccountName'   => $this->bankUserName,
            'PayeeAccountNumber' => $this->bankCard,
            'PayeeBankName'      => '',
            'PayeeIFSCCode'      => '',
            'PayeePhoneNumber'   => '',
            'PaymentChannelId'   => 14,
            'ShopInformUrl'      => $this->payCallbackDomain . '/thirdAdvance/callback/jwpay',
            'ShopOrderId'        => $this->orderID,
            'ShopRemark'         => '',
            'ShopUserLongId'     => $this->partnerID
        ];

        $this->parameter = $params;
        //判断 额外参数是否为空 Determine whether the extra parameter is empty
        $this->payUrl .= '/api/createPaymentOrder';

        $this->initParam($params);
        $this->basePostNew();
        $result = isset($this->re) ? json_decode($this->re, true) : '';
        var_dump($this->re);exit;
        $code = isset($result['Success']) ? $result['Success'] : 0;
        $message = isset($result['ErrorMessage']) ? $result['ErrorMessage'] : 'errorMsg:' . (string)$this->re;
        if ($this->httpCode == 200) {
            //true成功,false失败
            if ($code === true) {
                $this->return['code'] = 10500;
                $this->return['balance'] = $this->money;
                $this->return['msg'] = $message;
                $this->transferNo = $result['TrackingNumber'];   //第三方订单号
                //成功就直接返回了
                return;
            } else {
                $message = 'http_code:' . $this->httpCode . 'errorMsg:' . json_encode($result, JSON_UNESCAPED_UNICODE);
                $this->updateTransferOrder($this->money, 0, '', '', 'failed', null, $message);
                $this->return['code'] = 886;
                $this->return['balance'] = 0;
                $this->return['msg'] = 'JWPAY:' . $message ?? '代付失败';
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
            'CurrencyId' => 5,
            'ShopUserLongId' => $this->partnerID,
        ];
        $this->payUrl .= "/api/shopGetBalance";
        $this->initParam($params);
        $this->basePostNew();
        $result = json_decode($this->re, true);
        $code = isset($result['Success']) ? $result['Success'] : 1;
        $message = isset($result['ErrorMessage']) ? $result['ErrorMessage'] : 'errorMsg:' . (string)$this->re;

        if ($this->httpCode == 200) {
            if ($code === true) {
                $this->return['code'] = 10509;
                $this->return['balance'] = bcmul($result['AmountAvailable'], 100);
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
            'ShopUserLongId' => $this->partnerID,
            'ShopOrderId' => $this->orderID
        ];
        $this->payUrl .= "/api/shopGetPaymentOrder";
        $this->initParam($params);
        $this->basePostNew();

        $result = json_decode($this->re, true);
        $code = isset($result['Success']) ? $result['Success'] : 0;
        $message = isset($result['ErrorMessage']) ? $result['ErrorMessage'] : 'errorMsg:' . (string)$this->re;

        if ($this->httpCode == 200) {
            //订单状态付款状态：(//订单状态：1:待支付 2:成功 3:失败 4:待审核)
            switch ($result['PaymentOrder']['PaymentOrderStatusId']) {
                case 2:
                    $status = 'paid';
                    $this->return = ['code' => 1, 'msg' => $message];
                    break;
                case 3:
                    $status = 'failed';
                    $this->return = ['code' => 0, 'msg' => $message];
                    break;
                case 1:
                case 4:
                    $status = "pending";
                    $this->return = ['code' => 0, 'msg' => $message];
                    break;
                default:
                    $status = "pending";
                    $this->return = ['code' => 0, 'msg' => $message];
            }
            
            $real_money = bcmul($result['PaymentOrder']['Amount'], 100);
            $fee = $this->money - $real_money;
            $this->updateTransferOrder($this->money, $real_money, $result['PaymentOrder']['TrackingNumber'], $result['PaymentOrder']['CreatedAt'], $status, $fee, $message);
            return;
        }
        $this->return = ['code' => 886, 'transfer' => '', 'msg' => 'http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message];
    }

    //组装数组
    public function initParam($params = [])
    {
        $data = $params;
        $data['EncryptValue'] = $this->sign($params);  //校验码
        $this->parameter = $data;
    }

    //生成签名
    public function sign($data)
    {
        if (empty($data)) {
            return false;
        }
        unset($data['EncryptValue']);

        ksort($data);
        $str = '';

        foreach ($data as $key => $value) {
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }
            if ($value !== null) {
                $str .= $key . '=' . $value . '&';
            }
        }

        $str .= 'HashKey=' . $this->key;
        $str = strtolower($str);
        return strtoupper(hash('sha256', $str));
    }

    public function basePostNew($referer = null)
    {

        $this->payRequestUrl = $this->payUrl;
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($curl, CURLOPT_TIMEOUT, 15);
        curl_setopt($curl, CURLOPT_URL, $this->payUrl);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        $post_data = json_encode($this->parameter);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($curl);
        $this->curlError = curl_error($curl);
        $this->httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $this->re = $response;
        curl_close($curl);
    }

    /**
     * 代付异步回调
     * @param array $params 异步参数
     * @return mixed
     */
    public function callbackResult($params)
    {
        $this->parameter = $params;
        if ($this->sign($params) != $params['EncryptValue']) {
            throw new \Exception('Sign error');
        }

        //订单状态已经成功 或者 消息与订单状态一致
        if ($this->order['status'] == 'paid') {
            return;
        }
        $amount = bcmul($params['Amount'], 100);//以分为单位
        $real_money = $amount;//实际到账金额

        //订单状态付款状态：(//订单状态：1:待支付 2:成功 3:失败 4:待审核)
        switch ($params['PaymentOrderStatusId']) {
            case 2:
                $status = 'paid';
                $this->return = ['code' => 1, 'msg' => ''];
                break;
            case 3:
                $status = 'failed';
                $this->return = ['code' => 0, 'msg' => '代付失败'];
                break;
            case 1:
            case 4:
                $status       = 'pending';
                $this->return = ['code' => 0, 'msg' => '代付处理中'];
                break;
            default:
                $this->return = ['code' => 0, 'msg' => 'error'];
                return;
        }

        $this->re = $this->return;
        $this->updateTransferOrder($this->money, $real_money, $params['TrackingNumber'],//第三方转账编号
            $params['TrackingNumber']['CreatedAt'], $status);
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
            "MB"                                     => "MBS",
            "PBC"                                    => "PBC",
            "PBB"                                    => "PBB",
            "PNB"                                    => "PNB",
            "PSB"                                    => "PSB",
            "PTC"                                    => "PTC",
            "SBC"                                    => "SBC",
            "SSB"                                    => "SSB",
            "United Coconut Planters Bank"           => "UCPB",
            "Wealth Development Bank, Inc."          => "WDB",
            "GrabPay"                                => "GP",
            "Bangko Mabuhay (A Rural Bank), Inc."    => "BM",
            "CTBC"                                   => "CTBC",
            "CBS"                                    => "CBS",
            "CBC"                                    => "CBC",
            "ALLBANK (A Thrift Bank), Inc."          => "AB",
            "BDO Network Bank, Inc."                 => "BNB",
            "Camalig"                                => "CB",
            "Gcash"                                  => "gcash",
            "Cebuana Lhuillier Rural Bank, Inc."     => "CLB",
            "ISLA Bank (A Thrift Bank), Inc."        => "ISLA",
            "Landbank of the Philippines"            => "LBOB",
            "Maybank Philippines, Inc."              => "MBP",
            "Metropolitan Bank and Trust Co"         => "mbt",
            "Omnipay"                                => "OP",
            "Partner Rural Bank (Cotabato), Inc."    => "PRB",
            "Paymaya Philippines, Inc."              => "PMP",
            "ING"                                    => "IB",
            "BPI Direct Banko, Inc., A Savings Bank" => "BK",
            "BPI"                                    => "bpi",
        ];
        return $banks[$this->bankCode];
    }
}
