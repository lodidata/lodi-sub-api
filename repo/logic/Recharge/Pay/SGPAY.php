<?php

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\Utils;

/**
 *
 * SGPAY
 */
class SGPAY extends BASES {
    public $http_code;

    static function instantiation() {
        return new SGPAY();
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
        $rechargeType = 921;
        if(!empty($this->rechargeType)){
            $rechargeType = $this->rechargeType;
        }

        $data = [
            'pay_memberid' => $this->partnerID,
            'pay_orderid' => $this->orderID,
            'pay_applydate' => date('Y-m-d H:i:s'),
            'pay_bankcode' => $rechargeType,
            'pay_notifyurl' => $this->payCallbackDomain . '/pay/callback/sgpay',
            'pay_callbackurl' => $this->returnUrl ?? '-',
            'pay_amount' => floatval(bcdiv($this->money, 100, 2))
        ];
        $data['pay_md5sign'] = $this->sign($data);
        $data['pay_productname'] = 'GCash';
        $this->parameter = $data;
        $this->payUrl .= '/pay_index_index';
    }

    public function sign($data){
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
            if($status == 'success') {
                $targetUrl = $result['pay_url'];
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
            $this->return['pay_no'] = !empty($result['order_sn']) ? $result['order_sn'] : '';
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
        $config       = Recharge::getThirdConfig('sgpay');
        $this->key    = $config['key'];
        $this->pubKey = $config['pub_key'];


        $res = [
            'status'       => 0,
            'order_number' => $param['orderid'],
            'third_money'  => $param['amount'] * 100,
            'third_order'  => $param['transaction_id'],
            'third_fee'    => 0,
            'error'        => '',
        ];

        //检验状态
        $signParams = [
            'memberid' => $param['memberid'],
            'orderid' => $param['orderid'],
            'transaction_id' => $param['transaction_id'],
            'amount' => $param['amount'],
            'datetime' => $param['datetime'],
            'returncode' => $param['returncode']
        ];

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
