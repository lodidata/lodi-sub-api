<?php

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\Utils;

/**
 *
 * TIGERPAY
 * @author
 */
class TIGERPAY extends BASES {
    public $http_code;

    static function instantiation() {
        return new TIGERPAY();
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
            'out_trade_no' => $this->orderID,
            'amount'       => $this->money / 100,
            'callback_url' => $this->payCallbackDomain . '/pay/callback/tigerpay',
        );

//        $data['sign']    = $this->sign($data);
        $this->parameter = json_encode($data);
        $this->payUrl   .= '/api/transaction';
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

    public function formPost() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->payUrl);
        // 执行后不直接打印出来
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // 设置请求方式为post
        curl_setopt($ch, CURLOPT_POST, true);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->parameter);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $this->key
        ]);
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
        $result     = json_decode($this->re, true);
        $bool       = isset($result['success']) ? $result['success'] : false;
        $message    = '';
        $code       = 0;

        if ($this->http_code  == 200) {
            //下单成功，跳转支付链接 The order is successfully placed, jump to the payment link
            if($bool){
                $targetUrl = $result['data']['uri'];
            }else{
                $targetUrl = '';
                $code = isset($result['status_code']) ? $result['status_code'] : 401;
                $message = isset($result['message']) ? $result['message'] : 'unknown error';
            }

            $this->return['code']    = $code;
            $this->return['msg']     = $message;
            $this->return['way']     = 'jump';
            $this->return['str']     = $targetUrl;
            $this->return['pay_no']  = !empty($result['data']['trade_no']) ? $result['data']['trade_no'] : '';
            
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
        $config    = Recharge::getThirdConfig('tigerpay');
        $this->key = $config['key'];
        $this->pubKey = $config['pub_key'];

        if(!isset($param['state']) || $param['state'] != "completed"){
            throw new \Exception('unpaid');
        }
        $params = $param;

        $res = [
            'status'        => 0,
            'order_number'  => $params['out_trade_no'],
            'third_order'   => $params['trade_no'],
            'third_money'   => $params['amount'] * 100,
            'third_fee'     => 0,
            'error'         => '',
        ];

        //检验状态
        if ($param['sign'] == $this->sign($params)) {
            if($params['state'] == 'completed')
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
        unset($param['sign']);

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
            $originalString = implode('&', $sortParam) . $this->key . $this->pubKey;
        } else {
            $originalString = $this->key;
        }

        return md5($originalString);
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
