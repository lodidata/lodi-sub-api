<?php

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;

/**
 *
 * EPAY
 * @author
 */
class EPAY extends BASES {
    public $http_code;

    static function instantiation() {
        return new EPAY();
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
            'mch_id'         => (int)$this->partnerID,
            'pay_code'       => (int)$this->rechargeType,
            'order_no'       => $this->orderID,
            'price'          => (int)$this->money,
            'user_ip'        => $this->clientIp,
            'user_id'        => (string)$this->userId,
            'pay_notice_url' => $this->payCallbackDomain . '/pay/callback/epay',
            'pay_jump_url'   => $this->returnUrl ?? 'no return_url',
            'time'           => time(),
            'attach'         => ''
        ];
        $config_params = !empty($this->data['params']) ? json_decode($this->data['params'], true) : [];
        if(!empty($config_params) && isset($config_params['app_id'])) {
            $data['app_id'] = $config_params['app_id'];
        }

        $data['sign'] = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl    .= '/payApi/PayApi/CreateOrder';
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
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;

        if($this->http_code == 200) {
            //下单成功，跳转支付链接 The order is successfully placed, jump to the payment link
            if($code == 0) {
                $targetUrl = $result['data']['pay_url'];
            } else {
                $targetUrl = '';
            }

            $this->return['code']   = $code;
            $this->return['msg']    = $message;
            $this->return['way']    = 'jump';
            $this->return['str']    = $targetUrl;
            $this->return['pay_no'] = $result['data']['Dis_order_no'] ?? '';
        } else {
            $this->return['code'] = 886;
            $this->return['msg']  = 'http_code:' . $this->http_code;
            $this->return['way']  = 'jump';
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
        $config       = Recharge::getThirdConfig('epay');
        $this->pubKey = $config['pub_key'];


        $res = [
            'status'       => 0,
            'order_number' => $param['order_no'],
            'third_order'  => $param['dis_order_no'],
            'third_money'  => $param['real_price'],
            'third_fee'    => 0,
            'error'        => '',
        ];

        //检验状态
        if($this->signVerify($param)) {
            if($param['code'] == 1) {
                $res['status'] = 1;
            } else {
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
        unset($data['user_id']);
        unset($data['user_ip']);
        ksort($data);
        reset($data);

        $str = '';
        foreach($data as $k => $v) {
            if(is_null($v) || $v === '') continue;
            $str .= $k . '=' . $v . '&';
        }
        $str  .= 'key=' . $this->key;
        return md5(strtoupper($str));
    }

    //回调校验签名
    public function signVerify($data) {
        $sign = $data['sign'];
        unset($data['sign']);
        unset($data['order_cost']);
        ksort($data);
        reset($data);

        $str = '';
        foreach($data as $k => $v) {
            if(is_null($v) || $v === '') continue;
            $str .= $k . '=' . $v . '&';
        }
        $str      .= 'key=' . $this->pubKey;
        $sign_str = md5(strtoupper($str));

        if($sign === $sign_str) {
            return true;
        }
        return false;
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

}
