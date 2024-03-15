<?php

namespace Logic\Recharge;

/*
 * 第三方支付基础类 @author viva
 *
 *  注意父类与你对象是不相同的，每次父类一个对象，子类一个对象，是两个不同的对象，要注意区别 $this
 *  注意微信公众号数据库的bank_data统一用jsweixin  (微信公众号需要在微信浏览器打开，特殊些)
 */
use Logic\Recharge\Traits\RechargeLog;
use Model\FundsDeposit;
use Utils\Curl;

abstract class Bases{

    public $cacertURL = null;  //证书地址
    public $partnerID;  //商户ID
    public $payUrl;   //支付地址
    public $key;  //私钥
    public $pubKey;  //公钥
    public $orderID;  //订单号
    public $money;  //金额
    public $coin_amount;  //数字货币金额
    public $payType;  // 每个通道的所带的银行参数
    public $returnUrl;   //同步回调地址
    public $notifyUrl;   //异步回调地址
    public $showType;  //返回的类型 code url jump
    public $data;   //存储存过来的整个数组对象
    public $parameter;  //存储请求的参数
    public $re;  //请求url返回的参数
    public $sort = true;  //加密等是否排序
    public $curlError = '';  //请求异常
    public $clientIp;
    public $domain;
    public $userId; //用户ID号
    public $rechargeType = ''; //支付方式
    public $payCallbackDomain = ''; //支付域名配置
    public $payThirdType;//第三方支付类型type

    public $goPayUrl; //用于存储form前端跳转数据的临时支付地址 由buildGoOrderUrl()生成

    public $return = array(
        'code' => 0,     //统一 0 为OK
        'msg'  => '',    //统一 SUCCESS为成功
        'way' =>'',  //返回类型 （取值 code:二维码，url:跳转链接，json：）
        'str' =>'',  //
        'money' =>0,  //
    );

