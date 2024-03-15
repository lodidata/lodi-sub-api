<?php

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\Utils;

/**
 *
 * YUHONGPAY
 */
class YUHONGPAY extends BASES {
    public $http_code;

    static function instantiation() {
        return new YUHONGPAY();
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
        $data = [
            'mchId'      => $this->partnerID,
            'productId'  => '',
            'amount'     => $this->money,
            'mchOrderNo' => $this->orderID,
            'currency'   => 'php',
            'notifyUrl'  => $this->payCallbackDomain . '/pay/callback/yuhongpay',
            'subject'    => 'recharge',
            'body'       => 'recharge',
        ];

        if(!is_null($this->rechargeType) && $this->rechargeType != ''){
            $data['productId'] = $this->rechargeType;
        }

        $data['sign']    = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl    .= '/api/v1/collect';
    }

    public function formGet() {
        //        echo '<pre>';print_r($this->parameter);exit;
        //初始化
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->payUrl . '?' . http_build_query($this->parameter));
        // 执行后不直接打印出来
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        // 跳过证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // 不从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        //执行并获取HTML文档内容
        $output = curl_exec($ch);
        //释放curl句柄
        curl_close($ch);
        $this->re = $output;
    }

    public function formPost()
    {
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
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($curl);

        $this->http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $this->re = $response;

        curl_close($curl);
    }

    //处理结果
    public function parseRE() {
        $result  = json_decode($this->re, true);
        $status  = isset($result['code']) ? $result['code'] : '';
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;
        if($this->http_code == 200 || $this->http_code == 400) {
            //下单成功，跳转支付链接 The order is successfully placed, jump to the payment link
            if($status == 20000) {
                $code      = 0;
                $targetUrl = $result['data']['cashierData']['url'];
            } else {
                $code      = 1;
                $targetUrl = '';
            }

            $this->return['code']   = $code;
            $this->return['msg']    = $message;
            $this->return['way']    = 'jump';
            $this->return['str']    = $targetUrl;
            $this->return['pay_no'] = $this->orderID;
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
        $config    = Recharge::getThirdConfig('yuhongpay');
        $this->key = $config['key'];

        if(!isset($param['status']) || ($param['status'] != 2 && $param['status'] != 3)) {
            throw new \Exception('unpaid');
        }
        $params = $param;

        $res = [
            'status'       => 0,
            'order_number' => $params['mchOrderNo'],
            'third_order'  => $params['payOrderId'],
            'third_money'  => $params['amount'],
            'third_fee'    => 0,
            'error'        => '',
        ];

        //检验状态
        if($param['sign'] == $this->sign($params)) {
            if($param['status'] == 2 || $param['status'] == 3){
                $res['status'] = 1;
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
        foreach($data as $k => $v) {
            if(is_null($v) || $v === '')
                continue;
            $str .= $k . '=' . $v . '&';
        }
        $str = trim($str, '&');

        $sign_str = $str . '&key=' . $this->key;

        return strtoupper(md5($sign_str));
    }

    /**
     * 补单
     * @param $order_number
     * @return mixed
     */
    public function supplyOrder($order_number, $payNo = '') {
        $config    = Recharge::getThirdConfig('apay');
        $this->key = $config['key'];

        //请求参数 Request parameter
        $data = [
            'merchantId' => $config['partner_id'],//    是   string  商户号 business number
            'bizNum'     => $order_number,
        ];

        $data['sign']    = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl    = $config['payurl'] . '/pay/order/query/status';

        $this->formPost();
        $this->addStartPayLog();

        $result  = json_decode($this->re, true);
        $code    = isset($result['success']) ? $result['success'] : '';
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;

        if($this->http_code == 200) {
            if($code === true) {
                //未支付
                if($result['data']['status'] != 1) {
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

        throw new \Exception('http_code:' . $this->http_code . ' code:' . $code . ' message:' . $message);
    }

}
