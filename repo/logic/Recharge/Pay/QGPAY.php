<?php

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\Utils;

/**
 *
 * QGPAY
 * @author simos
 */
class QGPAY extends BASES {
    public $http_code;

    static function instantiation() {
        return new QGPAY();
    }

    //与第三方交互
    public function start() {
        $this->initParam();  // 数据初始化
        $this->formPost();  // 发送请求
        $this->parseRE();  // 处理结果
    }

    //组装数组
    public function initParam() {
        //请求参数 Request parameter
        $data = array(
            'merchant'     => $this->partnerID,
            'businessCode' => $this->rechargeType,
            'orderNo'      => $this->orderID,
            'name'         => 'SANTOS,CRUZ,JUAN',
            'phone'        => '09123123123',
            'email'        => 'xxxx@gamil.com',
            'amount'       => bcdiv($this->money, 100, 2),
            'notifyUrl'    => $this->payCallbackDomain . '/pay/callback/qgpay',
            'pageUrl'      => $this->returnUrl,
            'bankCode'     => '',
            'subject'      => 'recharge',

        );
        // 商户私钥，商户自己生成
        $data['sign'] = $this->pivate_key_encrypt($data, $this->key);

        $this->parameter = json_encode($data);
        $this->payUrl    .= '/orderPay';
    }


    public function formPost() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->payUrl);
        // 执行后不直接打印出来
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // 设置请求方式为post
        curl_setopt($ch, CURLOPT_POST, true);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->parameter);
        curl_setopt($ch, CURLOPT_HTTPHEADER,array(
            'Content-Type: application/json; charset=utf-8',
            'Cache-Control: no-cache',
            'Pragma: no-cache'
        ));
        // 请求头，可以传数组
        // curl_setopt($ch, CURLOPT_HEADER, $header);
        // curl_setopt($ch, CURLOPT_HEADER, 1);
        // 跳过证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // 不从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $output = curl_exec($ch);
        $this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $this->re = $output;
    }




    //处理结果
    public function parseRE() {
        $result  = json_decode($this->re, true);
        $code  = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:'.(string)$this->re;
        if ($this->http_code  == 200 && $code == 0) {
            //下单成功，跳转支付链接 The order is successfully placed, jump to the payment link
            $this->return['msg']     = $message;
            $this->return['way']     = 'jump';
            $this->return['str']     = $result['data']['orderData'];
            $this->return['pay_no']  = $result['data']['orderNo'];
        } else{
            $this->return['code'] = 886;
            $this->return['msg'] = 'http_code:' . $this->http_code;
            $this->return['way'] = 'jump';
            $this->return['str'] = $this->re;
        }
    }




    //回调数据校验
    /*
     * $resArray 第三方通知数组
     * $deposit  系统流水信息
     * $vender   授权信息
     * */
    public function returnVerify($param = [])
    {
        if(!isset($param['sign'])){
            throw new \Exception('no sign');
        }

        $res = [
            'status'        => 0,
            'order_number'  => $param['merchantOrderNo'],
            'third_order'   => $param['orderNo'],
            'third_money'   => bcmul($param['payAmount'], 100),
            'third_fee'     => 0,
            'error'         => '',
        ];
        $config    = Recharge::getThirdConfig('qgpay');
        $this->pubKey = $config['pub_key'];
        //检验状态
        if ($this->public_key_decrypt($param['sign'],$this->pubKey)) {
            if($param['status'] == 'success')
            {
                $res['status'] = 1;
            }else{
                throw new \Exception('status fail');
            }
        } else {
            throw new \Exception('sign is wrong');
        }

        return $res;
    }

    //生成签名
    public function sign($param) {
        unset($param['SIGNED_MSG']);
        unset($param['s']);

        $newParam = array_filter($param);
        if (!empty($newParam)) {
            $fields = array_keys($newParam);
            $sortParam = [];
            sort($fields);
            foreach ($fields as $k => $v) {
                if(empty($newParam[$v])){
                    continue;
                }
                $sortParam[] = $v . '=' . $newParam[$v];
            }
            $originalString = implode('&', $sortParam) . $this->key;
        } else {
            $originalString = $this->key;
        }
        return md5($originalString);
    }
    function pivate_key_encrypt($data, $pivate_key)
    {
        ksort($data);
        $str = '';
        foreach ($data as $k => $v){
            if(!empty($v)){
                $str .=(string) $k.'='.$v.'&';
            }
        }
        $str = rtrim($str,'&');
        //替换成自己的私钥
        $pem = chunk_split($pivate_key, 64, "\n");
        $pem = "-----BEGIN PRIVATE KEY-----\n" . $pem . "-----END PRIVATE KEY-----\n";
        $private_key = openssl_pkey_get_private($pem);
        $crypto = '';
        foreach (str_split($str, 117) as $chunk) {
            openssl_private_encrypt($chunk, $encryptData, $private_key);
            $crypto .= $encryptData;
        }
        $encrypted = base64_encode($crypto);
        $encrypted = str_replace(array('+','/','='),array('-','_',''),$encrypted);

        return $encrypted;
    }

    function public_key_decrypt($sign, $public_key)
    {
        //替换自己的公钥
        $pem = chunk_split( $public_key,64, "\n");
        $pem = "-----BEGIN PUBLIC KEY-----\n" . $pem . "-----END PUBLIC KEY-----\n";
        $publickey = openssl_pkey_get_public($pem);

        $base64=str_replace(array('-', '_'), array('+', '/'), $sign);

        $crypto = '';
        foreach(str_split(base64_decode($base64), 128) as $chunk) {
            openssl_public_decrypt($chunk,$decrypted,$publickey);
            $crypto .= $decrypted;
        }

        return $crypto;
    }

    /**
     * 补单
     * @param $order_number
     * @return mixed
     */
    public function supplyOrder($order_number, $payNo = '')
    {
        $config     = Recharge::getThirdConfig('yypay');
        $this->key  = $config['key'];

        //请求参数 Request parameter
        $data = array(
            'merchantId' => $config['partner_id'],//    是   string  商户号 business number
            'bizNum'    => $order_number,
        );

        $data['sign']    = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl    = $config['payurl'].'/pay/order/query/status';

        $this->formPost();
        $this->addStartPayLog();

        $result  = json_decode($this->re, true);
        $code    = isset($result['success']) ? $result['success'] : '';
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:'.(string)$this->re;

        if ($this->http_code == 200) {
            if($code === true){
                //未支付
                if($result['data']['status'] != 1){
                    throw new \Exception($result['data']['status']);
                }
                $res = [
                    'status'       => $result['data']['status'],
                    'order_number' => $result['data']['merchantBizNum'],
                    'third_order'  => $result['data']['sysBizNum'],
                    'third_money'  => $result['data']['money'],
                ];
                return $res;
            }
        }

        throw new \Exception('http_code:'.$this->http_code.' code:'.$code.' message:'.$message);
    }



}
