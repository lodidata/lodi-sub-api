<?php

namespace Las\Pay;

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;

/**
 *
 * TU-PAY支付
 * @author
 */
class TUPAY extends BASES {
    public $http_code;
    static function instantiation() {
        return new TUPAY();
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
            'amount'            => bcdiv($this->money, 100, 2),
            'merchant'          => $this->partnerID,//	是	string	商户号 business number
            'paytype'           => 'promptpay',
            'outtradeno'        => $this->orderID,
            'remark'            => '',
            'bankname'          => '',
            'notifyurl'         => $this->payCallbackDomain . '/pay/callback/tupay',
            'returnurl'         => '',
            'payername'         => '',
            'returndataformat'  => 'serverjson',
        );

        $data['sign']    = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl   .= '/pay';
    }

    //生成签名
    public function sign($data) {
        ksort($data);
        reset($data);

        $str = '';
        foreach ($data as $k => $v){
            $str .= $k.'='.strtolower(urlencode(trim($v))).'&';
        }
        $str = trim($str, '&');
        $sign_str       = $str . '&secret=' . $this->key;
        $sign           = md5($sign_str);
        return $sign;

    }

    //处理结果
    public function parseRE() {
        $result     = json_decode($this->re, true);
        $code       = isset($result['code']) ? $result['code'] : 6;

        if ($this->http_code  == 200) {
            //下单成功，跳转支付链接
            if($code == 0){
                $targetUrl = $result['results']['redirect'];
            }else{
                $targetUrl = '';
            }

            $this->return['code']    = $code;
            $this->return['msg']     = $code != 0 ? $result['results'] : '';
            $this->return['way']     = 'jump';
            $this->return['str']     = $targetUrl;
            $this->return['pay_no']  = $result['results']['tradeno'] ?? '';
        } else{
            $this->return['code'] = 886;
            $this->return['msg'] = 'http_code:' . $this->http_code;
            $this->return['way'] = 'jump';
            $this->return['str'] = $this->re;
        }

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
        $response = curl_exec($ch);
        $this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->re = $response;

    }


    //回调数据校验
    /*
     * $resArray 第三方通知数组
     * $deposit  系统流水信息
     * $vender   授权信息
     * */
    public function returnVerify($param = []) {
        $params = [
            "tradeno"           => $param['tradeno'],
            "outtradeno"        => $param['outtradeno'],
            "amount"            => $param['amount'],
            "ramount"           => $param['ramount'],
            "endtime"           => $param['endtime'],
            "status"            => $param['status'],
            "remark"            => $param['remark'],
            //"sign"              => $param['sign']
        ];

        $res = [
            'status'        => 0,
            'order_number'  => $params['outtradeno'],
            'third_order'   => $params['tradeno'],
            'third_money'   => $params['amount'] * 100,
            'third_fee'     => bcsub($params['ramount'], $params['amount'], 2) * 100,
            'error'         => '',
        ];

        $config    = Recharge::getThirdConfig('tupay');
        $this->key = $config['key'];

        if ($param['sign'] == $this->sign($params)) {
            if($param['status'] == 1){
                $res['status'] = 1;
            }else{
                throw new \Exception('unpaid');
            }
        } else {
            throw new \Exception('sign is wrong');
        }

        return $res;
    }

    /**
     * 补单
     * 订单状态 0未支付,1已支付,2超时,4撤销,5未认领
     * @param $order_number
     * @return mixed
     */
    public function supplyOrder($order_number, $payNo = '')
    {
        $config     = Recharge::getThirdConfig('tupay');
        $this->key  = $config['key'];
        //请求参数 Request parameter
        $data = array(
            'merchant' => $config['partner_id'],//	是	string	商户号 business number
            'outtradeno'    => $order_number,
        );

        $data['sign']    = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl    = $config['payurl'].'/trade/query';

        $this->formPost();

        $this->addStartPayLog();
        $result  = is_array($this->re) ?: json_decode($this->re, true);
        $code    = isset($result['code']) ? $result['code'] : 6;
        if ($this->http_code == 200) {
            if($code == 0){
                //未支付
                if($result['results']['status'] == 0){
                    throw new \Exception($result['results']['status']);
                }
                $res = [
                    'status'       => $result['results']['status'],
                    'order_number' => $result['results']['outtradeno'],
                    'third_order'  => $result['results']['tradeno'],
                    'third_money'  => $result['results']['amount'] * 100,
                    'third_fee'    => bcsub($result['results']['ramount'], $result['results']['amount'], 2) * 100,
                    'success_time'  => $result['results']['endtime']
                ];
                return $res;
            }
        }

        throw new \Exception('http_code:'.$this->http_code.' code:'.$code.' message:'.$result['results']?? $result['msg']);
    }
}
