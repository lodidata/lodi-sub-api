<?php

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\Utils;

/**
 *
 * OMOPAY
 */
class OMOPAY extends BASES {
    public $http_code;

    static function instantiation() {
        return new OMOPAY();
    }

    //与第三方交互
    public function start() {
        $this->initParam();  // 数据初始化
        $this->formPost();  // 发送请求
        $this->parseRE();  // 处理结果
    }

    //组装数组
    public function initParam() {
        $rechargeType = '821';
        if(!empty($this->rechargeType)){
            $rechargeType = $this->rechargeType;
        }
        //请求参数 Request parameter
        $data = array(
            'pay_memberid'      => $this->partnerID,//	是	string	商户号 business number
            'pay_orderid'       => $this->orderID,
            'pay_amount'        => bcdiv($this->money, 100, 2),
            'pay_applydate'     => date('Y-m-d H:i:s',time()),
            'pay_bankcode'      => $rechargeType,
            'pay_notifyurl'     => $this->payCallbackDomain . '/pay/callback/omopay',
        );
        $data['pay_md5sign'] = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl .= '/pay';
    }

    public function sign($data){
        unset($data['sign']);
        unset($data['s']);
        if(empty($data)){
            return false;
        }

        ksort($data);
        $str = urldecode(http_build_query($data));
        $str .= '&key='.$this->key;
        return strtoupper(md5($str));
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
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->parameter));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
        ]);
        $response        = curl_exec($ch);
        $this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->re        = $response;
    }

    //处理结果
    public function parseRE() {
        $result  = json_decode($this->re, true);
        $status       = isset($result['status']) ? $result['status'] : 'error';
        $message    = isset($result['msg']) ? $result['msg'] : 'errorMsg:'.(string)$this->re;
        if($this->http_code == 200) {
            //下单成功，跳转支付链接 The order is successfully placed, jump to the payment link
            if($status == '1') {
                $targetUrl = $result['payUrl'];
                $returnCode=0;
            } else {
                $targetUrl = '';
                $returnCode= 1;
                $message   = isset($result['msg']) ? $result['msg'] : 'unknown error';
            }

            $this->return['code']   = $returnCode;
            $this->return['msg']    = $message;
            $this->return['way']    = 'jump';
            $this->return['str']    = $targetUrl;
            $this->return['pay_no'] = !empty($result['pay_orderid']) ? $result['pay_orderid'] : '';
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
        $config       = Recharge::getThirdConfig('omopay');
        $this->key    = $config['key'];
        $this->pubKey = $config['pub_key'];


        $res = [
            'status'       => 0,
            'order_number' => $param['orderid'],
            'third_money'  => $param['amount'] * 100,
            'third_fee'    => 0,
            'error'        => '',
        ];
        //检验状态
        $signParams = [
            'memberid'       => $param['memberid'],
            'orderid'        => $param['orderid'],
            'transaction_id' => $param['transaction_id'],
            'amount'         => $param['amount'],
            'true_amount'    => $param['true_amount'],
            'datetime'       => $param['datetime'],
            'returncode'     => $param['returncode']
        ];
        //检验状态

        if($param['sign'] == $this->sign($signParams)) {
            if($param['returncode'] === '00') {
                $res['status'] = 1;
            } else {
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
    /**
     * 补单
     * @param $order_number
     * @return mixed
     */
    public function supplyOrder($order_number, $payNo = '') {
        $config    = Recharge::getThirdConfig('yypay');
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
