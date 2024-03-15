<?php

namespace Logic\Transfer\ThirdParty;

/*
 * 第三方代付基础类 @author viva
 *
 */

use Utils\Curl;

abstract class BASES {
    public $cacertURL;
    public $partnerID;  //商户ID
    public $mobile;  //用户手机号
    public $bankCode;  //银行代号
    public $bankName; //银行名称
    public $bankUserName;  //银行卡户名
    public $bankCard;  //银行卡号
    public $fee;  //收费方式//0转账金额扣除，1余额扣除
    public $type;  //转账方式//0普通，1加急
    public $returnUrl;   //同步回调地址
    public $notifyUrl;   //同步回调地址
    public $payUrl;   //支付地址
    public $key;  //私钥
    public $pubKey;  //公钥
    public $orderID;  //订单号
    public $transferNo;  //第三方订单号
    public $money;  //金额
    public $re;  //请求url返回的参数
    public $parameter;  //存储请求的参数
    public $app_secret;  //平台密钥
    public $sort = true;  //
    public $thirdConfig;  //
    public $order;  //
    public $payCallbackDomain;//代付回调域名
    public $payRequestUrl;//支付请求地址 用于log日志

    private static $logDir =  LOG_PATH.'/pay';

    public $jumpURL = 'http://ppp.cdweijunte.com/go.php';  //存储请求的参数
    //请求代付时返回的格式
    public $return = [
        'code'    => 886,     // 第三方错误代码统一为 886   10508统一为转账成功   10500  申请第三方转账成功  10509 查询成功 .....
        'balance' => 0,    //金额
        'msg'     => 'error',    //统一 SUCCESS为成功
    ];

    public function init($config, $order = null, $insert = false) {
        $this->partnerID    = $config['partner_id'];  //商户ID
        $this->returnUrl    = $config['url_return'];   //同步回调地址
        $this->notifyUrl    = $config['url_notify'];   //同步回调地址
        $this->payUrl       = $config['request_url'];   //支付地址
        $this->key          = $config['key'];  //私钥
        $this->pubKey       = $config['pub_key'];  //公钥
        $this->app_secret   = $config['app_secret'];  //平台密钥
        $this->payCallbackDomain = !empty($config['pay_callback_domain']) ? rtrim(trim($config['pay_callback_domain']), '/') : (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'];
        $this->thirdConfig  = $config;  //金额

        if ($order) {
            $this->order        = $order;  //订单信息
            $this->bankCode     = $order['receive_bank_code'];  //银行代号
            $this->bankName     = $order['receive_bank'];  //银行名称
            $this->bankUserName = $order['receive_user_name'];  //银行卡户名
            $this->bankCard     = $order['receive_bank_card'];  //银行卡号
            $this->fee          = $order['fee_way'];  //收费方式
            $this->type         = $order['tran_type'];  //转账模式
            $this->orderID      = $order['trade_no'];  //订单号
            $this->transferNo   = isset($order['transfer_no']) ? $order['transfer_no'] : '';  //第三方订单号
            $this->money        = $order['money'];  //金额
            $this->mobile       = isset($order['user_mobile'])?$order['user_mobile']:'';
            $this->mobile && $this->mobile = ltrim($this->mobile ,'0');//poppay需要去掉左边的0
            unset($order['user_id'], $order['user_mobile'], $order['user_name']);
            global $app;
            $insert && $app->getContainer()->db->getConnection()
                                               ->table("transfer_order")
                                               ->insert($order);

            if ($order['city_code'] && $order['city_code'] <= 6) {
                $k = 6 - strlen($order['city_code'] . '');  //城市 代号为 6位， 省2位，市2位，具体县2位，
                while ($k--) {
                    $this->order['city_code'] .= '0';
                }
            }
        }
    }

    /*
     * 需要在子类 实现向第三方提交代付申请
     */
    abstract public function runTransfer();

    /*
     * 需要在子类 实现向第三方查询账户余额
     * @return ['code'=>0,'balance'=>88,'msg'=>'' ]  0 查询成功  失败886  msg 错误信息
     */
    abstract public function getThirdBalance();

    /*
     * 需要在子类 实现向第三方查询代付结果
     * @return ['code'=>0,balance=>88,'msg'=>'']  0 查询成功  代付转账成功balance实际到账金额  886查询失败  msg 众失败原因
     * 注：-----   若查询成功，记得更新相应代付订单状态
     */
    abstract public function getTransferResult();

    /**
     * 代付异步回调
     * @param array $params 异步参数
     * @return mixed
     */
    abstract public function callbackResult($params);

    //更新第三方余额 $balance 金额为分
    public function updateBalance($balance) {
        global $app;
        $app->getContainer()->db->getConnection()
                                ->table("transfer_config")
                                ->where('id', $this->thirdConfig['id'])
                                ->update(['balance' => $balance]);
    }

    /*更新转账订单信息
     * @param applyAmount 申请金额
     * @param amount 实际转账金额
     * @param transferNo 第三方转账编号
     * @param transferDate 转账时间
     * @param status 转账状态 //'#状态(paid:转账成功， pending:处理中，failed：转账失败)',
     * @param fee 手续费用
     * @param remark 第三方备注
     */
    public function updateTransferOrder(
        $applyAmount,
        $amount,
        $transferNo,
        $transferDate,
        $status = 'paid',
        $fee = 0,
        $remark = ''
    ) {
        global $app;
        $data = [
            'money'        => $applyAmount,
            'real_money'   => $amount,
            'transfer_no'  => $transferNo,
            'confirm_time' => $transferDate ?: date('Y-m-d H:i:s'),
            'status'       => $status,
            'fee'          => $fee,
            'remark'       => $remark,
        ];
        $app->getContainer()->db->getConnection()
                                ->table("transfer_order")
                                ->where('trade_no', $this->orderID)
                                ->where('status', 'pending')
                                ->update($data);
        //更新funds_withdraw  中的状态并发送信息
        if ($this->order['withdraw_order'] && $status == 'paid') {
            \Logic\Transfer\ThirdTransfer::updateWithdrawOrder($this->order['withdraw_order'], $status);
        }
    }

    /**
     * 更新第三方代付订单号
     */
    public function updateTransferNo(){
        global $app;
        $data = [
            'transfer_no'  => $this->transferNo,
        ];
        $app->getContainer()->db->getConnection()
            ->table("transfer_order")
            ->where('trade_no', $this->orderID)
            ->update($data);
    }

    public function returnResult() {
        if ($this->return['code'] == 10509) {
            $this->updateBalance($this->return['balance']);
        }
        //创建代付订单成功后 更新第三方订单号
        if ($this->return['code'] == 10500) {
            $this->updateTransferNo();
        }
        $this->_end();
        return $this->return;
    }

    //统一调用收尾方法，需要收尾共同处理的在这处理
    public function _end() {
        $this->addStartPayLog();
    }

    // 传输
    public function post() {
        if (is_array($this->parameter))
            $this->re = CURL::post($this->payUrl, $this->cacertURL, $this->parameter);
        else
            $this->re = CURL::commonPost($this->payUrl, $this->cacertURL, $this->parameter);
    }

    public function get() {
        if ($this->parameter)
            $this->payUrl .= '?' . $this->arrayToURL();
        $this->re = CURL::get($this->payUrl, $this->cacertURL);
    }

    // http_build_query  URL格式化传输数据
    public function basePost($referer = null) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->payUrl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);

