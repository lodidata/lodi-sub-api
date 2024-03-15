<?php

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;


class PHPGO extends BASES
{
    public $http_code;

    static function instantiation()
    {
        return new PHPGO();
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
        $rechargeType = 'PMP';
        if (!empty($this->rechargeType)) {
            $rechargeType = $this->rechargeType;
        }

        $data = array(
            'merchant' => $this->partnerID,
            'payment_type' => 3,
            'amount' => bcdiv($this->money, 100, 2),
            'order_id' => $this->orderID,
            'bank_code' => $rechargeType,
            'callback_url' => $this->payCallbackDomain . '/pay/callback/phpgopay',
            'return_url' => $this->returnUrl ?? 'noreturn',
        );

        $data['sign'] = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl .= '/api/transfer';
    }

    public function sign($param) {
        if(isset($param['sign'])) {
            unset($param['sign']);
        }
        ksort($param);
        $originalString='';

        foreach($param as $key=>$val){
            $originalString = $originalString . $key . "=" . $val . "&";
        }

        $originalString.= "key=" . $this->key;;
        return md5($originalString);
    }

    public function formPost() 
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($curl, CURLOPT_TIMEOUT, 15);
        curl_setopt($curl, CURLOPT_URL, $this->payUrl);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_POST, 1);
        $data = json_encode($this->parameter, JSON_UNESCAPED_UNICODE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
            'Content-Length:' . strlen($data) ,
            'Cache-Control: no-cache',
            'Pragma: no-cache'
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $this->re = curl_exec($curl);
        $this->http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        curl_close($curl);
    }

    //处理结果
    public function parseRE()
    {
        $result = json_decode($this->re, true);
        $status = isset($result['status']) ? $result['status'] : '';
        //message返回数组，这里做特殊处理
        $message = 'errorMsg:' . (string)$this->re;
        if (isset($result['message'])) {
            if (is_array($result['message'])) {
                $message = json_encode($result['message']);
            } else {
                $message = $result['message'];
            }
        }

        if ($this->http_code == 200) {
            //下单成功，跳转支付链接 The order is successfully placed, jump to the payment link
            if ($status == 1) {
                $code = 0;
                $targetUrl = $result['redirect_url'];
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
            $this->return['msg'] = 'http_code:' . $this->http_code . ' msg:' . $message;
            $this->return['way'] = 'jump';
            $this->return['str'] = $this->re;
        }
    }


    /*
     * $resArray 第三方通知数组
     * $deposit  系统流水信息
     * $vender   授权信息
     */
    public function returnVerify($param = [])
    {
        $config = Recharge::getThirdConfig('phpgo');
        $this->key = $config['key'];

        if (!isset($param['status']) || $param['status'] != 5) {
            throw new \Exception('unpaid');
        }
        
        $params = $param;
        $res = [
            'status' => 0,
            'order_number' => $params['order_id'],
            'third_order' => $params['order_id'],
            'third_money' => $params['amount'] * 100,
            'third_fee' => 0,
            'error' => '',
        ];

        //检验状态
        if ($param['sign'] == $this->sign($params)) {
            if ($params['status'] == 5) {
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
