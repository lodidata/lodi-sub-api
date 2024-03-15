<?php

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\Utils;


class APEPAY extends BASES {
    public $http_code;

    static function instantiation() {
        return new APEPAY();
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
        if(empty($this->rechargeType)){
            $this->rechargeType = 0;
        }
        $home_url = $app->getContainer()->get('settings')['website']['game_back_url'];
        //请求参数 Request parameter
        $data = array(
            'amount'        => bcdiv($this->money,100,2),
            'channelId'     => $this->rechargeType,
            'noticeUrl'     => $this->payCallbackDomain . '/pay/callback/apepay',
            'orderId'       => $this->orderID,
            'returnUrl'     => $this->returnUrl?? $home_url,
            'user'          => $this->partnerID,
        );

        $data['sign']    = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl   .= '/api/recharge';
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
        curl_close($ch);
        $this->re = $response;
    }




    //处理结果
    public function parseRE() {
        $result     = json_decode($this->re, true);
        $status       = isset($result['code']) ? $result['code'] : '';
        $message    = isset($result['msg']) ? $result['msg'] : 'errorMsg:'.(string)$this->re;
        is_array($message) && $message= json_encode($message);

        if ($this->http_code  == 200) {
            //code=0 代表查询成功,其他代表错误代码
            if($status === 0){
                $code = 0;
                $targetUrl = $result['url'];
            }else{
                $code = 1;
                $targetUrl = '';
            }

            $this->return['code']    = $code;
            $this->return['msg']     = $message;
            $this->return['way']     = 'jump';
            $this->return['str']     = $targetUrl;
            $this->return['pay_no']  = $this->orderID;
            
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
        $config    = Recharge::getThirdConfig('apepay');
        $this->key = $config['key'];

        //支付状态.2支付成功 ,3支付失败,其他支付中(默认只通知成功订单)
        if(!isset($param['payStatus']) || $param['payStatus'] != 2){
            throw new \Exception('unpaid');
        }
        $params = $param;

        $res = [
            'status'        => 0,
            'order_number'  => $params['orderId'],
            'third_order'   => $params['orderId'],
            'third_money'   => $params['payAmount'] * 100,
            'third_fee'     => 0,
            'error'         => '',
        ];

        //检验状态
        if ($param['sign'] == $this->sign($params)) {
            if($params['payStatus'] == 2)
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
        $str = '';
        foreach($param as $k => $v) {
            if(is_null($v) || $v === '')
                continue;
            $str .= $k . '=' . $v . '&';
        }
        $sign_str = $str .'token=' . $this->key;
        return strtoupper(md5($sign_str));
    }


    /**
     * 补单
     * @param $order_number
     * @return mixed
     * @throws \Exception
     */
    public function supplyOrder($order_number, $payNo = '')
    {
        $config     = Recharge::getThirdConfig('apepay');
        $this->key  = $config['key'];

        $data = [
            'orderId' => $this->orderID,
            'user'     => $this->partnerID,
        ];

        $data['sign']    = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl    = $config['payurl'].'/api/recharge/query';

        $this->formPost();
        $this->addStartPayLog();

        $result  = json_decode($this->re, true);
        $code    = isset($result['code']) ? $result['code'] : '';
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:'.(string)$this->re;

        if ($this->http_code == 200) {
            if($code === 0){
                //未支付
                if($result['payStatus'] != 2){
                    throw new \Exception($message);
                }
                $res = [
                    'status'       => $result['payStatus'],
                    'order_number' => $this->orderID,
                    'third_order'  => $this->orderID,
                    'third_money'  => $result['payAmount'] * 100,
                ];
                return $res;
            }
        }

        throw new \Exception('http_code:'.$this->http_code.' code:'.$code.' message:'.$message);
    }



}
