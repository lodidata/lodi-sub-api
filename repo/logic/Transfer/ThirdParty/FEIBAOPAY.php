<?php

namespace Logic\Transfer\ThirdParty;

use Logic\Recharge\Recharge;

/**
 *
 * FEIBAOPAY代付
 */
class FEIBAOPAY extends BASES
{
    private $httpCode = '';

    //回调，不处理逻辑
    public function callbackMsg(){
        return 'success';
    }

    //请求代付接口
    public function runTransfer()
    {
        /*if($this->money % 100){
            throw new \Exception('Transfer only supports integer');
        }*/
        list($usec, $sec) = explode(" ", microtime());
        $time = ((float)$usec + (float)$sec);
        $params = array(
            'gateway'               => 'gcash',
            'merchant_order_num'    => $this->orderID,
            'uid'                   => '12345678',
            'amount'                => bcdiv($this->money, 100,2),
            'callback_url'          => $this->payCallbackDomain . '/thirdAdvance/callback/feibaopay',
            'merchant_order_time'   => $time,
            'merchant_order_remark' => 'withdraw',
            'user_ip'               => \Utils\Client::getIp(),
            'bank_code'             => $this->getBankName(),
            'card_number'           => $this->bankCard,
            'card_holder'           => $this->bankUserName,
            'province_code'         => 'hebei',
            'city_code'             => '071000',
            'area_code'             => '071300',
        );

        $this->payUrl   .= '/v3/withdraw';
        $this->initParam($params);

        $this->basePostNew();
        $result  = json_decode($this->re, true);
        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:'.(string)$this->re;

        if ($this->httpCode == 200) {
            if($code == 0){
                $order = $this->des3Decrypt($result['order'],$this->key,$this->pubKey);
                $order = json_decode($order, true);
                $this->return['code']           = 10500;
                $this->return['balance']        = bcmul($order['amount'], 100);
                $this->return['msg']            = $message;
                $this->transferNo               = $order['merchant_order_num'];//第三方订单号
                return;
            }else{
                $message = "curlError:{$this->curlError},http_code:{$this->httpCode},errorMsg:". json_encode($result, JSON_UNESCAPED_UNICODE);
                $this->updateTransferOrder($this->money, 0, '', '', 'failed', null, $message);
                $this->return['code']    = 886;
                $this->return['balance'] = 0;
                $this->return['msg']     = '88PAY代付:' . $message ?? '代付失败';
                return;
            }

        }
        throw new \Exception('http_code:'.$this->httpCode.' code:'.$code.' message:'.$message);
    }


    //查询余额
    public function getThirdBalance()
    {
        list($usec, $sec) = explode(" ", microtime());
        $time = ((float)$usec + (float)$sec);
        $params = [
            'gateways'      => ['gcash'],
            'merchant_time' => $time,
        ];

        $this->payUrl .= "/v3/balance";
        $this->initParam($params);
        $this->basePostNew ();

        $result  = json_decode($this->re, true);
        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:'.(string)$this->re;

        if ($this->httpCode == 200) {
            if($code == 0){
                $this->return['code']    = 10509;
                $this->return['balance'] = bcmul($result['wallets']['gcash'], 100);
                $this->return['msg']     = $message;
                //成功就直接返回了
                return;
            }

        }

        $this->_end();
        throw new \Exception('http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message);
    }

    //查询代付结果
    public function getTransferResult()
    {
        $params = [
            'merchant_slug'       => $this->partnerID,
            'merchant_order_num'  => $this->orderID,
        ];

        $this->payUrl .= "/v3/check_withdraw";
        $this->initParam($params);
        $this->basePostNew();

        $result     = json_decode($this->re, true);
        $code       = isset($result['code']) ? $result['code'] : 1;
        $message    = isset($result['msg']) ? $result['msg'] : 'errorMsg:'.(string)$this->re;

        if ($this->httpCode == 200) {
            if($code == 0){
                $order = $this->des3Decrypt($result['order'],$this->key,$this->pubKey);
                $order = json_decode($order, true);
                if($this->return_sign($order) != $order['sign']){
                    throw new \Exception('Sign error');
                }
                //订单状态 pay_status (1、 付款中 2、 付款失败 3、 付款成功)
                if($order['status'] == 'success'){
                    $status = 'paid';
                    $message = '代付成功';
                    $this->return = ['code' => 1,  'msg' => $message];
                }elseif($order['status'] == 'fail'){
                    $status = 'failed';
                    $message = '代付失败';
                    $this->return = ['code' => 0,  'msg' => $message];
                }else{
                    $message = $order['status'];
                    $this->return = ['code' => 0,  'msg' => $message];
                    return;
                }
                $real_money = bcmul($order['amount'], 100);
                $fee        = $this->money - $real_money;
                $this->updateTransferOrder(
                    $this->money,
                    $real_money,
                    $order['merchant_order_num'],//第三方转账编号
                    '',
                    $status,
                    $fee,
                    $message
                );
                return;
            }
        }
        $this->return = ['code' => 886, 'transfer' => '', 'msg' => 'http_code:'.$this->httpCode.' code:'.$code.' message:'.$message];

    }

    //组装数组
    public function initParam($params=[])
    {
        $data = $params;
        $data['sign']           = $this->sign($data);  //校验码
        $this->parameter = $data;
    }

    /**
     * 获取代付平台 的银行code
     */

    //生成签名
    public function sign($data) {
        unset($data['sign']);
        ksort($data);

        $sign = sha1(json_encode($data));
        return $sign;
    }

    //生成签名
    public function return_sign($data) {
        unset($data['sign']);
        ksort($data);

        $sign = sha1(json_encode($data, JSON_UNESCAPED_SLASHES));
        return $sign;
    }


    public function basePostNew($referer = null)
    {
        $ch = curl_init();
        $params_body = $this->getParamsBody($this->parameter, $this->key, $this->pubKey);
        $params_data = [
            'merchant_slug' => $this->partnerID,
            'data'  => $params_body
        ];
        $params_data = json_encode($params_data, JSON_UNESCAPED_UNICODE);
        curl_setopt($ch, CURLOPT_URL, $this->payUrl);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params_data);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        $response = curl_exec($ch);
        $this->httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->re = $response;

    }

