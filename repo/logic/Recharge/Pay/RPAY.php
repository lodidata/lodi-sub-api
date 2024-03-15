<?php

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;

class RPAY extends BASES
{
    public $http_code;

    static function instantiation()
    {
        return new RPAY();
    }

    //与第三方交互
    public function start()
    {
        $this->initParam();  // 数据初始化
        $this->formPost();   // 发送请求
        $this->parseRE();    // 处理结果
    }

    //组装数组
    public function initParam()
    {
        $data = [
            'merchant'     => $this->partnerID,
            'payment_type' => $this->rechargeType,
            'amount'       => floatval(bcdiv($this->money, 100, 2)),    //单位：元
            'order_id'     => $this->orderID,
            'bank_code'    => 'GCASH',
            'callback_url' => $this->payCallbackDomain . '/pay/callback/rpay',
            'return_url'   => $this->returnUrl
        ];

        $data['sign'] = $this->sign($data);

        $this->parameter = $data;
        $this->payUrl .= '/api/transfer';
    }

    public function formPost() {
        $params_data = json_encode($this->parameter, JSON_UNESCAPED_UNICODE);
        $ch = curl_init();
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
            'Content-Type: application/json',
        ]);
        $response = curl_exec($ch);
        $this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->re = $response;
    }

    //处理结果
    public function parseRE()
    {
        $result = json_decode($this->re, true);
        $code = isset($result['status']) ? $result['status'] : '1';
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;
        if ($this->http_code == 200) {
            $pay_no = '';
            if ($code == '1') {
                $code = 0;
                $targetUrl = $result['redirect_url'];
                $pay_no = $this->orderID;
            } else {
                $code = 886;
                $targetUrl = '';
            }

            $this->return['code'] = $code;
            $this->return['msg'] = $message;
            $this->return['way'] = 'jump';
            $this->return['str'] = $targetUrl;
            $this->return['pay_no'] = $pay_no;
        } else {
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
        $config = Recharge::getThirdConfig('rpay');
        $this->key = $config['key'];

        if (!isset($param['status']) || $param['status']!=5) {
            throw new \Exception('unpaid');
        }

        $res = [
            'status' => 0,
            'order_number' => $param['order_id'],
            'third_order' => $param['order_id'],
            'third_money' => $param['amount'] * 100,
            'third_fee' => 0,
            'error' => '',
        ];

        //检验状态
        if ($param['sign'] == $this->sign($param)) {
            $res['status'] = 1;
        } else {
            throw new \Exception('sign is wrong');
        }

        return $res;
    }

    //生成签名
    public function sign($data)
    {
        unset($data['sign']);
        ksort($data);

        $str = '';
        foreach ($data as $k => $v) {
            if(is_null($v) || $v === '') continue;     //值为 null 则不加入签名
            $str .= $k . '=' . $v . '&';
        }

        $sign_str = $str . 'key=' . $this->key;

        return md5($sign_str);
    }

    /**
     * 补单
     * @param $order_number
     * @return mixed
     */
    public function supplyOrder($order_number, $payNo = '')
    {
        $config = Recharge::getThirdConfig('rpay');
        $this->key = $config['key'];

        //请求参数 Request parameter
        $data = [
            'merchant' => $config['partner_id'],   //商户号
            'order_id' => $order_number,
        ];

        $data['sign'] = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl = $config['payurl'] . '/api/query';

        $this->formPost();
        $this->addStartPayLog();

        $result = json_decode($this->re, true);
        $code = isset($result['status']) ? $result['status'] : 5;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;

        if ($this->http_code == 200) {
            //未支付
            if ($result['data']['status'] != 5) {
                throw new \Exception($result['status']);
            }
            $res = [
                'status' => $result['status'],
                'order_number' => $result['order_id'],
                'third_order' => $result['order_id'],
                'third_money' => $result['amount']
            ];
            return $res;
        }

        throw new \Exception('http_code:' . $this->http_code . ' code:' . $code . ' message:' . $message);
    }

}