<?php

namespace Las\Pay;

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;

/**
 *
 * 安宝付
 * @author
 */
class LPAY extends BASES {

    static function instantiation() {
        return new LPAY();
    }

    //与第三方交互
    public function start() {
        $this->initParam();  // 数据初始化
        $this->formPost();  // 发送请求
        $this->parseRE();  // 处理结果
    }

    //组装数组
    public function initParam() {
        $params = array(
            'merchant_ref'      => $this->orderID,//是	string	商户订单号 Merchant order number
            'product'           => 'GCash',//	是 产品名称 product name ThaiQR	THB	泰国二维码   ThaiP2P	THB	泰国转账 ...
            'amount'            => bcdiv($this->money, 100, 2),//	是	string	金额，单位，保留 2 位小数 Amount, unit, 2 decimal places
            //'extra'           => $extra,//	否	Object	额外参数， 默认为json字符串 {} Extra parameters, the default is json string {}
            //'language'        => '',//	否	string	收银台语言选择（详细请看语言代码） Cashier language selection (please see language code for details)
        );

        //extra 参数, 可选字段 extra parameter, optional field
        $extra = array(
            //	否	string	玩家付款账号【需同时传递 bank_code 字段】 Player payment account [need to pass the bank_code field]
            //'account_no' => '1234567890',

            //	否	string	玩家付款银行代码（详细请看银行代码）【需同时传递 account_no 字段】
            //Player's payment bank code (please see bank code for details) [need to pass the account_no field at the same time]
            //'bank_code' => 'KBANK',
        );

        //判断 额外参数是否为空 Determine whether the extra parameter is empty
        if ($extra) {
            $params['extra'] = $extra;
        }

        //转换json串 Convert json string
        $params_json = json_encode($params,320);

        //请求参数 Request parameter
        $data = array(
            'merchant_no'       => $this->partnerID,//	是	string	商户号 business number
            'timestamp'         => time(),//	是	integer	发送请求的 10 位时间戳 10-bit timestamp of sending request
            'sign_type'         => 'MD5',//	是	string	默认为 MD5 Default is MD5
            'params'            => $params_json,//	是	string	请求业务参数组成的 JSON String；若接口对应的业务参数不需要字段传输，该字段的值可为空字符串
        );

        $data['sign'] = $this->sign($data);  //校验码
        $this->parameter = $data;
        $this->payUrl .= '/api/gateway/pay';
    }

    //生成签名
    public function sign($data) {
        //组装签名字段 签名 MD5(merchant_no+params+sign_type+timestamp+Key)-说明key 是商户秘钥
        //Assemble the signature field Signature MD5 (merchant_no+params+sign_type+timestamp+Key)-indicating that the key is the merchant secret key
        $merchant_no = isset($data['merchant_no']) ? $data['merchant_no'] : '';
        $params = isset($data['params']) ? $data['params'] : '';
        $sign_type = isset($data['sign_type']) ? $data['sign_type'] : '';
        $timestamp = isset($data['timestamp']) ? $data['timestamp'] : '';

        $sign_str = $merchant_no . $params . $sign_type . $timestamp . $this->key;
        $sign = md5($sign_str);//MD5签名 不区分大小写  MD5 signature is not case sensitive
        return $sign;

    }

    //处理结果
    public function parseRE() {
        $result = json_decode($this->re, true);
        $code = isset($result['code']) ? $result['code'] : 404;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:'.(string)$this->re;
        if ($code == 200) {
            //下单成功，跳转支付链接 The order is successfully placed, jump to the payment link
            $params = json_decode($result['params'],true);
            $payurl = isset($params['payurl']) ? $params['payurl'] : '';//支付链接 Payment link
            //header("Location:{$payurl}");//跳转支付链接 Jump payment link
           //$qrcode = isset($params['qrcode']) ? $params['qrcode'] : '';

            $this->return['code'] = 0;
            $this->return['msg'] = 'SUCCESS';
            $this->return['way'] = 'jump';
            $this->return['str'] = $payurl;
        } else {
            $this->return['code'] = 886;
            $this->return['msg'] = 'LPAY:' . $message ?? '下单失败';
            $this->return['way'] = 'jump';
            $this->return['str'] = $this->re;
        }

    }

    public function formPost() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_URL, $this->payUrl);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->arrayToURL($this->parameter));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
        ]);
        $response = curl_exec($ch);
        $this->re = $response;

    }


    //回调数据校验
    /*
     * $resArray 第三方通知数组
     * $deposit  系统流水信息
     * $vender   授权信息
     * */
    public function returnVerify($param = []) {
        $params = json_decode($param['params'], true);
        $res = [
            'status'       => 0,
            'order_number' => $params['merchant_ref'],
            'third_order'  => $params['system_ref'],
            'third_money'  => $params['pay_amount'] * 100,
            'third_fee'    => $params['fee'] * 100,
            'success_time'  => $params['success_time'],
            'error'        => '',
        ];
        $config = Recharge::getThirdConfig('lpay');
        $this->key = $config['key'];
        if ($param['sign'] == $this->sign($param)) {
            if ($params['status'] == 1) {
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
    public function supplyOrder($order_number)
    {
        $config = Recharge::getThirdConfig('lpay');
        $this->key = $config['key'];

        $params = array(
            'merchant_ref'      => $order_number,//是	string	商户订单号 Merchant order number
            'product_ref'       => $order_number,//	是 产品名称 product name ThaiQR	THB	泰国二维码   ThaiP2P	THB	泰国转账 ...
        );

        //转换json串 Convert json string
        $params_json = json_encode($params,320);

        //请求参数 Request parameter
        $data = array(
            'merchant_no'       => $this->partnerID,//	是	string	商户号 business number
            'timestamp'         => time(),//	是	integer	发送请求的 10 位时间戳 10-bit timestamp of sending request
            'sign_type'         => 'MD5',//	是	string	默认为 MD5 Default is MD5
            'params'            => $params_json,//	是	string	请求业务参数组成的 JSON String；若接口对应的业务参数不需要字段传输，该字段的值可为空字符串
        );

        $data['sign']            = $this->sign($data);  //校验码
        $this->parameter = $data;

        $this->payUrl = $config['payurl'].'/api/gateway/supply';
        $this->formPost();
        $this->addStartPayLog();
        $result = json_decode($this->re, true);
        $code = isset($result['code']) ? $result['code'] : 404;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:'.(string)$this->re;
        if ($code == 200) {
            //下单成功，跳转支付链接 The order is successfully placed, jump to the payment link
            $params = json_decode($result['params'],true);
            $payurl = isset($params['payurl']) ? $params['payurl'] : '';//支付链接 Payment link
            //header("Location:{$payurl}");//跳转支付链接 Jump payment link
            //$qrcode = isset($params['qrcode']) ? $params['qrcode'] : '';

            $this->return['code'] = 0;
            $this->return['msg'] = 'SUCCESS';
            $this->return['data'] = [
                'status'       => $params['status'],
                'order_number' => $params['merchant_ref'],
                'third_order'  => $params['system_ref'],
                'third_money'  => $params['amount'] * 100,
                'third_fee'    => $params['fee'] * 100,
                'success_time'  => $params['success_time']
            ];
        } else {
            $this->return['code'] = 886;
            $this->return['msg'] = 'LPAY:' . $message ?? '查询失败';
            $this->return['data'] = $this->re;
        }
        return $this->return;
    }
}
