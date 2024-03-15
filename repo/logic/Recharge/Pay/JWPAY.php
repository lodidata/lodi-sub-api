<?php

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;

/**
 *
 * jwpay
 */
class JWPAY extends BASES
{
    public $http_code;

    static function instantiation()
    {
        return new JWPAY();
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
        $rechargeType = '14';
        if (!empty($this->rechargeType)) {
            $rechargeType = $this->rechargeType;
        }
        $isTest = false;
        $configParams = !empty($this->data['params']) ? json_decode($this->data['params'], true) : [];
        if (isset($configParams['isTest'])) {
            $isTest = $configParams['isTest'];
        }

        $data = array(
            'Amount'           => floatval(bcdiv($this->money, 100, 2)),
            'CurrencyId'       => 5,
            'IsTest'           => $isTest,
            'PayerKey'         => (string)$this->userId,
//            'PayerName'        => '帅哥',
            'PaymentChannelId' => (int)$rechargeType,
            'ShopInformUrl'    => $this->payCallbackDomain . '/pay/callback/jwpay',
            'ShopOrderId'      => $this->orderID,
            'ShopReturnUrl'    => $this->returnUrl ?: $this->payCallbackDomain,
            'ShopUserLongId'   => $this->partnerID
        );

        $data['EncryptValue'] = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl .= '/api/createOrder';
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
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($curl, CURLOPT_TIMEOUT, 15);
        curl_setopt($curl, CURLOPT_URL, $this->payUrl);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        $post_data = json_encode($this->parameter);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($curl);

        $this->http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $this->re = $response;

        curl_close($curl);
    }

    //处理结果
    public function parseRE()
    {
        $result = json_decode($this->re, true);
        $status = isset($result['Success']) ? $result['Success'] : 'error';
        $message = isset($result['ErrorMessage']) ? $result['ErrorMessage'] : 'errorMsg:' . (string)$this->re;
        if ($this->http_code == 200) {
            //下单成功，跳转支付链接 The order is successfully placed, jump to the payment link
            if ($status === true) {
                $targetUrl = $result['PayUrl'];
                $returnCode = 0;
            } else {
                $targetUrl = '';
                $returnCode = 1;
                $message = isset($result['ErrorMessage']) ? $result['ErrorMessage'] : 'unknown error';
            }

            $this->return['code'] = $returnCode;
            $this->return['msg'] = $message;
            $this->return['way'] = 'jump';
            $this->return['str'] = $targetUrl;
            //$this->return['pay_no'] = !empty($result['orderNo']) ? $result['orderNo'] : '';
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
        $config = Recharge::getThirdConfig('jwpay');
        $this->key = $config['key'];

        if (!isset($param['OrderStatusId']) || $param['OrderStatusId'] != 2) {
            throw new \Exception('unpaid');
        }
        $params = $param;

        $res = [
            'status'       => 0,
            'order_number' => $params['ShopOrderId'],
            'third_order'  => $params['TrackingNumber'],
            'third_money'  => $params['AmountPaid'] * 100,
            'third_fee'    => 0,
            'error'        => '',
        ];

        //检验状态
        if ($param['EncryptValue'] == $this->sign($params)) {
            if ($params['OrderStatusId'] == 2) {
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
    public function sign($data)
    {
        if (empty($data)) {
            return false;
        }
        unset($data['EncryptValue']);

        ksort($data);
        $str = '';

        foreach ($data as $key => $value) {
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }
            if ($value !== null) {
                $str .= $key . '=' . $value . '&';
            }
        }

        $str .= 'HashKey=' . $this->key;
        $str = strtolower($str);
        return strtoupper(hash('sha256', $str));
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
