<?php

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;

/**
 *
 * EPAY
 * @author
 */
class SIMPAY extends BASES {
    public $http_code;

    static function instantiation() {
        return new SIMPAY();
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
        if(empty($this->rechargeType)){
            $this->rechargeType = 70221;
        }
        $data          = [
            'merorder'       => $this->orderID,
            'merchantid'     => $this->partnerID,
            'command'        => 'PHPA',
            'datasets'       => 'tester|09123123123|xxxx@gamil.com',
            'price'          => $this->money,
            'backurl'        => $this->payCallbackDomain . '/pay/callback/simpay',
            'notes'          => 'test',
            'key'            => $this->key,
        ];
        $data['sign'] = $this->sign($data);


        $body = $this->en3desBoy($data,$this->pubKey);

        $info = array(
            'merchantid'=> $this->partnerID,
            'action'=> 'pay',
            'body'=> $body,
        );

        $this->parameter = $info;
        $this->payUrl    .= '/gateway.php';
    }

    public function formPost() {
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
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json; charset=utf-8',
            'Content-Length:' . strlen($post_data),
            'Cache-Control: no-cache',
            'Pragma: no-cache'
        ]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($curl);

        $this->http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $this->re        = $response;

        curl_close($curl);
    }

    //处理结果
    public function parseRE() {

        $result = json_decode($this->re, true);
        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['reason']) ? $result['reason'] : 'errorMsg:' . (string)$this->re;

        if($code == 'success') {
            //下单成功，跳转支付链接 The order is successfully placed, jump to the payment link

            $this->return['code']   = $code;
            $this->return['str']    = $message;
            $this->return['way']    = 'jump';
        } else {
            $this->return['code'] = 886;
            $this->return['msg']  = 'http_code:' . $this->http_code;
            $this->return['str']  = $this->re;
        }
    }




    //回调数据校验
    /*
     * $resArray 第三方通知数组
     * $deposit  系统流水信息
     * $vender   授权信息
     * */
    public function returnVerify($param = []) {
        $config       = Recharge::getThirdConfig('simpay');
        $this->pubKey = $config['pub_key'];
        $this->key    = $config['key'];

        //检验状态

        if($debody = $this->signVerify($param)) {

            // 判断SIGN
            $merchantid     = $debody['merchantid'];
            $merorder       = $debody['merorder'];
            $ordnum         = $debody['ordnum'];
            $command        = $debody['command'];
            $currency       = $debody['currency'];
            $ordstate       = $debody['ordstate'];
            $price          = $debody['price'];
            $realprice      = $debody['realprice'];
            $notes          = $debody['notes'];
            $sign           = $debody['sign'];

            // 判断SIGN
            $signarr = array(
                'merchantid'    => $merchantid,
                'merorder'      => $merorder,
                'ordnum'        => $ordnum,
                'command'       => $command,
                'currency'      => $currency,
                'ordstate'      => $ordstate,
                'price'         => $price,
                'realprice'     => $realprice,
                'notes'         => $notes,
                'key'           => $this->key,
            );
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
            $sign = strtoupper($sign);

            // 判断SIGN
            if($sign != $verify){
                throw new \Exception('sign is wrong');

            }else{
                $res = array(
                    'order_number'  => $merorder,
                    'third_order'   => $ordnum,
                    'third_money'   => $price,
                    'third_fee'     => 0
                );
                $res['status'] = 1;
            }
        } else {
            throw new \Exception('sign is wrong');
        }
        return $res;
    }

    //生成签名
    public function   sign($data) {
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
    /**
     * 补单
     * @param $order_number
     * @return mixed
     */
    public function supplyOrder($order_number, $payNo = '') {
        $config    = Recharge::getThirdConfig('ZEPAY');
        $this->key = $config['key'];

        //请求参数 Request parameter
        $data          = [
            'mchNo'      => $config['partner_id'],
            'payOrderId' => $payNo,
            'mchOrderNo' => $order_number,
            'reqTime'    => (string)time(),
            'version'    => '1.0',
            'signType'   => 'MD5',
        ];
        $config_params = !empty($config['params']) ? json_decode($config['params'], true) : [];
        if(!empty($config_params) && isset($config_params['appId'])) {
            $data['appId'] = $config_params['appId'];
        }

        $data['sign']    = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl    = $config['payurl'] . '/api/pay/query';

        $this->formPost();
        $this->addStartPayLog();

        $result  = json_decode($this->re, true);
        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;

        if($this->http_code == 200) {
            if($code == 0) {
                //未支付
                if($result['data']['state'] != 2) {
                    throw new \Exception($result['data']['state']);
                }
                $res = [
                    'status'       => $result['data']['state'],
                    'order_number' => $result['data']['mchOrderNo'],
                    'third_order'  => $result['data']['payOrderId'],
                    'third_money'  => $result['data']['amount'],
                ];
                return $res;
            }
        }

        throw new \Exception('http_code:' . $this->http_code . ' code:' . $code . ' message:' . $message);
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
    function de3des($value,$deskey){
        $deskey = substr($deskey,0,24);
        $deskey = sprintf('%-024s', $deskey);
        $result = hex2bin($value);
        $result = openssl_decrypt($result, 'DES-EDE3', $deskey, OPENSSL_RAW_DATA);
        return $result;
    }
}
