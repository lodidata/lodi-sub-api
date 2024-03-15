<?php

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\Utils;

/**
 *
 * HONORPAY
 */
class HONORPAY extends BASES
{
    public $http_code;

    static function instantiation()
    {
        return new HONORPAY();
    }

    //与第三方交互
    public function start()
    {
        $this->initParam();  // 数据初始化
        $this->formPost();  // 发送请求
        $this->parseRE();  // 处理结果
    }

    /**
     * 初始化参数
     */
    public function initParam()
    {
        //请求参数 Request parameter
        $params = [
            'account' => $this->partnerID,
            'payType' => 'phlink',
            'payMoney' =>  floatval(bcdiv($this->money, 100, 2)),
            'ip' => \utils\Client::getIp(),
            'notifyURL' => $this->payCallbackDomain . '/pay/callback/honorpay',
            'returnURL' => $this->returnUrl ?? $this->payCallbackDomain . '/pay/callback/honorpay',
            'orderNo' => $this->orderID
        ];

        $params['sign'] = $this->sign($params);
        $this->parameter = $params;
        $this->payUrl .= '/api/v2/pay_request';
    }

    /**
     * sign加密
     * @param $data
     * @return false|string
     */
    public function sign($data)
    {
        if (empty($data)) {
            return false;
        }
        ksort($data);
        $str = urldecode(http_build_query($data)) . '&key=' . $this->pubKey;
        return strtoupper(hash_hmac("sha256", $str, $this->pubKey));
    }

    public function formPost()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->payUrl);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->parameter));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Bearer ' . $this->key
        ));

        $response = curl_exec($ch);
        $this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $this->re = $response;
    }

    /**
     * 处理结果
     */
    public function parseRE()
    {
        $result = json_decode($this->re, true);
        $code = isset($result['retCode']) ? $result['retCode'] : 1;
        $message = isset($result['retMsg']) ? $result['retMsg'] : 'errorMsg:' . (string)$this->re;
        if ($this->http_code == 200) {
            //下单成功，跳转支付链接 The order is successfully placed, jump to the payment link
            if ($code === '0') {
                $targetUrl = $result['redirectURL'];
                $returnCode = 0;
            } else {
                $targetUrl = '';
                $returnCode = 1;
                $message = isset($result['retMsg']) ? $result['retMsg'] : 'unknown error';
            }

            $this->return['code'] = $returnCode;
            $this->return['msg'] = $message;
            $this->return['way'] = 'jump';
            $this->return['str'] = $targetUrl;
            $this->return['pay_no'] = !empty($result['uuid']) ? $result['uuid'] : '';
        } else {
            $this->return['code'] = 886;
            $this->return['msg'] = 'http_code:' . $this->http_code;
            $this->return['way'] = 'jump';
            $this->return['str'] = $this->re;
        }
    }

    /**
     * 回调数据校验
     * @param $param
     * @return array|mixed
     */
    public function returnVerify($param = [])
    {
        $config = Recharge::getThirdConfig('honorpay');
        $this->key = $config['key'];
        $this->pubKey = $config['pub_key'];

        $res = [
            'status' => 0,
            'order_number' => $param['orderNo'],
            'third_order' => $param['uuid'],
            'third_money' => $param['realCharge'] * 100,
            'third_fee' => 0,
            'error' => ''
        ];

        $sign = $param['sign'];
        unset($param['sign']);

        $data = http_build_query($param) . "&key={$this->pubKey}";
        $hashSign = strtoupper(hash_hmac("sha256", $data, $this->pubKey));

        //检验状态
        if ($sign == $this->sign($param)) {
            if ($param['payStatus'] == 'success') {
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
    public function supplyOrder($order_number, $payNo = '')
    {
        $config = Recharge::getThirdConfig('yypay');
        $this->key = $config['key'];

        //请求参数 Request parameter
        $data = [
            'merchantId' => $config['partner_id'],//    是   string  商户号 business number
            'bizNum' => $order_number,
        ];

        $data['sign'] = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl = $config['payurl'] . '/pay/order/query/status';

        $this->formPost();
        $this->addStartPayLog();

        $result = json_decode($this->re, true);
        $code = isset($result['success']) ? $result['success'] : '';
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;

        if ($this->http_code == 200) {
            if ($code === true) {
                //未支付
                if ($result['data']['status'] != 1) {
                    throw new \Exception($result['data']['status']);
                }
                $res = [
                    'status' => $result['data']['status'],
                    'order_number' => $result['data']['merchantBizNum'],
                    'third_order' => $result['data']['sysBizNum'],
                    'third_money' => $result['data']['money'],
                ];
                return $res;
            }
        }

        throw new \Exception('http_code:' . $this->http_code . ' code:' . $code . ' message:' . $message);
    }

}
