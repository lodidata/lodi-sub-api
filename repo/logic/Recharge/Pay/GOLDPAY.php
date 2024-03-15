<?php

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;

/**
 *
 * GOLDPAY
 * @author
 */
class GOLDPAY extends BASES
{
    public $http_code;

    static function instantiation()
    {
        return new GOLDPAY();
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
        $data = array(
            'amount' => bcdiv($this->money, 100),
            'callback_url' => $this->payCallbackDomain . '/pay/callback/goldpay',
            'hashed_mem_id' => $this->userId,
            'merchant_code' => $this->partnerID,
            'merchant_order_no' => $this->orderID,
            'platform' => 'PC',
            'risk_level' => 1,
            'service_type' => 999
        );

        $data['sign'] = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl .= '/sha256/deposit';
    }

    //生成签名
    public function sign($data)
    {
        if (empty($data)) {
            return false;
        }
        unset($data['sign']);

        ksort($data);
        $data = array_filter($data, function ($val) {
            return ($val !== "") && ($val !== 0) && ($val !== 'undefined');
        });

        $str = urldecode(http_build_query($data)) . '&key=' . $this->key;
        return hash('sha256', $str);
    }

    //处理结果
    public function parseRE()
    {
        $result = json_decode($this->re, true);
        $code = isset($result['status']) ? $result['status'] : 0;
        $message = isset($result['error_msg']) ? $result['error_msg'] : 'errorMsg:' . (string)$this->re;

        if ((int)$code === 1) {
            //下单成功，跳转支付链接 The order is successfully placed, jump to the payment link
            $this->return['code'] = 0;
            $this->return['msg'] = $message;
            $this->return['way'] = 'jump';
            $this->return['str'] = $result['transaction_url'];
        } else {
            $this->return['code'] = 886;
            $this->return['msg'] = 'http_code:' . $this->http_code . ' msg: ' . $message;
            $this->return['way'] = 'jump';
            $this->return['str'] = $this->re;
        }

    }

    public function formPost()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->payUrl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->parameter);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        $this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->re = $response;
        curl_close($ch);
    }


    //回调数据校验
    /*
     * $resArray 第三方通知数组
     * $deposit  系统流水信息
     * $vender   授权信息
     * */
    public function returnVerify($param = [])
    {
        $config = Recharge::getThirdConfig('goldpay');
        $this->key = $config['key'];
        $res = [
            'status' => 0,
            'order_number' => $param['merchant_order_no'],
            'third_order' => $param['trans_id'],
            'third_money' => $param['amount'] * 100,
            'third_fee' => 0,
            'error' => '',
        ];
        if ($param['sign'] == $this->sign($param)) {
            if ((int)$param['status'] === 1) {
                $res['status'] = 1;
            } else {
                throw new \Exception('{"status": 0, "error_msg":"sign is wrong"}');
            }
        } else {
            throw new \Exception('{"status": 0, "error_msg":"sign is wrong"}');
        }

        return $res;
    }

    /**
     * 补单
     * @param $order_number
     * @return mixed
     */
    public function supplyOrder($order_number, $payNo = '')
    {

    }
}
