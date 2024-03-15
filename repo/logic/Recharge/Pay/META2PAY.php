<?php

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\Utils;

/**
 *
 * MBPAY
 * @author simos
 */
class META2PAY extends BASES {
    public $http_code;

    static function instantiation() {
        return new META2PAY();
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
            'appId'             => $this->partnerID,
            'channel'           => $this->rechargeType,
            'referenceNo'       => $this->orderID,
            'amount'            => bcdiv($this->money, 100, 2),
            'mobile'            => '09123123123',
            'userName'          => 'SANTOS,CRUZ,JUAN',
            'address'           => 'address',
            'remark'            => 'remark',
            'email'             => 'xxxx@gamil.com',
            'productType'       => 'CASHIER_DESK',
            'notificationURL'   => $this->payCallbackDomain . '/pay/callback/meta2pay',
        );
        // 商户私钥，商户自己生成
        // mchchant private key

        ksort($data);

        $params_str = '';

        foreach ($data as $key => $val) {
            $params_str = $params_str . $val;
        }
        $data['sign'] = $this->pivate_key_encrypt($params_str, $this->key);

        $this->parameter = json_encode($data);
        $this->payUrl    .= '/payment/collect/collect';
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
        $targetUrl  = isset($result['url']) ? $result['url'] : '';
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:'.(string)$this->re;
        if ($this->http_code  == 200) {
            //下单成功，跳转支付链接 The order is successfully placed, jump to the payment link
            $this->return['msg']     = $message;
            $this->return['way']     = 'jump';
            $this->return['str']     = $targetUrl;
            $this->return['pay_no']  = '';

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
            throw new \Exception('unpaid');
        }

        $res = [
            'status'        => 0,
            'order_number'  => $param['referenceNo'],
            'third_order'   => $param['transId'],
            'third_money'   => $param['amount'] * 100,
            'third_fee'     => 0,
            'error'         => '',
        ];
        $config    = Recharge::getThirdConfig('meta2pay');
//        $this->pubKey = $config['pub_key'];
        if(empty($config['params'])){
            throw new \Exception('no params');
        }
        $cnf_param = json_decode($config['params'], true);
        if(!isset($cnf_param['pubKey'])){
            throw new \Exception('no params');
        }
        $this->pubKey = $cnf_param['pubKey'];
        //检验状态
        if ($this->public_key_decrypt($param['sign'],$this->pubKey)) {
            if($param['status'] == '0')
            {
                $res['status'] = 1;
            }else{
                throw new \Exception('unpaid');
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
        $pivate_key = '-----BEGIN PRIVATE KEY-----'."\n".$pivate_key."\n".'-----END PRIVATE KEY-----';
        $pi_key = openssl_pkey_get_private($pivate_key);
        $crypto = '';
        foreach (str_split($data, 117) as $chunk) {
            openssl_private_encrypt($chunk, $encryptData, $pi_key);
            $crypto .= $encryptData;
        }

        return base64_encode($crypto);
    }

    function public_key_decrypt($data, $public_key)
    {
        $public_key = '-----BEGIN PUBLIC KEY-----'."\n".$public_key."\n".'-----END PUBLIC KEY-----';
        $data = base64_decode($data);
        $pu_key =  openssl_pkey_get_public($public_key);
        $crypto = '';
        foreach (str_split($data, 128) as $chunk) {
            openssl_public_decrypt($chunk, $decryptData, $pu_key);
            $crypto .= $decryptData;
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