        if (is_array($this->parameter)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->parameter));
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->parameter);
        }

        if ($referer) {
            curl_setopt($ch, CURLOPT_REFERER, $referer);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $this->curlError = curl_error($ch);

        $this->re = $response;
    }

    //通常MD5加密方法  有些加密需要带&key= 有些直接接key
    public function currentMd5(string $key = null) {
        $signPars = $this->arrayToURL($this->parameter);
        $signPars .= $key . $this->key;
        return md5($signPars);
    }

    //直接加密
    public function md5() {
        if ($this->sort)
            ksort($this->parameter);
        return md5(implode('', $this->parameter) . $this->key);
    }

    //二进制MD5加密
    public function HmacMd5($data = [], $key = null) {
        $data = $data ? $data : $this->parameter;
        $key = $key ? $key : $this->key;
        ksort($data);
        $str = implode('', $data);
        $b = 64; // byte length for md5
        if (strlen($key) > $b) {
            $key = pack("H*", md5($key));
        }
        $key = str_pad($key, $b, chr(0x00));
        $ipad = str_pad('', $b, chr(0x36));
        $opad = str_pad('', $b, chr(0x5c));
        $k_ipad = $key ^ $ipad;
        $k_opad = $key ^ $opad;

        $sign = md5($k_opad . pack("H*", md5($k_ipad . $str)));

        return $sign;
    }

    //通常回调解密方法
    public function currentVerify(array $data, string $sign) {
        $signPars = $this->arrayToURL($data);
        $signPars .= "&key=" . $this->pubKey;
        $s = strtolower(md5($signPars));

        $sign = strtolower($sign);
        return $sign == $s;
    }

    //私钥加密
    public function currentOpenssl($way = OPENSSL_ALGO_MD5) {
        $signPars = $this->arrayToURL();
        openssl_sign($signPars, $sign_info, $this->key, $way);
        return base64_encode($sign_info);
    }

    //私钥解密
    public function privateDecrypt(string $encrypted = null, string $pi_key = null, $code = null, int $length = 128) {
        $encrypted = $encrypted ? $encrypted : $this->parameter;
        $encrypted = $this->decode($encrypted, $code);
        $pi_key = $pi_key ? $pi_key : $this->key;
        $crypto = '';
        foreach (str_split($encrypted, $length) as $chunk) {

            openssl_private_decrypt($chunk, $decryptData, $pi_key);

            $crypto .= $decryptData;

        }
        return $crypto;
    }

    private function decode($data, $code) {
        switch (strtolower($code)) {
            case 'base64' :
                $data = base64_decode($data);
                break;
            case 'hex' :
                $data = $this->hex2bin($data);
                break;
            case 'bin' :
            default :
        }
        return $data;
    }

    private function hex2bin($hex = false) {
        $ret = $hex !== false && preg_match('/^[0-9a-fA-F]+$/i', $hex) ? pack("H*", $hex) : false;
        return $ret;
    }

    //解码函数
    public function urlsafe_b64decode(string $string) {
        $data = str_replace(['-', '_'], ['+', '/'], $string);
        $mod4 = strlen($data) % 4;
        if ($mod4) {
            $data .= substr('====', $mod4);
        }
        return base64_decode($data);
    }

    //公钥加密
    public function pukOpenssl(int $length = 117) {
        if (is_array($this->parameter))
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
    public function pukDecrypt(string $encrypted = null, string $pu_key = null, $code = null, int $length = 128) {

        $encrypted = $encrypted ? $encrypted : $this->parameter;
        $encrypted = $this->decode($encrypted, $code);
        $pu_key = $pu_key ? $pu_key : $this->pubKey;
        $crypto = '';
        foreach (str_split($encrypted, $length) as $chunk) {
            openssl_public_decrypt($chunk, $decrypted, $pu_key);
            $crypto .= $decrypted;

        }
        return $crypto;
    }

    public function arrayToURL() {
        $signPars = "";
        if ($this->sort)
            ksort($this->parameter);
        foreach ($this->parameter as $k => $v) {
            //  字符串0  和 0 全过滤了，所以加上
            if (!empty($v) || $v === 0 || $v === "0") {
                $signPars .= $k . "=" . $v . "&";
            }
        }
        $signPars = rtrim($signPars, '&');
        return $signPars;
    }

    public function arrayToURLALL(array $array) {
        $signPars = "";
        if ($this->sort)
            ksort($array);
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                $signPars .= $this->arrayToURLALL($v) . '& &';
            } else {
                //  字符串0  和 0 全过滤了，所以加上
                if (!empty($v) || $v === 0 || $v === "0") {
                    $signPars .= $k . "=" . $v . "&";
                }
            }
        }
        $signPars = rtrim($signPars, '&');
        return $signPars;
    }

    /**
     * 将数据转为XML
     */
    public function toXml(array $array) {
        $xml = '<xml>';
        forEach ($array as $k => $v) {
            $xml .= '<' . $k . '><![CDATA[' . $v . ']]></' . $k . '>';
        }
        $xml .= '</xml>';
        return $xml;
    }

    /**
     * XML解析成数组
     */
    public function parseXML($xmlSrc) {
        if (empty($xmlSrc)) {
            return false;
        }
        $array = [];
        $xml = simplexml_load_string($xmlSrc);
        $encode = $this->getXmlEncode($xmlSrc);
        if ($xml && $xml->children()) {
            foreach ($xml->children() as $node) {
                //有子节点
                if ($node->children()) {
                    $k = $node->getName();
                    $nodeXml = $node->asXML();
                    $v = $this->parseXML($nodeXml);
                } else {
                    $k = $node->getName();
                    $v = (string)$node;
                }
                if ($encode != "" && strpos($encode, "UTF-8") === false) {
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
        $ret = preg_match("/<?xml[^>]* encoding=\"(.*)\"[^>]* ?>/i", $xml, $arr);
        if ($ret) {
            return strtoupper($arr[1]);
        } else {
            return "";
        }
    }

    //发起第三方
    public function addStartPayLog() {
        if ($this->re) {
            global $app;
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
            $date['pay_type'] = $this->thirdConfig['code'];
            $date['payUrl'] = $this->payUrl??'';
            $date['order_id'] = $this->orderID;
            $date['json'] = json_encode($this->parameter);
            $date['response'] = $re;
            $date['created'] = time();
            $app->getContainer()->db->getConnection()
                                    ->table("transfer_log")
                                    ->insert($date);
            self::addLogByTxt($date);
        }
    }

    //添加文本日志
    public static function addLogByTxt($data){
        $date['logTime'] = date('Y-m-d H:i:s');
        if (!is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0777, true);
        }
        $file = self::$logDir.'/'.date('Y-m-d').'.log';
        $stream = @fopen($file, "aw+");
        !empty($data['json']) && $data['json'] = json_decode($data['json'], true);
        !empty($data['response']) && $data['response'] = json_decode($data['response'], true);
        $str = json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $str .= "\r\n";
        @fwrite($stream, $str);
        @fclose($stream);
    }

    function is_json($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}