    function getParamsBody($data, $key, $vi){
        $res = $this->des3Encrypt(json_encode($data),$key, $vi);
        return $res;
    }

    /**
     * 加密
     * @param $str
     * @param string $des_key
     * @param string $des_iv
     * @return string
     */
    function des3Encrypt($str, $des_key="", $des_iv = '')
    {
        $res = base64_encode(openssl_encrypt($str, 'AES-256-CBC', $des_key, OPENSSL_RAW_DATA, $des_iv));
        return $res;
    }

    /**
     * 解密
     * @param $str
     * @param string $des_key
     * @param string $des_iv
     * @return false|string
     */
    function des3Decrypt($str, $des_key="", $des_iv= '')
    {
        $str = base64_decode($str);
        $res = openssl_decrypt($str, 'AES-256-CBC', $des_key, OPENSSL_RAW_DATA, $des_iv);
        return $res;
    }

    /**
     * 代付异步回调
     * @param array $params 异步参数
     * @return mixed
     */
    public function callbackResult($params)
    {
        $this->parameter  = $params;
        $config    = Recharge::getThirdConfig('feibaopay');
        $this->key = $config['key'];
        $this->pubKey = $config['pub_key'];

        if($params['code'] != 0){
            throw new \Exception('code error');
        }

        $order = $this->des3Decrypt($params['order'],$this->key,$this->pubKey);
        $order = json_decode($order, true);
        if($this->return_sign($order) != $order['sign']){
            throw new \Exception('Sign error');
        }

        //订单状态已经成功 或者 消息与订单状态一致
        if($this->order['status'] == 'paid') {
            return;
        }
        $amount     = bcmul($order['amount'], 100);//以分为单位
        $real_money = $amount;//实际到账金额

        //订单状态付款状态：(//订单状态：0 处理中 1 成功 2 失败)
        if($order['status'] == 'success') {
            $status       = 'paid';
            $this->return = ['code' => 1, 'msg' => ''];
        } elseif($order['status'] == 'waiting' || $order['status'] == 'processing') {
            $status       = 'pending';
            $this->return = ['code' => 0, 'msg' => '代付处理中'];
        } elseif($order['status'] == 'fail') {
            $status       = 'failed';
            $this->return = ['code' => 0, 'msg' => '代付失败'];
        } else {
            $this->return = ['code' => 0, 'msg' => 'error'];
            return;
        }

        $this->re = $this->return;
        $this->updateTransferOrder($this->money, $real_money, $order['merchant_order_num'],//第三方转账编号
            '', $status);
    }

    /**
     * 银行名称
     * @return mixed
     */
    private function getBankName()
    {
        $banks = [
            "Starpay"                              => "STARP",
            "PNB"                                  => "PNBMP",
            "GrabPay"                              => "GRABPAY",
            "Camalig"                              => "RUCAP",
            "Gcash"                                => "GCASH",
            "Maybank Philippines, Inc."            => "MBBEP",
            "Omnipay"                              => "OMNIP",
            "Partner Rural Bank (Cotabato), Inc."  => "PRTOP",
            "Paymaya Philippines, Inc."            => "PAYMP",
            "BPI"                                  => "BPI",
            "Banco De Oro Unibank, Inc."           => "BNORP",
            "BDO Network Bank, Inc."               => "BDONP",
            "AUB"                                  => "AUBKP",
            "EB"                                   => "EWBCP",
            "ESB"                                  => "EQSNP",
            "MB"                                   => "MAARP",
            "PB"                                   => "PSCOP",
            "PBC"                                  => "CPHIP",
            "PBB"                                  => "PPBUP",
            "PSB"                                  => "PHSBP",
            "PTC"                                  => "PHTBP",
            "PVB"                                  => "PHVBP",
            "RBG"                                  => "RUGUP",
            "Rizal Commercial Banking Corporation" => "RCBC",
            "RB"                                   => "ROBPP",
            "SBC"                                  => "SETCP",
            "SBA"                                  => "STLAP",
            "Queen City Development Bank, Inc."    => "QCDFP",
            "United Coconut Planters Bank"         => "UCPBP",
            "Wealth Development Bank, Inc."        => "WEDVP",
            "Yuanta Savings Bank, Inc."            => "TYBKP",
            "BOC"                                  => "PABIP",
            "CTBC"                                 => "CTCBP",
            "Chinabank"                            => "CHBKP",
            "CBS"                                  => "CHSVP",
            "ALLBANK (A Thrift Bank), Inc."        => "ALKBP",
            "DBI"                                  => "DUMTP",
            "Cebuana Lhuillier Rural Bank, Inc."   => "CELRP",
            "Landbank of the Philippines"          => "TLBPP",
            "Metropolitan Bank and Trust Co"       => "MBTCP",
            "ING"                                  => "INGBP",
            "UBPHPH"                               => "UBP",
        ];
        return $banks[$this->bankCode];
    }
}
