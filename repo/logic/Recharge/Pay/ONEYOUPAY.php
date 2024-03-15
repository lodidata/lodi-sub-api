<?php

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;

class ONEYOUPAY extends BASES
{
    public $http_code;

    static function instantiation()
    {
        return new ONEYOUPAY();
    }

    //与第三方交互
    public function start()
    {
        $this->initParam();  // 数据初始化
        $this->formPost();  // 发送请求
        $this->parseRE();  // 处理结果
    }

    //组装数组
    public function initParam()
    {
        $data = [
            'channel_code' => $this->rechargeType,
            'username'     => $this->partnerID,
            'amount'       => floatval(bcdiv($this->money, 100, 2)),    //单位：元，金额最低100元
            'order_number' => $this->orderID,
            'notify_url'   => $this->payCallbackDomain . '/pay/callback/oneyoupay',
            'return_url'   => $this->returnUrl,
            'client_ip'    => \utils\Client::getIp()
        ];

        $data['sign'] = $this->sign($data);

        $this->parameter = $data;
        $this->payUrl .= '/api/v1/third-party/create-transactions';
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
        $code = isset($result['http_status_code']) ? $result['http_status_code'] : 200;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;
        if ($this->http_code == 200) {
            $pay_no = '';
            //下单成功，跳转支付链接 The order is successfully placed, jump to the payment link
            if (in_array($code, [200, 201])) {
                $code = 0;
                $targetUrl = $result['data']['casher_url'];
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
        $config = Recharge::getThirdConfig('oneyoupay');
        $this->key = $config['key'];

        $params = $param['data'];

        if (!isset($param['http_status_code']) || !in_array($param['http_status_code'], [200,201])) {
            throw new \Exception('unpaid');
        }

        $res = [
            'status' => 0,
            'order_number' => $params['order_number'],
            'third_order' => $params['order_number'],
            'third_money' => $params['amount'] * 100,
            'third_fee' => 0,
            'error' => '',
        ];

        //检验状态
        if ($params['sign'] == $this->sign($params)) {
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

        $sign_str = $str . 'secret_key=' . $this->key;

        return md5($sign_str);
    }

    /**
     * 补单
     * @param $order_number
     * @return mixed
     */
    public function supplyOrder($order_number, $payNo = '')
    {
        $config = Recharge::getThirdConfig('oneyoupay');
        $this->key = $config['key'];

        //请求参数 Request parameter
        $data = [
            'username' => $config['partner_id'],//    是   string  商户号 business number
            'order_number' => $order_number,
        ];

        $data['sign'] = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl = $config['payurl'] . '/api/v1/third-party/transaction-queries';

        $this->formPost();
        $this->addStartPayLog();

        $result = json_decode($this->re, true);
        $code = isset($result['http_status_code']) ? $result['http_status_code'] : '';
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;

        if ($this->http_code == 200) {
            if (in_array($code, [200, 201])) {
                //未支付
                if (!in_array($result['data']['status'], [4, 5])) {
                    throw new \Exception($result['data']['status']);
                }
                $res = [
                    'status' => $result['data']['status'],
                    'order_number' => $result['data']['order_number'],
                    'third_order' => $result['data']['system_order_number'],
                    'third_money' => $result['data']['amount'],
                ];
                return $res;
            }
        }

        throw new \Exception('http_code:' . $this->http_code . ' code:' . $code . ' message:' . $message);
    }

}