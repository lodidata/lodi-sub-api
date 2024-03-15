<?php

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;

/**
 *
 * DINGPEI
 * @author
 */
class DINGPEI extends BASES {
    public $http_code;

    static function instantiation() {
        return new DINGPEI();
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
            'appId'       => $this->partnerID,
            'appOrderNo'  => $this->orderID,
            'orderAmt'    => bcdiv($this->money, 100, 2),
            'payId'       => !empty($this->payId) ? $this->payId  : '201',
        );

        $data['sign']    = $this->sign($data);
        $data['notifyURL'] = $this->payCallbackDomain . '/pay/callback/dingpei';
        $data['jumpURL'] = '';
        $data['fromAddress'] = '';

        $this->parameter = $data;
        $this->payUrl   .= '/newbankPay/crtOrder.do';
    }



    public function formPost() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_URL, $this->payUrl);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->arrayToURL($this->parameter));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
        ]);
        $response = curl_exec($ch);
        $this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->re = $response;
    }




    //处理结果
    public function parseRE() {
        $result     = json_decode($this->re, true);
        $code       = isset($result['code']) ? $result['code'] : 1;
        $message    = isset($result['msg']) ? $result['msg'] : 'errorMsg:'.(string)$this->re;


        if ($this->http_code  == 200) {
            //下单成功，跳转支付链接 The order is successfully placed, jump to the payment link
            if($code === '0000'){
                $targetUrl = $result['data']['payUrl'];
            }else{
                $targetUrl = '';
            }

            $this->return['code']    = $code;
            $this->return['msg']     = $message;
            $this->return['way']     = 'jump';
            $this->return['str']     = $targetUrl;
            if(!empty($result['data']['tradeNo']))
            {
                $this->return['pay_no'] = $result['data']['tradeNo'];
            }else{
                $this->return['pay_no']  = $result['data']['orderNo'] ?? '';
            }
            
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
        $config    = Recharge::getThirdConfig('dingpei');
        $this->key = $config['key'];

        $params = [
            'appOrderNo' => $param['appOrderNo'],
            'orderNo' => $param['orderNo'],
            'orderTime' => $param['orderTime'],
            'appId' => $param['appId'],
            'orderAmt' => $param['orderAmt'],
            'payAmt' => $param['payAmt'],
            'orderStatus' => $param['orderStatus'],
        ];

        $res = [
            'status'        => 0,
            'order_number'  => $param['appOrderNo'],
            'third_order'   => $param['orderNo'],
            'third_money'   => $param['payAmt'] * 100,
            'third_fee'     => 0,
            'error'         => '',
        ];

        //检验状态
        if ($param['sign'] == $this->sign($params)) {
            if($param['orderStatus'] === '00')
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
    public function sign($data) {
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
        $sign           = strtoupper(md5($sign_str));
        return $sign;
    }



    /**
     * 补单
     * @param $order_number
     * @return mixed
     */
    public function supplyOrder($order_number, $payNo = '')
    {
        $config     = Recharge::getThirdConfig('DINGPEI');
        $this->key  = $config['key'];

        //请求参数 Request parameter
        $data = array(
            'appId' => $config['partner_id'],//    是   string  商户号 business number
            'appOrderNo'    => $order_number,
        );

        $data['sign']    = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl    = $config['payurl'].'/newbankPay/selOrder.do';

        $this->formPost();
        $this->addStartPayLog();

        $result  = json_decode($this->re, true);
        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:'.(string)$this->re;

        if ($this->http_code == 200) {
            if($code === '0000'){
                //未支付
                if($result['data']['orderStatus'] !== '00'){
                    throw new \Exception($result['data']['orderStatus']);
                }
                $res = [
                    'status'       => $result['data']['orderStatus'],
                    'order_number' => $result['data']['appOrderNo'],
                    'third_order'  => $result['data']['orderNo'],
                    'third_money'  => $result['data']['orderAmt'] * 100,
                ];
                return $res;
            }
        }

        throw new \Exception('http_code:'.$this->http_code.' code:'.$code.' message:'.$message);
    }



}
