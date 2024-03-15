<?php

namespace Logic\Transfer\ThirdParty;

/**
 *
 * BIGPAY代付
 */
class BIGPAY extends BASES
{
    private $httpCode = '';

    //回调，不处理逻辑
    public function callbackMsg(){
        return 'OK';
    }

    //请求代付接口
    public function runTransfer()
    {
        $params = array(
            'merchant_ref'      => $this->orderID,//是	string	商户订单号 Merchant order number
            'product'           => 'ThaiPayout',//	是 产品名称 product ThaiPayout ThaiSettlement
            'amount'            => bcdiv($this->money, 100, 2),//	是	string	金额，单位，保留 2 位小数 Amount, unit, 2 decimal places
            //'extra'           => $extra,//	否	Object	额外参数， 默认为json字符串 {} Extra parameters, the default is json string {}
            //'extend_params'        => '',//	否 扩展字段
        );

        //extra 参数, 可选字段 extra parameter, optional field
        $extra = array(
            'account_name'  => $this->bankUserName, //持卡人
            'account_no' => $this->bankCard,//银行卡号
            'bank_code' => $this->bankCode,//提现银行代码(印度为IFSC码、UPI定值，巴西为(CPF、PHONE、EMAIL、RANDOM_CHARACTER))
        );

        //判断 额外参数是否为空 Determine whether the extra parameter is empty
        if ($extra) {
            $params['extra'] = $extra;
        }
        $this->initParam($params);

        $this->basePostNew('/api/gateway/withdraw');
        $result = json_decode($this->re, true);
        $code = isset($result['code']) ? $result['code'] : 404;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:'.(string)$this->re;
        if ($code == 200) {
            $params = json_decode($result['params'],true);
            $this->return['code'] = 10500;
            $this->return['balance'] = $this->money;
            $this->return['msg'] = '';
        } else {
            $this->updateTransferOrder(
                $this->money,
                0,
                '',
                '',
                'failed',
                null,
                $message
            );
            $this->return['code'] = 886;
            $this->return['balance'] = 0;
            $this->return['msg'] = 'BIGPAY:' . $message ?? '查询失败';
        }
    }


    //查询余额
    public function getThirdBalance()
    {
        $params = array(
            'currency' => 'THB'
        );
        $this->initParam($params);
        $this->basePostNew ('/api/gateway/query/balance');

        $result = json_decode($this->re, true);
        $code = isset($result['code']) ? $result['code'] : 404;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:'.(string)$this->re;
        if ($code == 200) {
            $params = json_decode($result['params'],true);
            $this->return['code'] = 10509;
            $this->return['balance'] = $params['current_balance']*100;
            $this->return['msg'] = '';
        } else {
            $this->return['code'] = 886;
            $this->return['msg'] = 'BIGPAY:' . $message ?? '查询失败';
            $this->return['balance'] = 'balance';
        }
    }

    //查询代付结果
    public function getTransferResult()
    {
        $params = [
            'merchant_refs' => [$this->orderID]
        ];
        $this->initParam($params);
        $this->basePostNew('/api/gateway/batch-query/order');
        /*$this->re = <<<EOL
{"code":200,"message":"","timestamp":1640335256,"params":"[{"merchant_ref":"1224041309100616734","system_ref":"P1640333589WYFM4","amount":"100.00","pay_amount":"100.00","fee":"3.50","status":5,"success_time":null,"extend_params":"","product":"ThaiPayout"}]"}
EOL;*/

        $re = json_decode($this->re, true);
        $code = isset($re['code']) ? $re['code'] : 404;
        $pay_no = '';
        $real_money = $this->money;
        $fee = null;
        $success_time = '';
        if ($code == 200) {
            $params = json_decode($re['params'],true);
            $params = $params[0];
            $pay_no = $params['system_ref'];
            $real_money = $params['pay_amount']*100;//以分为单位
            $fee = $params['fee'];
            $success_time = date('Y-m-d H:i:s', $params['success_time']);
            //存款订单状态: 0：Unpaid；1：Paid；出金订单状态：1: Success； 2: Pending；5: Reject
            switch ($params['status']) {
                case '1'://交易成功
                    $status = 'paid';
                    break;
                case '5'://交易失败
                    $status = 'failed';
                    break;
                default:
                    $status = 'pending';
                    break;
            }
        }else{
            $status = 'pending';//支付状态设置的宽泛一些！
        }

        $message = isset($re['message']) ? $re['message'] : 'errorMsg:'.(string)$this->re;
        if ($status == 'paid') {//支付成功
            $message = '代付成功';
            $this->return = ['code' => 10508, 'balance' => $real_money, 'msg' => ''];
        } else {
            $real_money = 0;
            $message = $status == 'pending' ? '代付中-' . $message : '代付失败-' . $message;
            $this->return = ['code' => 886, 'balance' => 0, 'msg' => 'BIGPAY:' . $message];
        }
        if(in_array($status, ['paid', 'failed'])){
            $this->updateTransferOrder(
                $this->money,
                $real_money,
                $pay_no,
                $success_time,
                $status,
                $fee,
                $message
            );
        }
    }

    //组装数组
    public function initParam($params)
    {
        //转换json串 Convert json string
        $params_json = json_encode($params,320);
        //请求参数 Request parameter
        $data = array(
            'merchant_no'       => $this->partnerID,//	是	string	商户号 business number
            'timestamp'         => time(),//	是	integer	发送请求的 10 位时间戳 10-bit timestamp of sending request
            'sign_type'         => 'MD5',//	是	string	默认为 MD5 Default is MD5
            'params'            => $params_json,//	是	string	请求业务参数组成的 JSON String；若接口对应的业务参数不需要字段传输，该字段的值可为空字符串
        );

        $data['sign']           = $this->sign($data);  //校验码
        $this->parameter = $data;
    }

    /**
     * 获取代付平台 的银行code
     */

    //生成签名
    public function sign($data)
    {
        $merchant_no = isset($data['merchant_no']) ? $data['merchant_no'] : '';
        $params = isset($data['params']) ? $data['params'] : '';
        $sign_type = isset($data['sign_type']) ? $data['sign_type'] : '';
        $timestamp = isset($data['timestamp']) ? $data['timestamp'] : '';

        $sign_str = $merchant_no . $params . $sign_type . $timestamp . $this->key;
        $sign = md5($sign_str);//MD5签名 不区分大小写  MD5 signature is not case sensitive
        return $sign;
    }




    public function basePostNew($str, $referer = null)
    {
        $this->payRequestUrl = $this->payUrl . $str;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->payUrl . $str);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->parameter));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($referer) {
            curl_setopt($ch, CURLOPT_REFERER, $referer);
        }
        $response = curl_exec($ch);
        $this->curlError = curl_error($ch);
        $this->httpCode = curl_getinfo($ch,CURLINFO_HTTP_CODE);
        $this->re = $response;
        curl_close($ch);

    }

    /**
     * 代付异步回调
     * @param array $params 异步参数
     * @return mixed
     */
    public function callbackResult($params)
    {
        // TODO: Implement callbackResult() method.
    }
}
