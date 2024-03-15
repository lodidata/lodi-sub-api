<?php

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;

class MATRIXPAY extends BASES {
    public $http_code;

    static function instantiation() {
        return new MATRIXPAY();
    }

    //与第三方交互
    public function start() {
        $this->initParam();  // 数据初始化
        $this->formPost();  // 发送请求
        $this->parseRE();  // 处理结果
    }

    //组装数组
    public function initParam() {
        if(empty($this->rechargeType)) {
            $this->rechargeType = 'phlp_netbank';
        }
        //请求参数 Request parameter
        $data = [
            'merchantNum' => $this->partnerID,
            'orderNo'     => $this->orderID,
            'amount'      => bcdiv($this->money, 100, 2),
            'notifyUrl'   => $this->payCallbackDomain . '/pay/callback/matrixpay',
        ];
        $data['sign']    = $this->sign($data);
        $data['channelCode'] = $this->rechargeType;
        $data['currency'] = 'PHP';
        $data['returnUrl'] = $this->returnUrl ?? 'no return_url';

        $this->parameter = $data;
        $this->payUrl    .= '/startCollectFiatForAnotherOrder';
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $output          = curl_exec($ch);
        $this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $this->re = $output;
    }

    //处理结果
    public function parseRE() {
        $result  = json_decode($this->re, true);
        $code  = isset($result['code']) ? $result['code'] : '';
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;
        is_array($message) && $message = json_encode($message);

        if($this->http_code == 200) {
            //下单成功，跳转支付链接 The order is successfully placed, jump to the payment link
            if($code == 200) {
                $code      = 0;
                $targetUrl = $result['data']['payUrl'];
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
        $config    = Recharge::getThirdConfig('matrixpay');
        $this->key = $config['key'];

        $params = $param;

        $res = [
            'status'       => 0,
            'order_number' => $params['orderNo'],
            'third_order'  => $params['platformOrderNo'],
            'third_money'  => $params['amount'] * 100,
            'third_fee'    => 0,
            'error'        => '',
        ];

        $sign=md5($params['state'].$params['merchantNum'].$params['orderNo'].$params['amount'].$this->key);
        //检验状态
        if($param['sign'] == $sign) {
            if($params['state'] == 1) {
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

        $str = '';
        foreach($param as $k => $v) {
            if(is_null($v) || $v === '')
                continue;
            $str .= $v;
        }
        $str = $str . $this->key;
        return md5($str);
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
