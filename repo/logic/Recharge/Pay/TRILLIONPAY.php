<?php

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\Utils;

/**
 *
 * trillionpay
 */
class TRILLIONPAY extends BASES
{
    public $http_code;

    static function instantiation()
    {
        return new TRILLIONPAY();
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
        $rechargeType = 5;
        if (!empty($this->rechargeType)) {
            $rechargeType = $this->rechargeType;
        }

        $data = array(
            'pid'          => $this->partnerID,
            'out_trade_no' => $this->orderID,
            'type'         => $rechargeType,
            'notify_url'   => $this->payCallbackDomain . '/pay/callback/trillionpay',
            'return_url'   => $this->returnUrl ?: 'noreturn',
            'userid'       => $this->userId,
            'userip'       => $this->clientIp,
            'money'        => bcdiv($this->money, 100, 2)
        );

        $data['sign'] = $this->sign($data);
        $this->parameter = $data;
        $this->payUrl .= '/mobile/chongzhi/payment';
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
        curl_setopt_array($curl, array(
            CURLOPT_URL            => $this->payUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => $this->parameter,
        ));

        $response = curl_exec($curl);
        $this->http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $this->re = $response;
    }


    //处理结果
    public function parseRE()
    {
        $result = json_decode($this->re, true);
        $code = isset($result['resultCode']) ? $result['resultCode'] : 1;
        $message = isset($result['resultMsg']) ? $result['resultMsg'] : 'errorMsg:' . (string)$this->re;
        if ($this->http_code == 200) {
            //下单成功，跳转支付链接 The order is successfully placed, jump to the payment link
            if ($code == 200) {
                $code = 0;
                $targetUrl = $result['payUrl'];
            } else {
                $code = 1;
                $targetUrl = '';
            }

            $this->return['code'] = $code;
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
        $config = Recharge::getThirdConfig('trillionpay');
        $this->key = $config['key'];

        if (!isset($param['resultCode']) || $param['resultCode'] != 1001) {
            throw new \Exception('unpaid');
        }
        $params = $param;

        $res = [
            'status'       => 0,
            'order_number' => $params['out_trade_no'],
            'third_order'  => $params['orderNum'],
            'third_money'  => $params['payMoney'] * 100,
            'third_fee'    => 0,
            'error'        => '',
        ];

        //检验状态
        if ($param['sign'] == $this->sign($params)) {
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
        foreach ($data as $key => $value) {
            $str .= $key . '=' . $value . '&';
        }
        $str .= 'key=' . $this->key;
        return md5(md5(md5($str)));
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
