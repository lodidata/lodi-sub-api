<?php

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;

/**
 *
 * 优付宝
 * @author
 */
class TAIWANGPAY extends BASES {
    public $http_code;
    static function instantiation() {
        return new TAIWANGPAY();
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
        $time = time();
        $data = array(
            'merchantNo'        => $this->partnerID,//	是	string	商户号 business number
            'orderNo'           => $this->orderID,
            'userNo'            => null,
            'userName'          => null,
            'channelNo'         => 2,
            'amount'            => bcdiv($this->money, 100, 2),
            'discount'          => null,
            'payeeName'         => null,
            'bankName'          => null,
            'extra'             => null,
            'datetime'          => date('Y-m-d H:i:s', $time),
            'notifyUrl'         => $this->payCallbackDomain . '/pay/callback/taiwangpay',
            'time'              => $time,
            'appSecret'         => $this->pubKey,
        );

        $data['sign']    = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl   .= '/order/create';
    }

    //生成签名
    public function sign($data) {
        unset($data['bankName']);
        unset($data['userName']);
        unset($data['channelNo']);
        unset($data['payeeName']);
        unset($data['appSecret']);
        unset($data['amountBeforeFixed']);
        unset($data['sign']);
        ksort($data);
        reset($data);

        $str = '';
        foreach ($data as $k => $v){
            $str .= $k.'='.$v.'&';
        }
        $str = trim($str, '&');

        $sign_str       = $str . $this->key;
        $digest         = hash("sha256", $sign_str);
        $sign           = strtoupper(md5($digest));
        return $sign;

    }

    //处理结果
    public function parseRE() {
        $result     = json_decode($this->re, true);
        $code       = isset($result['code']) ? $result['code'] : 1;
        $message    = isset($result['text']) ? $result['text'] : 'errorMsg:'.(string)$this->re;

        if ($this->http_code  == 200) {
            //下单成功，跳转支付链接 The order is successfully placed, jump to the payment link
            if($code == 0){
                $targetUrl = $result['targetUrl'];
            }else{
                $targetUrl = '';
            }

            $this->return['code']    = $code;
            $this->return['msg']     = $message;
            $this->return['way']     = 'jump';
            $this->return['str']     = $targetUrl;
            $this->return['pay_no']  = $result['tradeNo'] ?? '';
        } else{
            $this->return['code'] = 886;
            $this->return['msg'] = 'http_code:' . $this->http_code;
            $this->return['way'] = 'jump';
            $this->return['str'] = $this->re;
        }

    }

    public function formPost() {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->payUrl);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->arrayToURL($this->parameter));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
        ]);
        $response = curl_exec($ch);
        $this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->re = $response;

    }


    //回调数据校验
    /*
     * $resArray 第三方通知数组
     * $deposit  系统流水信息
     * $vender   授权信息
     * */
    public function returnVerify($param = []) {
        $params = [
            "status"            => $param['status'],
            "tradeNo"           => $param['tradeNo'],
            "orderNo"           => $param['orderNo'],
            "userNo"            => $param['userNo'],
            "userName"          => $param['userName'],
            "channelNo"         => $param['channelNo'],
            "amount"            => $param['amount'],
            "discount"          => $param['discount'],
            "lucky"             => $param['lucky'],
            "paid"              => $param['paid'],
            "extra"             => $param['extra'],
            "amountBeforeFixed" => $param['amountBeforeFixed']??'',
            "sign"              => $param['sign']
        ];

        $res = [
            'status'        => 0,
            'order_number'  => $param['orderNo'],
            'third_order'   => $param['tradeNo'],
            'third_money'   => $param['amount'] * 100,
            'third_fee'     => 0,
            'error'         => '',
        ];

        $config    = Recharge::getThirdConfig('taiwangpay');
        $this->key = $config['key'];

        if ($param['sign'] == $this->sign($params)) {
            if($param['status'] === 'PAID' || $param['status'] === 'MANUAL PAID'){
                $res['status'] = 1;
            }else{
                throw new \Exception('unpaid');
            }

        } else {
            throw new \Exception('sign is wrong');
        }

        return $res;
    }

    /**
     * 补单
     * @param $order_number
     * @return mixed
     */
    public function supplyOrder($order_number, $payNo = '')
    {
        $config     = Recharge::getThirdConfig('TAIWANGPAY');
        $this->key  = $config['key'];

        //请求参数 Request parameter
        $data = array(
            'merchantNo' => $config['partner_id'],//	是	string	商户号 business number
            'tradeNo'    => $payNo,
            'orderNo'    => $order_number,
            'time'       => time(),
            'appSecret'  => $config['pub_key'],
        );

        $data['sign']    = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl    = $config['payurl'].'/order/status';

        $this->formPost();
        $this->addStartPayLog();

        $result  = json_decode($this->re, true);
        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['text']) ? $result['text'] : 'errorMsg:'.(string)$this->re;

        if ($this->http_code == 200) {
            if($code == 0){
                //未支付
                if($result['status'] != 'PAID'){
                    throw new \Exception($result['status']);
                }
                $res = [
                    'status'       => $result['status'],
                    'order_number' => $result['orderNo'],
                    'third_order'  => $result['tradeNo'],
                    'third_money'  => $result['paid'] * 100,
                ];
                return $res;
            }
        }

        throw new \Exception('http_code:'.$this->http_code.' code:'.$code.' message:'.$message);
    }
}
