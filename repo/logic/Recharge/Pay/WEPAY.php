<?php

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\Utils;

/**
 *
 * WEPAY
 * @author
 */
class WEPAY extends BASES {
    public $http_code;

    static function instantiation() {
        return new WEPAY();
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
            $this->rechargeType = 753;
        }

        $data = array(
            'mchId'             => $this->partnerID,
            'passageId'         => $this->rechargeType,
            'amount'            => bcdiv($this->money, 100, 2),
            'orderNo'           => $this->orderID,
            'notifyUrl'         => $this->payCallbackDomain . '/pay/callback/wepay',
            /*'callBackUrl'       => '',
            'otherData'         => '',
            'remark'            => '',
            'number'            => '',
            'email'             => '',*/
        );

        $data['sign']    = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl   .= '/collect/create';
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
        //code 描述 200 成功 400 业务异常 具体错误详见 msg 字段
        $status = isset($result['code']) ? $result['code'] : 400;
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:'.(string)$this->re;
        if ($this->http_code  == 200) {
            //下单成功，跳转支付链接 The order is successfully placed, jump to the payment link
            if($status == 200){
                $code = 0;
                $targetUrl = $result['data']['payUrl'];
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
        $config    = Recharge::getThirdConfig('wepay');
        $this->key = $config['key'];
        //支付状态,0-订单生成,1-支付成功,2-支付失败
        if(!isset($param['payStatus']) || $param['payStatus'] == 0){
            throw new \Exception('unpaid');
        }
        $params = $param;

        $res = [
            'status'        => 0,
            'order_number'  => $params['orderNo'],
            'third_order'   => $params['tradeNo'],
            'third_money'   => bcmul($params['amount'], 100),
            'third_fee'     => 0,
            'error'         => '',
        ];

        //检验状态
        if ($param['sign'] == $this->sign($params)) {
            if($params['payStatus'] == 1)
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
        ksort($param);

        $originalString='';
        foreach($param as $key=>$val){
            if(is_null($val) || trim($val) == ''){
                continue;
            }
            $originalString = $originalString . $key . "=" . $val . "&";
        }
        $originalString.= "key=" . $this->key;
        return strtolower(md5($originalString));
    }


    /**
     * 补单
     * @param $order_number
     * @return mixed
     * @throws \Exception
     */
    public function supplyOrder($order_number, $payNo = '')
    {
        $config     = Recharge::getThirdConfig('wepay');
        $this->key  = $config['key'];

        //请求参数 Request parameter
        $data = array(
            'mchId' => $config['partner_id'],//    是   string  商户号 business number
            'orderNo'    => $order_number,
        );

        $data['sign']    = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl    = $config['payurl'].'/collect/query';

        $this->formPost();
        $this->addStartPayLog();

        $result  = json_decode($this->re, true);
        //code 描述 200 成功 400 业务异常 具体错误详见 msg 字段
        $code  = isset($result['code']) ? $result['code'] : 400;
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:'.(string)$this->re;

        if ($this->http_code == 200) {
            if($code === 200){
                //未支付 支付状态,0-订单生成,1-支付成功,2-支付失败
                if($result['data']['payStatus'] != 1){
                    throw new \Exception($result['data']['status']);
                }
                $res = [
                    'status'       => $result['data']['payStatus'],
                    'order_number' => $result['data']['orderNo'],
                    'third_order'  => $result['data']['tradeNo'],
                    'third_money'  => bcmul($result['data']['amount'],100,0),
                ];
                return $res;
            }
        }

        throw new \Exception('http_code:'.$this->http_code.' code:'.$code.' message:'.$message);
    }



}
