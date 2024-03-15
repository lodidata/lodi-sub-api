<?php

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\Utils;

/**
 *
 * COPOPAY
 * @author
 */
class COPOPAY extends BASES {
    public $http_code;

    static function instantiation() {
        return new COPOPAY();
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
            $this->rechargeType = 'GCS';
        }
        $data = array(
            'accessType' => 1,
            'merchantId' => $this->partnerID,
            'orderAmount' => bcdiv($this->money, 100, 2),
            'orderNo' => $this->orderID,
            'notifyUrl' => $this->payCallbackDomain . '/pay/callback/copopay',
            'payType' => $this->rechargeType,// 支付类型
//            'payType' => 'GCASH',//$this->rechargeType,// 支付类型
            'userId' => strval($this->userId), // 会员名称
            'language' => 'zh-CN', // 语言
            'currency' => 'PHP', // 付款币种
            'orderName' => 'PAY', // 订单名称
            'orderTime' => date('YmdHis'),
        );

        $data['sign']    = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl   .= '/dior/merchant-api/pay-order';
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
        $params_data = json_encode($this->parameter, JSON_UNESCAPED_UNICODE);
        curl_setopt($ch, CURLOPT_URL, $this->payUrl);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params_data);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json;charset=UTF-8',
        ]);
        $response = curl_exec($ch);
        $this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $this->re = $response;
    }




    //处理结果
    public function parseRE() {
        $result     = json_decode($this->re, true);
        $code  = isset($result['respCode']) ? $result['respCode'] : 1;
        $message = isset($result['respMsg']) ? $result['respMsg'] : 'errorMsg:'.(string)$this->re;

        if ($this->http_code  == 200) {
            $pay_no = '';
            //下单成功，跳转支付链接 The order is successfully placed, jump to the payment link
            if($code == '000'){
                $code = 0;
                $targetUrl = $result['info'] ?? '';
                $pay_no = $result['payOrderNo'] ?? '';
            }else{
                $code = 886;
                $targetUrl = '';
            }

            $this->return['code']    = $code;
            $this->return['msg']     = $message;
            $this->return['way']     = 'jump';
            $this->return['str']     = $targetUrl;
            $this->return['pay_no']  = $pay_no;

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
        $config    = Recharge::getThirdConfig('copopay');
        $this->key = $config['key'];

        if(!isset($param['orderStatus']) || $param['orderStatus'] == '0'){
            throw new \Exception('unpaid');
        }
        $params = $param;

        $res = [
            'status' => 0,
            'order_number' => $params['orderNo'],
            'third_order' => $params['payOrderId'] ?? '',
            'third_money' => $params['orderAmount'] * 100,
            'third_fee' => ($params['fee'] ?? 0) * 100,
            'error' => '',
        ];

        //检验状态
        if ($param['sign'] == $this->sign($params)) {
            if (in_array($params['orderStatus'], ['1', '3'])) {
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
    public function sign($param) {
        unset($param['sign']);
        ksort($param);

        $originalString='';

        foreach($param as $key=>$val){
            if(!empty($val)){
                $originalString = $originalString . $key . "=" . $val . "&";
            }
        }
        $originalString.= "Key=" . $this->key;

        return strtolower(md5($originalString));
    }


    /**
     * 补单
     * @param $order_number
     * @return mixed
     */
    public function supplyOrder($order_number, $payNo = '')
    {
        $config     = Recharge::getThirdConfig('aiaipay');
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
