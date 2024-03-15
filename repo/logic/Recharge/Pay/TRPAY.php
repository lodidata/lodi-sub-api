<?php

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\Utils;

/**
 *
 * JHPAY
 * @author simos
 */
class TRPAY extends BASES {
    public $http_code;

    static function instantiation() {
        return new TRPAY();
    }

    //与第三方交互
    public function start() {
        $this->initParam();  // 数据初始化
        $this->formPost();  // 发送请求
        $this->parseRE();  // 处理结果
    }

    //组装数组
    public function initParam() {
        $rechargeType = '11';
        if(!empty($this->rechargeType)){
            $rechargeType = $this->rechargeType;
        }
        $data = array(
            'pay_memberid'    => $this->partnerID,
            'pay_orderid'     => $this->orderID,
            'pay_applydate'   => date('Y-m-d H:i:s', time()),
            'pay_bankcode'    => $rechargeType,
            'pay_notifyurl'   => $this->payCallbackDomain . '/pay/callback/trpay',
            'pay_callbackurl' => $this->payCallbackDomain . '/pay/callback/trpay',
            'pay_amount'      => bcdiv($this->money, 100, 2),
            'pay_userphone'   => '11111111111',
            'pay_userip'      => '127.0.0.1',
            'pay_userid'      => $this->userId,
            'pay_username'    => 'username',
        );

        $data['pay_md5sign'] = $this->sign($data);
        $data['format']      = 'json';
        $this->parameter     = $data;
        $this->payUrl        .= '/Pay_Index.html';
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
        $code       = isset($result['status']) ? $result['status'] : '';
        $message    = isset($result['msg']) ? $result['msg'] : 'errorMsg:'.(string)$this->re;

        if ($this->http_code  == 200) {
            //下单成功，跳转支付链接 The order is successfully placed, jump to the payment link
            if($code == 'success'){
                $code = 0;
                $targetUrl = $result['data'];
            }else{
                $targetUrl = '';
            }

            $this->return['code']    = $code;
            $this->return['msg']     = $message;
            $this->return['way']     = 'jump';
            $this->return['str']     = $targetUrl;
            $this->return['pay_no'] =!empty($result['pay_orderid']) ? $result['pay_orderid'] : '';
            
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
        $config    = Recharge::getThirdConfig('trpay');
        $this->key = $config['key'];
        $params = $param;

        $res = [
            'status'        => 0,
            'order_number'  => $params['orderid'],
            'third_order'   => $params['transaction_id'],
            'third_money'   => bcmul($params['amount'],100,2),
            'third_fee'     => 0,
            'error'         => '',
        ];

        //检验状态
        unset($params['attach']);
        if ($params['sign'] == $this->returnSign($params)) {
            if($params['returncode'] == '00')
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
    //生成回调签名
    private function returnSign($params) {
        $returnArray = array( // 返回字段
            "memberid" => $params["memberid"], // 商户ID
            "orderid" =>  $params["orderid"], // 订单号
            "amount" =>  $params["amount"], // 交易金额
            "datetime" =>  $params["datetime"], // 交易时间
            "transaction_id" =>  $params["transaction_id"], // 流水号
            "returncode" => $params["returncode"]
        );
        ksort($returnArray);
        reset($returnArray);
        $md5str = "";
        foreach ($returnArray as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        return strtoupper(md5($md5str . "key=" . $this->key));
    }
    //生成签名
    public function sign($param) {
        unset($param['sign']);

        $newParam = array_filter($param);
        ksort($newParam);
        if (!empty($newParam)) {
            $sortParam = [];
            foreach ($newParam as $k => $v) {
                if(empty($v)){
                    continue;
                }
                $sortParam[] = $k . '=' . $v;
            }
            $originalString = implode('&', $sortParam) . '&key='.$this->key;
        } else {
            $originalString = $this->key;
        }
        return strtoupper(md5($originalString));
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
