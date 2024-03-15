<?php

namespace Logic\Transfer\ThirdParty;

use Logic\Recharge\Recharge;

/**
 *
 * NN88PAY代付
 */
class NN88PAY extends BASES
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

        $params = array(
            'business_type'     => '30001', //
            'mer_order_no'      => $this->orderID, //是	string	商户订单号 Merchant order number
            'order_price'       => bcdiv($this->money, 100,2),//代付金额 (单位：฿，不支持小数)
            'bank_id'           => 'Gcash',  //string 收款银行编号
            'account_no'        => $this->bankCard, // string 收款银行账号 (示例：6227888888888888)
            'account_name'      => $this->bankUserName, //收款人姓名 (示例：张三)
            'notify_url'        => $this->payCallbackDomain . '/thirdAdvance/callback/nn88pay',  //string 异步回调地址 (当代付完成时，平台将向此URL地址发送异步通知。建议使用 https)，
        );

        //判断 额外参数是否为空 Determine whether the extra parameter is empty
        $this->initParam($params);

        $this->basePostNew();
        $result  = json_decode($this->re, true);
        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:'.(string)$this->re;

        if ($this->httpCode == 200) {
            if($code == 0){
                $this->return['code']           = 10500;
                $this->return['balance']        = bcmul($result['order_price'], 100);
                $this->return['msg']            = $message;
                $this->transferNo               = $result['order_no'];//第三方订单号
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
        $params = [
            'business_type' => '30004',
        ];

        $this->initParam($params);
        $this->basePostNew ();

        $result  = json_decode($this->re, true);
        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:'.(string)$this->re;

        if ($this->httpCode == 200) {
            if($code == 0){
                $this->return['code']    = 10509;
                $this->return['balance'] = bcmul($result['account_price'], 100);
                $this->return['msg']     = $message;
                //成功就直接返回了
                return;
            }

        }

        $message = 'http_code:'.$this->httpCode.'errorMsg:'.json_encode($result, JSON_UNESCAPED_UNICODE);
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
        $this->return['msg'] = 'NN88PAY:' . $message ?? '代付失败';
    }

    //查询代付结果
    public function getTransferResult()
    {
        $params = [
            'business_type' => '30002',
            'mer_order_no'  => $this->orderID,
        ];

        $this->initParam($params);
        $this->basePostNew();

        $result     = json_decode($this->re, true);
        $code       = isset($result['code']) ? $result['code'] : 1;
        $message    = isset($result['msg']) ? $result['msg'] : 'errorMsg:'.(string)$this->re;
        $result['order_price'] = bcmul($result['order_price'],1,2);

        if ($this->httpCode == 200) {
            if($code == 0){
                if($this->sign($result) != $result['sign']){
                    throw new \Exception('Sign error');
                }
                //订单状态 pay_status (1、 付款中 2、 付款失败 3、 付款成功)
                if($result['pay_status'] == 3){
                    $status = 'paid';
                    $this->return = ['code' => 1,  'msg' => $message];

                }elseif($result['pay_status'] == 2){
                    $status = 'failed';
                    $this->return = ['code' => 0,  'msg' => $message];
                }elseif($result['pay_status'] == 1){
                    $this->return = ['code' => 0,  'msg' => $message];
                    return;
                }else{
                    $this->return = ['code' => 0,  'msg' => $message];
                    return;
                }
                
                $real_money = bcmul($result['order_price'], 100);
                $fee        = $this->money - $real_money;
                $this->updateTransferOrder(
                    $this->money,
                    $real_money,
                    $result['order_no'],//第三方转账编号
                    '',
                    $status,
                    $fee,
                    $message
                );
                return;
            }elseif($code == 1002){
                //查不到订单
                $this->updateTransferOrder($this->money, 0, '', '', 'failed', 0, $message);
                return;
            }

        }
        $this->return = ['code' => 886, 'transfer' => '', 'msg' => 'http_code:'.$this->httpCode.' code:'.$code.' message:'.$message];

    }

    //组装数组
    public function initParam($params=[])
    {
        //请求参数 Request parameter
        $data = array(
            'timestamp'         => time(),//	是	integer	发送请求的 10 位时间戳 10-bit timestamp of sending request
        );

        $params && $data = array_merge($data, $params);
        $data['sign']           = $this->sign($data);  //校验码
        $this->parameter = $data;
    }

    /**
     * 获取代付平台 的银行code
     */

    //生成签名
    public function sign($data)
    {
        unset($data['sign']);
        ksort($data);
        reset($data);

        $str = '';
        foreach ($data as $k => $v){
            if(is_null($v) || $v === '') continue;
            $str .= $k.'='.$v.'&';
        }
        $str = trim($str, '&');

        $sign_str       = $str .'&key='. $this->key;
        $sign           = strtolower(md5($sign_str));
        return $sign;
    }


    public function basePostNew($referer = null)
    {
        $this->payRequestUrl = $this->payUrl;
        $params_body = $this->getParamsBody($this->parameter, $this->key);
        $params_data = [
            'mcode' => $this->partnerID,
            'body'  => $params_body
        ];
        $params_data = json_encode($params_data, JSON_UNESCAPED_UNICODE);
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
        $this->httpCode  = curl_getinfo($ch,CURLINFO_HTTP_CODE);
        $this->re        = $response;
        curl_close($ch);

    }

    function getParamsBody($data, $key){
        $res = $this->des3Encrypt(json_encode($data),$key);
        return base64_encode($res);
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
        $des_iv = substr($des_key,0,8);
        $res = base64_encode(openssl_encrypt($str, 'des-ede3-cbc', $des_key, OPENSSL_RAW_DATA, $des_iv));
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
        $des_iv = substr($des_key,0,8);
        return openssl_decrypt(base64_decode($str), 'des-ede3-cbc', $des_key, OPENSSL_RAW_DATA, $des_iv);
    }

    /**
     * 代付异步回调
     * @param array $params 异步参数
     * @return mixed
     */
    public function callbackResult($params)
    {
        $this->parameter  = $params;
        $config    = Recharge::getThirdConfig('nn88pay');
        $this->key = $config['key'];

        $field = [
            'order_no'          => $params['order_no'],
            'sign'              => $params['sign'],
            'order_price'       => bcmul($params['order_price'],1,2),
            'notify_status'     => $params['notify_status'],
            'notify_url'        => $params['notify_url'],
            'account_no'        => $params['account_no'],
            'pay_time'          => $params['pay_time'],
            'pay_status'        => $params['pay_status'],
            'bank_id'           => $params['bank_id'],
            'business_type'     => $params['business_type'],
            'account_name'      => $params['account_name'],
            'mer_order_no'      => $params['mer_order_no'],
            'timestamp'         => $params['timestamp'],
        ];

        if($this->sign($field) != $params['sign']){
            throw new \Exception('Sign error');
        }
        if($params['business_type'] != '30003'){
            throw new \Exception("代付回调business_type error:{$params['business_type']}");
        }

        //订单状态已经成功 或者 消息与订单状态一致
        if($this->order['status'] == 'paid'){
            return;
        }

        $amount     = bcmul($field['order_price'] , 100);//以分为单位
        $real_money = $amount;//实际到账金额

        //订单状态付款状态：(1、 付款中 2、 付款失败 3、 付款成功)
        if($field['pay_status'] == 3){
            $status = 'paid';
            $this->return = ['code' => 1,  'msg' => ''];
        }else{
            $this->return = ['code' => 0, 'msg' => $field['msg']];
            return;
        }

        $this->re = $this->return;
        $this->updateTransferOrder(
            $this->money,
            $real_money,
            $field['order_no'],//第三方转账编号
            '',
            $status,
            0,
            $field['msg']
        );
    }

    /**
     * 银行名称
     * @return mixed
     */
    private function getBankName()
    {
        $banks = [
            "KBANK" => "Kasikornbank",
            "BBL" => "BANGKOK BANK",
            "BAAC" => "Bank for Agriculture and Agricultural Cooperatives",
            "BAY" => "Bank of Ayudhya",
            "BOC" => "Bank of China (Thai)",
            "CIMB" => "CIMB Thai Bank",
            "CITI" => "Citibank Thailand",
            "GHB" => "Government Housing Bank",
            "ICBC" => "ICBC Bank",
            "TIBT" => "Islamic Bank of Thailand",
            "KKB" => "KIATNAKIN BANK",
            "KTB" => "Krung Thai Bank",
            "LHBA" => "LH Bank",
            "SCBT" => "Standard Chartered",
            "SMTB" => "Sumitomo Mitsui Trust Bank",
            "TTB" => "TMBThanachart Bank",
            "GSB" => "Government Savings Bank",
            "SCB" => "The Siam Commercial Bank",
            "TCRB" => "Thai Credit Retail Bank",
            "TISCO" => "TISCO Bank",
            "UOB" => "United Overseas Bank"
        ];
        return $banks[$this->bankCode];
    }
}
