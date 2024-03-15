<?php

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;

/**
 *
 * MOMOPAY
 * @author
 */
class MOMOPAY extends BASES
{
    public $http_code;

    static function instantiation()
    {
        return new MOMOPAY();
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
        if ($this->money % 100) {
            throw new \Exception('Transfer only supports integer');
        }
        $channelNo = 8;
        if (!empty($this->rechargeType)) {
            $channelNo = $this->rechargeType;
        }

        //请求参数 Request parameter
        $time = time();
        $data = array(
            'platformId' => $this->partnerID,//  是   string  商户号 business number
            'amount' => bcdiv($this->money, 100),
            'playerName' => $this->userId,//  是   string  用户id business number
            'realName' => '',
            'depositMethod' => $channelNo,
            'callbackUrl' => $this->payCallbackDomain . '/pay/callback/momopay',
            'proposalId' => $this->orderID,
            'clientType' => "0",
            'entryType' => "0",
            'createTime' => $time,
        );

        $data['sign'] = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl .= '/api/deposit-url';
    }


    //生成签名
    public function sign($data)
    {
        if (empty($data)) {
            return false;
        }
        unset($data['sign']);

        ksort($data);
        $data = array_filter($data, function ($val){
            return ($val !== "") && ($val !== 0) && ($val !== 'undefined');
        });

        $str = urldecode(http_build_query($data));
        return hash_hmac("sha256", $str, $this->key);
    }

    //处理结果
    public function parseRE()
    {
        $result = json_decode($this->re, true);
        $code = isset($result['status']) ? $result['status'] : 1;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;

        if ($code == 200) {
            //下单成功，跳转支付链接 The order is successfully placed, jump to the payment link
            $this->return['code'] = 0;
            $this->return['msg'] = $message;
            $this->return['way'] = 'jump';
            $this->return['str'] = $result['reqUrl'];
        } else {
            $this->return['code'] = 886;
            $this->return['msg'] = 'http_code:' . $this->http_code . ' msg: '. $message;
            $this->return['way'] = 'jump';
            $this->return['str'] = $this->re;
        }

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
            'platform:' . $this->partnerID
        ]);
        $response = curl_exec($ch);
        $this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->re = $response;
    }


    //回调数据校验
    /*
     * $resArray 第三方通知数组
     * $deposit  系统流水信息
     * $vender   授权信息
     * */
    public function returnVerify($param = [])
    {
        $config = Recharge::getThirdConfig('momopay');
        $this->key = $config['key'];
        $params = $param['content'];
        $res = [
            'status' => 0,
            'order_number' => $params['proposalId'],
            'third_order' => $params['billNo'],
            'third_money' => $params['amount'] * 100,
            'third_fee' => 0,
            'error' => '',
        ];
        if ($param['sign'] == $this->sign($params)) {
            if ($params['status'] == 'SUCCESS') {
                $res['status'] = 1;
            } else if ($params['status'] == 'PENDING') {
                throw new \Exception('{"code": 200, "message":"PENDING"}');
            } else {
                throw new \Exception('{"code": 200, "message":"unpaid"}');
            }
        } else {
            throw new \Exception('{"code": 200, "message":"sign is wrong"}');
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
