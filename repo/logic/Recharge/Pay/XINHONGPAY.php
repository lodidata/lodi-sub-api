<?php

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;

/**
 *
 * XINHONGPAY
 * @author
 */
class XINHONGPAY extends BASES
{
    public $http_code;

    static function instantiation()
    {
        return new XINHONGPAY();
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
        //请求参数 Request parameter
        if (empty($this->rechargeType)) {
            $this->rechargeType = 'gcash';
        }

        $data = array(
            'merchantNo' => $this->partnerID,
            'outTradeNo' => $this->orderID,
            'amount'     => bcdiv($this->money, 100, 2),
            'notifyUrl'  => $this->payCallbackDomain . '/pay/callback/xinhongpay',
            'type'       => $this->rechargeType,
            'extend'     => '',
        );

        $data['sign'] = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl .= '/payin/create';
    }

    public function formPost()
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->payUrl);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->arrayToURL($this->parameter));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
        ]);
        $response = curl_exec($ch);
        $this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->re = $response;
    }


    //处理结果
    public function parseRE()
    {
        $result = json_decode($this->re, true);
        $status = isset($result['status']) ? $result['status'] : 'error';
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;
        if ($this->http_code == 200) {
            //下单成功，跳转支付链接 The order is successfully placed, jump to the payment link
            if ($status === 0) {
                $targetUrl = $result['data']['payUrl'];
                $returnCode = 0;
            } else {
                $targetUrl = '';
                $returnCode = 1;
                $message = isset($result['msg']) ? $result['msg'] : 'unknown error';
            }

            $this->return['code'] = $returnCode;
            $this->return['msg'] = $message;
            $this->return['way'] = 'jump';
            $this->return['str'] = $targetUrl;
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
        $config = Recharge::getThirdConfig('xinhongpay');
        $this->key = $config['key'];

        if (!isset($param['status']) || $param['status'] != 1) {
            throw new \Exception('unpaid');
        }
        $params = $param;

        $res = [
            'status'       => 0,
            'order_number' => $params['outTradeNo'],
            'third_order'  => $params['sn'],
            'third_money'  => $params['amount'] * 100,
            'third_fee'    => 0,
            'error'        => '',
        ];

        //检验状态
        if ($param['sign'] == $this->sign($params)) {
            if ($params['status'] == 1) {
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
    public function sign($param)
    {
        unset($param['sign']);
        ksort($param);

        $originalString = '';

        foreach ($param as $key => $val) {
            if (!empty($val)) {
                $originalString = $originalString . $key . "=" . $val . "&";
            }
        }
        $originalString .= "signKey=" . $this->key;

        return strtoupper(md5($originalString));
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
