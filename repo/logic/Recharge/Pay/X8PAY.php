<?php

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;

/**
 *
 * X8PAY
 * @author
 */
class X8PAY extends BASES {
    public $http_code;

    static function instantiation() {
        return new X8PAY();
    }

    //与第三方交互
    public function start() {
        $this->initParam();  // 数据初始化
        $this->formPost();  // 发送请求
        $this->parseRE();  // 处理结果
    }

    //组装数组
    public function initParam() {
        global $app;
        $tid = $app->getContainer()->get('settings')['app']['tid'];
        //请求参数 Request parameter
        $data = array(
            'x_access_key' => $this->partnerID,
            'x_reference_no' => str_pad($tid,4,0,STR_PAD_LEFT).'-'.$this->orderID,
            'x_amount' => bcdiv($this->money, 100, 2),
            'details' => json_encode([
                'customerName' => $this->orderID,
            ]),
            'generate_customer_redirect_url'=> true,
        );

        $data['signature']    = $this->sign($data);

        $config_params = !empty($this->data['params']) ? json_decode($this->data['params'],true) : [];
        $this->parameter = [
            'url' => $config_params['url'].'/api/orders',
            'request_params' => $data
        ];
        $this->payUrl   .= '/api/orders';
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
            'Content-Length:' . strlen($post_data) ,
            'Cache-Control: no-cache',
            'Pragma: no-cache'
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($curl);

        $this->http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $this->re = $response;

        curl_close($curl);
    }




    //处理结果
    public function parseRE() {
        $result     = json_decode($this->re, true);
        $code  = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;
        if ($this->http_code  == 200) {
            //下单成功，跳转支付链接 The order is successfully placed, jump to the payment link
            if($code == 0 && $result['data']['http_code'] == 202){
                $code = 0;
                $targetUrl = $result['data']['result']['customerRedirectUrl']??'';
            }else{
                $targetUrl = '';
            }

            if(RUNMODE != 'dev' && !empty($targetUrl)){
                $targetUrl .= '&institution_code=GCASH';
            }
            $this->return['code']    = $code;
            $this->return['msg']     = $message;
            $this->return['way']     = 'jump';
            $this->return['str']     = $targetUrl;

        } else{
            $this->return['code'] = 886;
            $this->return['msg'] = 'X8PAY:' . $message ?? '代付失败';
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
        $config    = Recharge::getThirdConfig('x8pay');
        $this->pubKey = $config['pub_key'];

        $params = [
            'x_reference_no' => $param['x_reference_no'],
            'x_payment_id' => $param['x_payment_id'],
            'x_payment_status' => $param['x_payment_status'],
            'signature' => $param['signature'],
        ];

        $res = [
            'status'        => 0,
            'order_number'  => explode('-',$param['x_reference_no'])[1],
            'third_order'   => $param['x_payment_id'],
            'third_money'   => 0,
            'third_fee'     => 0,
            'error'         => '',
        ];

        //检验状态
        if ($this->signVerify($params)) {
            \DB::table('funds_deposit')->where('trade_no',  $res['order_number'])->where('pay_no','0')->update(['pay_no' => $res['third_order']]);
            if($param['x_payment_status'] === 'EXECUTED') {
                $res['status'] = 1;
                $res['third_money'] = \DB::table('funds_deposit')->where('trade_no',  $res['order_number'])->value('money');
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
        unset($data['signature']);
        $data = array_filter($data, function ($k) {
            return substr($k, 0, 2) === "x_";
        }, ARRAY_FILTER_USE_KEY);
        ksort($data);
        $str = '';
        foreach ($data as $k => $v) {
            $str .= "$k$v";
        }

        return hash_hmac("sha256", $str, $this->key);
    }


    //回调校验签名
    public function signVerify($data) {
        $sign = $data['signature'];
        unset($data['signature']);
        $data = array_filter($data, function ($k) {
            return substr($k, 0, 2) === "x_";
        }, ARRAY_FILTER_USE_KEY);
        ksort($data);
        $str = '';
        foreach ($data as $k => $v) {
            $str .= "$k$v";
        }

        $sign_new = hash_hmac("sha256", $str, $this->pubKey);

        if($sign === $sign_new){
            return true;
        }
        return false;
    }



    /**
     * 补单
     * @param $order_number
     * @return mixed
     */
    public function supplyOrder($order_number)
    {
        $config     = Recharge::getThirdConfig('x8pay');
        $this->key  = $config['key'];
        $this->payUrl .= '/api/payments/status';

        $funds_deposit = \DB::table('funds_deposit')->where('trade_no',$order_number)->first(['pay_no','money']);
        $this->parameter = [
            'url' => $config['params']['url'].'/api/payments/status',
            'header_params' => ['X-Swiftpay-Payment-Token'=>$funds_deposit->pay_no]
        ];


        $this->formPost();
        $this->addStartPayLog();

        $result  = json_decode($this->re, true);
        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:'.(string)$this->re;

        if ($this->http_code == 200) {
            if($code == 0 && $result['data']['http_code'] == 200){
                //未支付
                if($result['data']['result'] != 'EXECUTED'){
                    throw new \Exception($code);
                }
                $res = [
                    'status'       => $code,
                    'order_number' => $order_number,
                    'third_order'  => '',
                    'third_money'  => $funds_deposit->money,
                ];
                return $res;
            }
        }

        throw new \Exception('http_code:'.$this->http_code.' code:'.$code.' message:'.$message);
    }



}
