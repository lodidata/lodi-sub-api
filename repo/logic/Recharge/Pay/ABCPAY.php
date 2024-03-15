<?php

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\Utils;

/**
 *
 * abcpay
 */
class ABCPAY extends BASES
{
    public $http_code;

    static function instantiation()
    {
        return new ABCPAY();
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
        $rechargeType = 10029;
        if (!empty($this->rechargeType)) {
            $rechargeType = $this->rechargeType;
        }
        $data = [
            'app_id' => $this->key,
            'trade_no' => $this->orderID,
            'channel_id' => (int)$rechargeType,
            'money' => bcdiv($this->money, 100, 2),
            'is_delay' => 0,
            'ip' => $this->clientIp,
            'notify_url' => $this->payCallbackDomain . '/pay/callback/abcpay',
            'return_url' => $this->returnUrl ?: $this->payCallbackDomain,
        ];
        $data['sign'] = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl .= '/api/pay/create';
    }

    public function sign($data)
    {
        if (empty($data)) {
            return false;
        }
        unset($data['sign']);

        ksort($data);
        $str = urldecode(http_build_query($data));
        $str .= '&app_secret=' . $this->pubKey;
        return md5($str);
    }

    public function formGet()
    {
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

    //处理结果
    public function parseRE()
    {
        $result = json_decode($this->re, true);
        $status = isset($result['status']) ? $result['status'] : 'error';
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;
        if ($this->http_code == 200) {
            //下单成功，跳转支付链接 The order is successfully placed, jump to the payment link
            if ($status === true) {
                $targetUrl = $result['data']['url'];
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
            $this->return['pay_no'] = !empty($result['data']['sn']) ? $result['data']['sn'] : '';
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
        $config = Recharge::getThirdConfig('abcpay');
        $this->key = $config['key'];
        $this->pubKey = $config['pub_key'];

        $res = [
            'status' => 0,
            'order_number' => $param['trade_no'],
            'third_money' => $param['money'] * 100,
            'third_order' => $param['sn'],
            'third_fee' => 0,
            'error' => ''
        ];

        //检验状态
        if ($param['sign'] == $this->sign($param)) {
            if ((int)$param['status'] === 1) {
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

    }

}
