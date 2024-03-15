<?php

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;

/**
 *
 * etpay
 */
class ETPAY extends BASES
{
    public $http_code;

    static function instantiation()
    {
        return new ETPAY();
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
        $rechargeType = 'PHP_Gcash';
        if (!empty($this->rechargeType)) {
            $rechargeType = $this->rechargeType;
        }
        $data = array(
            'number'     => $this->partnerID,
            'channel'    => $rechargeType,
            'order_id'   => $this->orderID,
            'pay_name'   => 'etpayUser',
            'notify_url' => $this->payCallbackDomain . '/pay/callback/etpay',
            'amount'     => bcdiv($this->money, 100, 2),
            'timestamp'  => time()
        );

        $data['sign'] = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl .= '/api/gateway/deposit';
    }

    public function formGet()
    {
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

    public function formPost()
    {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->payUrl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($this->parameter));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json;charset=utf-8',
        ]);
        $response = curl_exec($ch);
        $this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->re = $response;
        curl_close($ch);
    }

    //处理结果
    public function parseRE()
    {
        $result = json_decode($this->re, true);
        $status = isset($result['status']) ? $result['status'] : '1';
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;
        if ($this->http_code == 200) {
            //下单成功，跳转支付链接 The order is successfully placed, jump to the payment link
            if ($status === true) {
                $targetUrl = $result['url'];
                $returnCode = 0;
            } else {
                $targetUrl = '';
                $returnCode = 1;
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
        $config = Recharge::getThirdConfig('etpay');
        $this->key = $config['key'];

        if (!isset($param['execution']) || $param['execution'] != 1) {
            throw new \Exception('unpaid');
        }
        $params = $param;

        $res = [
            'status'       => 0,
            'order_number' => $params['order_id'],
            'third_order'  => $params['order_id'],
            'third_money'  => $params['amount'] * 100,
            'third_fee'    => 0,
            'error'        => '',
        ];

        //检验状态
        if ($param['sign'] == $this->sign($params)) {
            if ($params['execution'] == 1) {
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
    public function sign($requestData)
    {
        if (empty($requestData)) {
            return false;
        }
        ksort($requestData);
        foreach ($requestData as $key => $value) {
            if ($key != 'sign') {
                $array[] = "$key=$value";
            }
        }
        $signStr = implode('&', $array);
        $signStr = $signStr . "&key=" . $this->key;
        return md5($signStr);
    }


    /**
     * 补单
     * @param $order_number
     * @return mixed
     */
    public function supplyOrder($order_number, $payNo = '')
    {
        $config = Recharge::getThirdConfig('etpay');
        $this->key = $config['key'];

        //请求参数 Request parameter
        $data = array(
            'merchantId' => $config['partner_id'],//    是   string  商户号 business number
            'bizNum'     => $order_number,
        );

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
