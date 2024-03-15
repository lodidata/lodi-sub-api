<?php

namespace Logic\Transfer\ThirdParty;

use Logic\Recharge\Recharge;
use Utils\Curl;

/**
 *
 * TYPAY代付
 */
class TYPAY extends BASES {
    private $httpCode = '';

    //回调，不处理逻辑
    public function callbackMsg() {
        return 'success';
    }

    //请求代付接口
    public function runTransfer() {
        $config_params = !empty($this->thirdConfig['params']) ? json_decode($this->thirdConfig['params'],true) : [];

        $params = [
            "mchId"         => $this->partnerID,
            "amount"        => bcdiv($this->money, 100, 2),
            "accountRemark" => 'none',
            "mchOrderId"    => $this->orderID,
            "account"       => $this->bankCard,
            "accountOwner"  => $this->bankUserName,
            "currency"      => "CNY",
            "notifyUrl"     => $this->payCallbackDomain . '/thirdAdvance/callback/typay',
        ];
        $params['sign'] =$this->sign($params);

        $this->payUrl    .= '/export/taro/bill/create';
        $this->parameter = $params;

        $this->formPost();
        $result  = json_decode($this->re, true);
        //code 描述 200 成功 400 业务异常 具体错误详见 msg 字段
        $status  = isset($result['code']) ? $result['code'] : 400;
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;
        if($this->httpCode == 200) {
            if($status == 0) {
                $this->return['code']    = 10500;
                $this->return['balance'] = $this->money;
                $this->return['msg']     = $message;
                $this->transferNo        = $result['data']['mchOrderId'];//第三方订单号
                //成功就直接返回了
                return;
            }else{
                $message = 'http_code:' . $this->httpCode . 'errorMsg:' . json_encode($result, JSON_UNESCAPED_UNICODE);
                $this->updateTransferOrder($this->money, 0, '', '', 'failed', null, $message);
                $this->return['code']    = 886;
                $this->return['balance'] = 0;
                $this->return['msg']     = 'TYPAY:' . $message ?? '代付失败';
                return;
            }
        }

        $this->return['code']    = 886;
        $this->return['balance'] = $this->money;
        $this->return['msg']     = $message;
        $this->transferNo        = '';//第三方订单号
    }

    public function getThirdBalance() {
        $this->payUrl .= "/export/taro/user/balance/".$this->partnerID;

        $params = [
            'mchId'     => $this->partnerID,
        ];
        $this->parameter = $params;
        $this->formGET();
        $result  = json_decode($this->re, true);
        $code  = isset($result['code']) ? $result['code'] : 400;
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;

        if($this->httpCode == 200) {
            //code 描述 200 成功 400 业务异常 具体错误详见 msg 字段
            if($code == 0) {
                $this->return['code']    = 10509;
                $this->return['balance'] = bcmul($result['data'], 100, 0);
                $this->return['msg']     = $message;
                return;
            }
        }
        $this->_end();
        throw new \Exception('http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message);
    }

    public function getTransferResult() {
//        $this->payUrl    .= '/pay/query';

        $params = [
            'mchId'   => $this->partnerID,
            'orderNo' => $this->orderID,
        ];
        $params['sign'] = $this->sign($params);
        $this->parameter = $params;
        $this->formPost();
        $result  = json_decode($this->re, true);
        //code 描述 200 成功 400 业务异常 具体错误详见 msg 字段
        $code  = isset($result['code']) ? $result['code'] : 400;
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;

        if($this->httpCode == 200) {
            //code=0 代表查询成功,其他代表错误代码
            if($code == 200){
                //支付状态,0-订单生成,1-支付成功,2-支付失败
                if($result['data']['payStatus'] == 'Y') {
                    $status       = 'paid';
                    $this->return = ['code' => 1, 'msg' => $message];
                } elseif($result['data']['payStatus'] == 'N') {
                    $status       = 'failed';
                    $this->return = ['code' => 0, 'msg' => $message];
                } else {
                    $this->return = ['code' => 0, 'msg' => $message];
                    return;
                }

                $real_money = bcmul($result['data']['amount'], 100);
                $fee        = bcmul($result['data']['charge'], 100);
                $this->updateTransferOrder($this->money, $real_money, $result['data']['mchOrderId'],//第三方转账编号
                    '', $status, $fee, $message);
                return;
            }

        }
        $this->return = ['code' => 886, 'transfer' => '', 'msg' => 'http_code:' . $this->httpCode . ' message:' . $message];
    }

    //验证回调签名
    public function sign($data) {
        unset($data['sign']);
        unset($data['notifyUrl']);
        ksort($data);

        $originalString='';
        foreach($data as $key=>$val){
            if(is_null($val) || trim($val) == ''){
                continue;
            }
            $originalString = $originalString . $key . "=" . $val . "&";
        }
        $originalString.= "key=" . $this->key;
        return strtoupper(md5($originalString));
    }

    //验证回调签名
    public function callbackSign($data) {
        unset($data['sign']);
        unset($data['s']);
        unset($data['mchId']);
        unset($data['notifyUrl']);
        ksort($data);

        $originalString='';
        foreach($data as $key=>$val){
            if(is_null($val) || trim($val) == ''){
                continue;
            }
            $originalString = $originalString . $key . "=" . $val . "&";
        }
        $originalString.= "key=" . $this->key;
        return strtoupper(md5($originalString));
    }

    /**
     * 代付异步回调
     * @param array $params 异步参数
     * @return mixed
     * @throws \Exception
     */
    public function callbackResult($params) {
        $this->parameter = $params;

        if($this->callbackSign($params) != $params['sign']) {
            throw new \Exception('Sign error');
        }

        //订单状态已经成功 或者 消息与订单状态一致
        if($this->order['status'] == 'paid') {
            return;
        }

        //实际到账金额
        $real_money     = bcmul($this->parameter['amount'], 100);//以分为单位

        //支付状态,0-订单生成,1-支付成功,2-支付失败
        if($this->parameter['payStatus'] == 'Y') {
            $status       = 'paid';
            $this->return = ['code' => 1, 'msg' => ''];
        } elseif($this->parameter['payStatus'] == 'N') {
            $status       = 'failed';
            $this->return = ['code' => 0, 'msg' => '代付失败'];
        } else {
            $this->return = ['code' => 0, 'msg' => 'error'];
            return;
        }

        $this->re = $this->return;
        $this->updateTransferOrder($this->money, $real_money, $this->parameter['tradeNo'],//第三方转账编号
            '', $status,0,'');
    }

    public function formPost() {
        $ch = curl_init();
        $params_data = json_encode($this->parameter, JSON_UNESCAPED_UNICODE);
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
            'Content-Type: application/json;charset=UTF-8',
        ]);
        $response = curl_exec($ch);
        $this->httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $this->re = $response;
    }

    public function formGet() {
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
        $this->httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        //释放curl句柄
        curl_close($ch);
        $this->re = $output;
    }

    /**
     * 银行名称
     * @return mixed
     */
    private function getBankName() {

        $banks = [
            "Gcash"                                  => "478",
            "Paymaya Philippines, Inc."              => "939",
        ];
        return $banks[$this->bankCode];
    }
}
