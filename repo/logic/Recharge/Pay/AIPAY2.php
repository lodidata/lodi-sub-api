<?php

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;

/**
 *
 * AIPAY2
 * @author
 */
class AIPAY2 extends BASES
{
    public $http_code;

    static function instantiation()
    {
        return new AIPAY2();
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
            $this->rechargeType = '21001';
        }
        $data = array(
            'mer_no'       => $this->partnerID,
            'order_no'     => $this->orderID,
            'order_amount' => bcdiv($this->money, 100, 2),
            'payname'      => 'test',
            'payemail'     => 'xiaoming@email.com',
            'payphone'     => '987654321',
            'currency'     => 'PHP',
            'paytypecode'  => $this->rechargeType,
            'method'       => 'trade.create',
        );

        $data['returnurl'] = $this->payCallbackDomain . '/pay/callback/aipay2';

        $data['sign'] = $this->sign($data);

        $this->parameter = $data;
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
            'Content-Length:' . strlen($post_data),
            'Cache-Control: no-cache',
            'Pragma: no-cache'
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
        $status = isset($result['status']) ? $result['status'] : 'fail';
        $message = isset($result['status_mes']) ? $result['status_mes'] : 'errorMsg:' . (string)$this->re;
        $code = 1;
        if ($this->http_code == 200) {
            //下单成功，跳转支付链接 The order is successfully placed, jump to the payment link
            if ($status == 'success') {
                $code = 0;
                $targetUrl = $result['order_data'] ?? '';
            } else {
                $targetUrl = '';
            }

            $this->return['code'] = $code;
            $this->return['msg'] = $message;
            $this->return['way'] = 'jump';
            $this->return['str'] = $targetUrl;
            $this->return['pay_no'] = $result['order_no'] ?? '';
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
        $config = Recharge::getThirdConfig('aipay2');
        $this->pubKey = $config['pub_key'];

        $res = [
            'status'       => 0,
            'order_number' => $param['order_no'],
            'third_order'  => '',
            'third_money'  => $param['order_realityamount'] * 100,
            'third_fee'    => 0,
            'error'        => '',
        ];

        //检验状态
        if ($this->signVerify($param)) {
            if ($param['status'] === 'success') {
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
        unset($data['sign']);
        ksort($data);
        reset($data);

        $str = '';
        foreach ($data as $k => $v) {
            if (is_null($v) || $v === '') continue;
            $str .= $k . '=' . $v . '&';
        }
        $str = trim($str, '&') . $this->key;
        return md5($str);
    }


    //回调校验签名
    public function signVerify($data)
    {
        $sign = $data['sign'];
        unset($data['sign']);
        ksort($data);
        reset($data);

        $str = '';
        foreach ($data as $k => $v) {
            if (is_null($v) || $v === '') continue;
            $str .= $k . '=' . $v . '&';
        }

        $str = trim($str, '&') . $this->pubKey;

        $sign_new = md5($str);

        if ($sign === $sign_new) {
            return true;
        }
        return false;
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
