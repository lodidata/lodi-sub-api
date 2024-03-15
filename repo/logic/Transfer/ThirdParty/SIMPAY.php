<?php

namespace Logic\Transfer\ThirdParty;

use Logic\Recharge\Recharge;
use Utils\Curl;

/**
 *
 * simpay代付
 */
class SIMPAY extends BASES {
    private $httpCode = '';
    private $sign;
    private $time;

    //回调，不处理逻辑
    public function callbackMsg() {
        return 'success';
    }

    //请求代付接口
    public function runTransfer() {
        $data          = [
            'merorder'       => $this->orderID,
            'merchantid'     => $this->partnerID,
            'command'        => 'PHPA',
            'datasets'       => $this->bankUserName.'|'.$this->mobile.'|'.$this->bankCard.'|GCASH',
            'price'          => $this->money,
            'backurl'        => $this->payCallbackDomain . '/thirdAdvance/callback/simpay',
            'notes'          => 'test',
            'key'            => $this->key,
        ];

        //判断 额外参数是否为空 Determine whether the extra parameter is empty
        $this->payUrl .= '/gateway.php';

        $this->initParam($data,'paid');
        $this->basePostNew();
        $result  = isset($this->re) ? json_decode($this->re, true) : '';
        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['reason']) ? $result['reason'] : '';
        //true成功,false失败
        if($code == 'success') {
            $this->return['code']    = 10500;
            $this->return['balance'] = $this->money;
            $this->return['msg']     = $message;
            //成功就直接返回了
            return;
        } else {
            $message = 'http_code:' . $this->httpCode . 'errorMsg:' . json_encode($result, JSON_UNESCAPED_UNICODE);
            $this->updateTransferOrder($this->money, 0, '', '', 'failed', null, $message);
            $this->return['code']    = 886;
            $this->return['balance'] = 0;
            $this->return['msg']     = 'SIMPAY:' . $message ?? '代付失败';
            return;
        }

