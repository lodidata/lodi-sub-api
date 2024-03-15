<?php

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\Utils;


class MCOPAY extends BASES
{
    public $http_code;

    static function instantiation()
    {
        return new MCOPAY();
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
        global $app;
        if (empty($this->rechargeType)) {
            $this->rechargeType = 0;
        }
        $config_params = !empty($this->data['params']) ? json_decode($this->data['params'], true) : [];
        //请求参数 Request parameter
        $data = array(
            'mchNo' => $this->partnerID,
            'appId' => $this->pubKey,
            'mchOrderNo' => $this->orderID,
            'wayCode' => $this->rechargeType,
            'amount' => bcdiv($this->money, 100),//支付金额，整数
            'currency' => $config_params['currency'] ?? 'PHP',
            'subject' => 'recharge',
            'body' => 'recharge ' . bcdiv($this->money, 100),
            'notifyUrl' => $this->payCallbackDomain . '/pay/callback/mcopay',
            'reqTime' => time(),
            'version' => '1.0',
            'signType' => 'MD5',
        );

        $data['sign'] = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl .= '/api/anon/pay/unifiedOrder';
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
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->arrayToURL($this->parameter));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
        ]);
        $response = curl_exec($ch);
        $this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $this->re = $response;
    }


    //处理结果
    public function parseRE()
    {
        $result = json_decode($this->re, true);
        $status = isset($result['code']) ? $result['code'] : '';
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;
        is_array($message) && $message = json_encode($message);

        if ($this->http_code == 200) {
            //code=0 代表查询成功,其他代表错误代码
            if ($status === 0) {
                $code = 0;
                $targetUrl = $result['data']['payData'];
            } else {
                $code = 1;
                $targetUrl = '';
            }

            $this->return['code'] = $code;
            $this->return['msg'] = $message;
            $this->return['way'] = 'jump';
            $this->return['str'] = $targetUrl;
            $this->return['pay_no'] = $this->orderID;

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
    public function returnVerify($params = [])
    {
        $config = Recharge::getThirdConfig('mcopay');
        $this->key = $config['key'];

        //支付状态.2支付成功 ,3支付失败,其他支付中(默认只通知成功订单)
        if (!isset($params['state']) || $params['state'] != 2) {
            throw new \Exception('unpaid');
        }

        $res = [
            'status' => 0,
            'order_number' => $params['mchOrderNo'],
            'third_order' => $params['payOrderId'],
            'third_money' => $params['amount']*100,
            'third_fee' => 0,
            'error' => '',
        ];

        //检验状态
        if ($params['sign'] == $this->sign($params)) {
            if ($params['state'] == 2) {
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
        $str = '';
        foreach ($param as $k => $v) {
            if (is_null($v) || $v === '')
                continue;
            $str .= $k . '=' . $v . '&';
        }
        $sign_str = $str . 'key=' . $this->key;
        return strtoupper(md5($sign_str));
    }


    /**
     * 补单
     * @param $order_number
     * @return mixed
     * @throws \Exception
     */
    public function supplyOrder($order_number, $payNo = '')
    {
        $config = Recharge::getThirdConfig('mcopay');
        $this->key = $config['key'];
        $pay_no = \DB::table('funds_deposit')->where('trade_no')->value('pay_no');
        $data = [
            'mchNo' => $this->partnerID,
            'appId' => $this->pubKey,
            'payOrderId' => $pay_no,
            'mchOrderNo' => $this->orderID,
            'reqTime' => time(),
            'version' => '1.0',
            'signType' => 'MD5',
        ];

        $data['sign'] = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl = $config['payurl'] . '/api/pay/query';

        $this->formPost();
        $this->addStartPayLog();

        $result = json_decode($this->re, true);
        $code = isset($result['code']) ? $result['code'] : '';
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;

        if ($this->http_code == 200) {
            if ($code === 0) {
                //0-订单生成 1-支付中 2-支付成功 3-支付失败 6-订单关闭
                if ($result['data']['state'] != 2) {
                    throw new \Exception($message);
                }
                $res = [
                    'status' => $result['data']['state'],
                    'order_number' => $this->orderID,
                    'third_order' => $result['data']['payOrderId'],
                    'third_money' => $result['amount'] * 100,
                ];
                return $res;
            }
        }

        throw new \Exception('http_code:' . $this->http_code . ' code:' . $code . ' message:' . $message);
    }


}
