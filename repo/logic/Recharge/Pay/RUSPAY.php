<?php

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;

class RUSPAY extends BASES
{
    public $http_code;

    static function instantiation()
    {
        return new RUSPAY();
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
            'mch_id'       => $this->partnerID,
            'notify_url'   => $this->payCallbackDomain . '/pay/callback/ruspay',
            'user_id'      => '123456789',
            'mch_order_no' => $this->orderID,
            'pay_type'     => $this->rechargeType,
            'trade_amount' => bcdiv($this->money,100),    //整数，单位元
            'order_date'   => date('Y-m-d H:i:s', time()),
        ];

        $data['sign'] = $this->sign($data);

        $this->parameter = $data;
        $this->payUrl .= '/api/Pay/addPay';
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
            'Content-Length: ' . strlen($params_data)
        ]);
        $response = curl_exec($ch);
        $this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->re = $response;
    }

    public function formGet() {
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
        $this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        //释放curl句柄
        curl_close($ch);
        $this->re = $output;
    }

    //处理结果
    public function parseRE()
    {
        $result = json_decode($this->re, true);
        $code = isset($result['code']) ? $result['code'] : 20000;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;
        if ($this->http_code == 200) {
            $pay_no = '';
            //下单成功，跳转支付链接 The order is successfully placed, jump to the payment link
            if ($code == 20000) {
                $code = 0;
                $targetUrl = $result['result']['pay_pageurl'];
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
        $config = Recharge::getThirdConfig('ruspay');
        $this->key = $config['key'];

        if (!isset($param['tradeResult']) || $param['tradeResult']!=1) {
            throw new \Exception('unpaid');
        }

        $res = [
            'status' => 0,
            'order_number' => $param['mch_order_no'],
            'third_order' => $param['mch_order_no'],
            'third_money' => intval($param['trade_amount']) * 100,
            'third_fee' => 0,
            'error' => '',
        ];

        //检验状态
        $signData['trade_amount'] = intval($param['trade_amount']);
        $signData['mch_id'] = $param['mch_id'];
        $signData['mch_order_no'] = $param['mch_order_no'];
        $signData['notify_url'] = $param['notify_url'];
        $signData['order_date'] = $param['order_date'];
        $signData['pay_type'] = $param['pay_type'];
        $signData['user_id'] = $param['user_id'];

        if ($param['sign'] == $this->sign($signData)) {
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

        return strtolower(md5($sign_str));
    }

    /**
     * 补单
     * @param $order_number
     * @return mixed
     */
    public function supplyOrder($order_number, $payNo = '')
    {
        $config = Recharge::getThirdConfig('ruspay');
        $this->key = $config['key'];

        //请求参数 Request parameter
        $data = [
            'merchant_id' => $config['partner_id'],//    是   string  商户号 business number
            'merchant_order' => $order_number,
        ];

        $this->parameter = $data;
        $this->payUrl = $config['payurl'] . '/api/PayoutOrder/getPayInOrder';

        $this->formGet();
        $this->addStartPayLog();

        $result = json_decode($this->re, true);
        $code = isset($result['code']) ? $result['code'] : 20000;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;

        if ($this->http_code == 200) {
            if ($code == 20000) {
                $res = [
                    'status' => $result['result']['status'],
                    'order_number' => $result['result']['order_id'],
                    'third_order' => $result['result']['merchant_order'],
                    'third_money' => $result['result']['money'],
                ];
                return $res;
            }
        }

        throw new \Exception('http_code:' . $this->http_code . ' code:' . $code . ' message:' . $message);
    }

}