    public function run(array $data){
        $this->payThirdType     = $data['type'];
        $this->partnerID        = $data['partner_id'];
        $this->payUrl           = $data['payurl'];
        $this->key              = $data['key'];
        $this->pubKey           = $data['pub_key'];
        $this->orderID          = $data['order_number'];
        $this->money            = $data['money'];
        $this->coin_amount      = $data['coin_amount'];
        $this->return['money']  = $data['money'];
        $this->payType          = isset($data['bank_code']) ? $data['bank_code'] : '';
        $this->returnUrl        = $data['url_return'];
        $this->notifyUrl        = $data['url_notify'];
        $this->showType         = $data['return_type'];
        $this->clientIp         = $data['client_ip'];
        $this->userId           = $data['user_id'];
        $this->rechargeType     = $data['pay_type'];
        $this->payCallbackDomain = !empty($data['pay_callback_domain']) ? rtrim(trim($data['pay_callback_domain']), '/') : (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'];
        $this->data             = $data;

        //$arr = parse_url($this->notifyUrl);
        //$this->domain = $arr['scheme'] . '://' . $arr['host'] . (isset($arr["port"]) ? ":" . $arr["port"]: "");
        $this->start();
        $this->windUp();  //收尾需要的工作
        //兼容安卓  HTTP大写跳不过
        $this->return['str']    = str_replace(["HTTPS://","HTTP://"], ["https://","http://"], $this->return['str']);
        return $this->return;
    }

    //具体 请求第三方支付，子类必须实现
    abstract public function start();
    //请求验签
    //返回数组
    //[status=1 通过  0不通过,
    //order_number = '订单',
    //'third_order'=第三方订单,
    //'third_money'='金额',
    //'error'='未有该订单/订单未支付/未有该订单']
    /**
     * 异步回调
     * @param $data
     * @return mixed
     */
    abstract public function returnVerify($data);

    /**
     * 补单
     * @param $order_number
     * @return mixed
     */
    abstract public function supplyOrder($order_number);
    //子类统一调用收尾方法，需要收尾共同处理的在些处理
    public function windUp(){
        $this->addStartPayLog();
    }

    // 传输
    public function post(){
        if(is_array($this->parameter))
            $this->re = CURL::post($this->payUrl,$this->cacertURL,$this->parameter);
        else
            $this->re = CURL::commonPost($this->payUrl,$this->cacertURL,$this->parameter);
    }

    public function get(){
        if($this->parameter)
            $this->payUrl .= '?'.$this->arrayToURL();
        $this->re = CURL::get($this->payUrl,$this->cacertURL);
    }
    // http_build_query  URL格式化传输数据
    public function basePost($referer = null){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->payUrl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);          //单位 秒，也可以使用
        if(is_array($this->parameter))
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->parameter));
        else
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->parameter);
        if($referer)
            curl_setopt($ch, CURLOPT_REFERER, $referer);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $this->curlError = curl_error($ch);
        curl_close($ch);
        $this->re = $response;
    }

    //curl的json格式传输
    public function payJson2(){
        $data_string = json_encode($this->parameter, JSON_UNESCAPED_UNICODE);
        $data_string = str_replace("\\/", "/", $data_string);//去除转义问题
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->payUrl);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl,CURLOPT_POST,1);
        curl_setopt($curl,CURLOPT_POSTFIELDS,$data_string);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json; charset=utf-8',
                'Content-Length: ' . strlen($data_string)
            )
        );
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($curl);
        $this->curlError = curl_error($curl);
        $this->re = $response;
    }

    //通常MD5加密方法  有些加密需要带key= 有些直接接key
    public function currentMd5(string $key = null){
        $signPars = $this->arrayToURL($this->parameter);
        $signPars .= '&'.$key.$this->key;
        return md5($signPars);
    }

    //直接加密
    public function md5(){
        if($this->sort)
            ksort($this->parameter);
        return md5(implode('',$this->parameter).$this->key);
    }

    //通常回调解密方法
    public function currentVerify(array $data,string $sign) {
        $signPars = $this->arrayToURL($data);
        $signPars .= "&key=" . $this->pubKey;
        $s = strtolower(md5($signPars));

        $sign = strtolower($sign);
        return $sign == $s;
    }
    //私钥加密
    public function currentOpenssl($way = OPENSSL_ALGO_MD5){
        $signPars = $this->arrayToURL();
        openssl_sign($signPars,$sign_info,$this->key,$way);
        return base64_encode($sign_info);
    }
    //私钥解密
    public function privateDecrypt(string $encrypted = null,string $pi_key = null,int $length = 128){
        $encrypted = $encrypted ? $encrypted : $this->parameter;
        $pi_key = $pi_key ? $pi_key : $this->key;
        $crypto = '';
        foreach (str_split($encrypted, $length) as $chunk) {

            openssl_private_decrypt($chunk, $decryptData, $pi_key);

            $crypto .= $decryptData;

        }
        return $crypto;
    }
    //解码函数
    public function urlsafe_b64decode(string $string) {
        $data = str_replace(array('-','_'),array('+','/'),$string);
        $mod4 = strlen($data) % 4;
        if ($mod4) {
            $data .= substr('====', $mod4);
        }
        return base64_decode($data);
    }
    //公钥加密
    public function pukOpenssl(int $length=117){
        if(is_array($this->parameter))
            $this->parameter = json_encode($this->parameter);
        $encryptData = '';
        $crypto = '';
        foreach (str_split($this->parameter, 117) as $chunk) {
            openssl_public_encrypt($chunk, $encryptData, $this->pubKey);
            $crypto = $crypto . $encryptData;
        }

        $crypto = base64_encode($crypto);
        return $crypto;
    }
    //公钥解密
    public function pukDecrypt(string $encrypted = null,string $pu_key = null,$code = null,int $length = 128){

        $encrypted  = $encrypted ? $encrypted : $this->parameter;
        $encrypted  = $this->decode($encrypted,$code);
        $pu_key     = $pu_key ? $pu_key : $this->pubKey;
        $crypto     = '';

        foreach (str_split($encrypted, $length) as $chunk) {
            openssl_public_decrypt( $chunk,$decrypted, $pu_key);
            $crypto .= $decrypted;

        }
        return $crypto;
    }

    public function decode($data, $code) {
        switch (strtolower ( $code )) {
            case 'base64' :
                $data = base64_decode ( $data );
                break;
            case 'hex' :
                $data = $this->hex2bin ( $data );
                break;
            case 'bin' :
            default :
        }
        return $data;
    }
    public function hex2bin($hex = false) {
        $ret = $hex !== false && preg_match ( '/^[0-9a-fA-F]+$/i', $hex ) ? pack ( "H*", $hex ) : false;
        return $ret;
    }
    //二进制MD5加密
    public function HmacMd5($data = [],$key = null) {
        $data   = $data ? $data : $this->parameter ;
        $key    = $key ? $key : $this->key ;
        ksort($data);
        $str    = implode('',$data);
        $b      = 64; // byte length for md5
        if (strlen($key) > $b) {
            $key = pack("H*",md5($key));
        }
        $key    = str_pad($key, $b, chr(0x00));
        $ipad   = str_pad('', $b, chr(0x36));
        $opad   = str_pad('', $b, chr(0x5c));
        $k_ipad = $key ^ $ipad ;
        $k_opad = $key ^ $opad;

        $sign = md5($k_opad . pack("H*",md5($k_ipad . $str)));

        return $sign;
    }

    public function arrayToURL() {
        $signPars = "";
        if($this->sort)
            ksort($this->parameter);
        foreach($this->parameter as $k => $v) {
            //  字符串0  和 0 全过滤了，所以加上
            if(!empty($v) || $v === 0  || $v === "0" ) {
                $signPars .= $k . "=" . $v . "&";
            }
        }
        $signPars = rtrim($signPars,'&');
        return $signPars;
    }

    public function arrayToURLALL(array $array) {
        $signPars = "";
        if($this->sort)
            ksort($array);
        foreach($array as $k => $v) {
            if(is_array($v)){
                $signPars .= $this->arrayToURLALL($v).'& &';
            }else {
                //  字符串0  和 0 全过滤了，所以加上
                if (!empty($v) || $v === 0 || $v === "0") {
                    $signPars .= $k . "=" . $v . "&";
                }
            }
        }
        $signPars = rtrim($signPars,'&');
        return $signPars;
    }

    /**
     * 将数据转为XML
     */
    public function toXml(array $array){
        $xml = '<xml>';
        forEach($array as $k=>$v){
            $xml.='<'.$k.'><![CDATA['.$v.']]></'.$k.'>';
        }
        $xml.='</xml>';
        return $xml;
    }
    /**
     * XML解析成数组
     */
    public function parseXML($xmlSrc){
        if(empty($xmlSrc)){
            return false;
        }
        $array = array();
        $xml = simplexml_load_string($xmlSrc);
        $encode = $this->getXmlEncode($xmlSrc);
        if($xml && $xml->children()) {
            foreach ($xml->children() as $node){
                //有子节点
                if($node->children()) {
                    $k = $node->getName();
                    $nodeXml = $node->asXML();
                    $v = $this->parseXML($nodeXml);
                } else {
                    $k = $node->getName();
                    $v = (string)$node;
                }
                if($encode!="" && strpos($encode,"UTF-8") === FALSE ) {
                    $k = iconv("UTF-8", $encode, $k);
                    $v = iconv("UTF-8", $encode, $v);
                }
                $array[$k] = $v;
            }
        }
        return $array;
    }

    //获取xml编码
    public function getXmlEncode($xml) {
        $ret = preg_match ("/<?xml[^>]* encoding=\"(.*)\"[^>]* ?>/i", $xml, $arr);
        if($ret) {
            return strtoupper ( $arr[1] );
        } else {
            return "";
        }
    }

    public function formatPrivateKey(string $str = '',bool $set = true) {
        $str = $str ? $str : $this->key;
        $private_key = "-----BEGIN RSA PRIVATE KEY-----\r\n";
        foreach (str_split($str, 64) as $val) {
            $private_key = $private_key . $val . "\r\n";
        }
        $private_key = $private_key . "-----END RSA PRIVATE KEY-----";
        if($set) $this->key = $private_key;
        return $private_key;
    }

    public function formatPublicKey(string $str = '',bool $set = true) {
        $str = $str ? $str : $this->pubKey;
        $public_key = "-----BEGIN PUBLIC KEY-----\r\n";
        foreach (str_split($str, 64) as $val) {
            $public_key = $public_key . $val . "\r\n";
        }
        $public_key = $public_key . "-----END PUBLIC KEY-----";
        if($set) $this->pubKey = $public_key;
        return $public_key;
    }

    //发起第三方支付日志
    public function addStartPayLog(){
        if($this->re==null)
            $this->re = $this->return;
        if($this->re) {
            if (is_string($this->re)) {
                if($this->is_json($this->re)){
                    $this->re = json_decode($this->re, true);
                    if(isset($this->re['params']) && $this->is_json($this->re['params'])){
                        $this->re['params'] = json_decode($this->re['params'], true);
                    }
                    $this->re = json_encode($this->re, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
                }
                $re = $this->re;
            }else if (is_array($this->re)) {
                $re = json_encode($this->re);
            }else {
                $re = $this->parseXML(json_encode($this->re));
            }

            if(!is_null($this->parameter) && isset($this->parameter['params']) && $this->is_json($this->parameter['params'])){
                $this->parameter['params'] = json_decode($this->parameter['params'], true);
            }
            $data['pay_type'] = $this->payThirdType;
            $data['pay_url'] = $this->payUrl??'';
            $data['order_number'] = $this->orderID;
            $data['json'] = json_encode($this->parameter,true);
            $data['response'] = $re;
            $data['date'] = date('Y-m-d H:i:s');
            Recharge::addLog($data,'pay_request_third_log');
        }
    }

    public function buildGoOrderUrl($method='post'){
        $data = array(
            'method' => $method,
            'url' => $this->payUrl,
            'data' => $this->parameter,
        );

        $id = md5(time()+$this->orderID);
        $this->goPayUrl = $this->domain.'/'.CUSTOMER.'/pay?id='.$id;
        global $app;
        $redis = $app->getContainer()->redis;
        $redis->setex('pay_request_data_'.$id, 60, json_encode($data));
    }

    public function logCurlFunc($order_number, $fn){
        $ret = $fn; //调用传入函数
        $data = [
            'pay_type' => $this->payThirdType,
            'order_number' => $order_number,
            'pay_url' => $this->payUrl,
            'parameter' => $this->arrayToURL(),//$this->parameter
            'response' => $this->re,
            'date'=>date('Y-m-d H:i:s'),
        ];
        //记录curl 请求日志
        RechargeLog::addLogByTxt($data,'pay_log_callback');
        return $ret;
    }

    function is_json($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    //修改订单金额
    public function updateOrderMoney($order_no, $money){
        return FundsDeposit::where('trade_no', '=', $order_no)
            ->update(['money' => $money]);
    }
}