        $this->return['code']    = 886;
        $this->return['balance'] = $this->money;
        $this->return['msg']     = $message;
        $this->transferNo        = '';//第三方订单号
    }

    //查询余额
    public function getThirdBalance() {
        $params        = [
            'merchantid'  => $this->partnerID,
            'command'     => 'PHPA',
        ];
        $this->payUrl .= "/gateway.php";
        $this->initParam($params,'querymoney');
        $this->basePostNew();
        $result  = json_decode($this->re, true);
        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['reason']) ? $result['reason'] : 'errorMsg:' . (string)$this->re;
        if($code == 'success') {
                $info  = json_decode($result['result'], true);
                $this->return['code']    = 10500;
                $this->return['balance'] = $info['price'];
                $this->return['msg']     = $message;
                return;
        }
        $this->_end();
        throw new \Exception('http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message);
    }

    //代付订单查询
    public function getTransferResult() {

        $params       = [
            'merorder' => $this->orderID
        ];

        $this->payUrl .= '/gateway.php';
        $this->initParam($params,'querypaid');
        $this->basePostNew();

        $result  = json_decode($this->re, true);
        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)'';
        if($code == 'success') {
                $resultData = json_decode($result['result'],true);

            //订单状态： status Y代表成功，N代表申请中，Z代表失败
                if($resultData['ordstate'] == '1') {
                    $status       = 'paid';
                    $this->return = ['code' => 1, 'msg' => $message];
                } elseif($resultData['ordstate'] == '2') {
                    $status       = 'failed';
                    $this->return = ['code' => 0, 'msg' => $message];
                } elseif($resultData['ordstate'] == '0') {
                    $status       = "pending";
                    $this->return = ['code' => 0, 'msg' => $message];
                } else {
                    $this->return = ['code' => 0, 'msg' => $message];
                    return;
                }
                
                $real_money = bcmul($resultData['price'], 100);
                $fee        = $this->money - $real_money;
                $this->updateTransferOrder($this->money, $real_money, $resultData['merorder'], '', $status, $fee);
                return;
        }
        $this->return = ['code' => 886, 'transfer' => '', 'msg' => 'http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message];
    }

    //组装数组
    public function initParam($params = [],$action) {

        $params['sign']    = $this->sign($params);
        $info = array(
            'merchantid'=> $this->partnerID,
            'action'=> $action,
            'body'=> $this->en3desBoy($params),
        );
        $this->parameter = $info;
    }

    function sign($data)
    {
        $data['key'] =    $this->key;
        //生成签名
        ksort($data);
        $verify = '';
        foreach($data as $x=>$x_value){
            $verify = $verify . $x_value;
        }
        return strtolower(md5($verify));
    }

    //回调校验签名
    public function signVerify($data) {

        $body   = $this->de3des($data,$this->pubKey);
        if (!$body) return false;
        $debody = $this->stringToArray($body);
        return $debody;
    }
    public function stringToArray($str) {
        if (is_string($str)) {
            parse_str($str,$strArray);
            $arr = $strArray;
        } else {
            $arr = $str;
        }
        return $arr;
    }

    public function basePostNew($referer = null) {

        $this->payRequestUrl = $this->payUrl;
        $params_data = json_encode($this->parameter, JSON_UNESCAPED_UNICODE);
        $ch          = curl_init();
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
        if($referer) {
            curl_setopt($ch, CURLOPT_REFERER, $referer);
        }
        $response        = curl_exec($ch);
        $this->curlError = curl_error($ch);
        $this->httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->re        = $response;
        curl_close($ch);
    }



    /**
     * 代付异步回调
     * @param array $params 异步参数
     * @return mixed
     */
    public function callbackResult($params) {
        $this->parameter = $params;

        //检验状态
        if (!$this->public_key_decrypt($params)) {
            throw new \Exception('sign is wrong');
        }

        //订单状态已经成功 或者 消息与订单状态一致
        if($this->order['status'] == 'paid') {
            return;
        }
        $amount     = bcmul($params['price'], 100);//以分为单位
        $real_money = $amount;//实际到账金额

        //订单状态付款状态：(//订单状态：Failed 失败Payed 成功 OverTime超时 Canceled 订单取消)v
        if($this->parameter['ordstate'] == '1') {
            $status       = 'paid';
            $this->return = ['code' => 1, 'msg' => ''];
        } elseif($this->parameter['ordstate'] == '2') {
            $status       = 'failed';
            $this->return = ['code' => 0, 'msg' => '代付失败'];
        } else {
            $this->return = ['code' => 0, 'msg' => 'error'];
            return;
        }

        $this->re = $this->return;
        $this->updateTransferOrder($this->money, $real_money, $params['merorder'],//第三方转账编号
            '', $status);
    }
    public function public_key_decrypt($params)
    {
        // 判断SIGN
//        $signarr = array(
//            'merchantid'    => $params['merchantid'],
//            'ordnum'        => $params['ordnum'],
//            'merorder'      => $params['merorder'],
//            'command'       => $params['command'],
//            'currency'      => $params['currency'],
//            'price'         => $params['price'],
//            'fees'          => $params['fees'],
//            'ordstate'      => $params['ordstate'],
//            'notes'         => $params['notes'],
//            'key'           => $this->key,
//        );
        $signarr = $params;
        unset($signarr['sign']);
        $signarr['key'] = $this->key;
        // 数组升序
        ksort($signarr);
        // 循环取值
        $verify = '';
        foreach($signarr as $x=>$x_value){
            $verify = $verify . $x_value;
        }

        $verify = md5($verify);
        // 强制大写
        $verify = strtoupper($verify);
        $sign   = strtoupper($params['sign']);
        // 判断SIGN
        if($sign != $verify){
            return false;

        }else{
            return true;
        }
    }
    /**
     * 银行名称
     * @return mixed
     */
    private function getBankName() {

        $banks = [
            "Gcash" => "GCASH",
        ];
        return $banks[$this->bankCode];
    }
    //加密
    public function en3des($value){
        $result = openssl_encrypt($value, 'DES-EDE3', $this->pubKey, OPENSSL_RAW_DATA);
        $result = bin2hex($result);
        return $result;
    }
    //加密
    public function en3desBoy($data){
        $body = array_filter($data);
        ksort($body);
        foreach ($body as $key => $value) {
            $string[] = $key . '=' . $value;
        }
        $body = implode('&', $string);

        return $this->en3des($body);
    }
    //解密
    public function de3des($value,$deskey){
        $result = hex2bin($value);
        $result = openssl_decrypt($value, 'DES-EDE3', $deskey, OPENSSL_RAW_DATA);
        return $result;
    }
}
