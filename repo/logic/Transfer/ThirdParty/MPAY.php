<?php

namespace Logic\Transfer\ThirdParty;

use Logic\Recharge\Recharge;
use Utils\Curl;

/**
 *
 * MPay代付
 */
class MPAY extends BASES {
    private $httpCode = '';

    //回调，不处理逻辑
    public function callbackMsg() {
        return 'success';
    }

    //请求代付接口
    public function runTransfer() {
        $params          = [
            "merchNo"   => $this->partnerID, //商户号
            "amount"    => (int)$this->money,   //支付金额
            "orderNo"   => $this->orderID,   //订单号
            "reqTime"   => time(),
            'bankCode'  => $this->getBankName(),
            'acctName'  => $this->bankUserName,
            'acctNo'    => $this->bankCard,
            'notifyUrl' => $this->payCallbackDomain . '/thirdAdvance/callback/mpay',
        ];
        $this->parameter = $params;
        //判断 额外参数是否为空 Determine whether the extra parameter is empty
        $this->payUrl .= '/api/add_payout_order';

        $this->initParam($params);
        $this->basePostNew();
        $result  = isset($this->re) ? json_decode($this->re, true) : '';
        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;
        if($this->httpCode == 200) {
            //true成功,false失败
            if($code === 0) {
                $this->return['code']    = 10500;
                $this->return['balance'] = $this->money;
                $this->return['msg']     = $message;
                $this->transferNo        = $result['data']['businessNo'];   //第三方订单号
                //成功就直接返回了
                return;
            }else{
                $message = 'http_code:' . $this->httpCode . 'errorMsg:' . json_encode($result, JSON_UNESCAPED_UNICODE);
                $this->updateTransferOrder($this->money, 0, '', '', 'failed', null, $message);
                $this->return['code']    = 886;
                $this->return['balance'] = 0;
                $this->return['msg']     = 'MPAY:' . $message ?? '代付失败';
                return;
            }
        }

        $this->return['code']    = 886;
        $this->return['balance'] = $this->money;
        $this->return['msg']     = $message;
        $this->transferNo        = '';   //第三方订单号
    }

    //查询余额
    public function getThirdBalance() {
        $params       = [
            'merchNo' => $this->partnerID,
        ];
        $this->payUrl .= "/api/query_payout_balance";
        $this->initParam($params);
        $this->basePostNew();
        $result  = json_decode($this->re, true);
        $code    = isset($result['code']) ? $result['code'] : 1;
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;

        if($this->httpCode == 200) {
            if($code === 0) {
                $this->return['code']    = 10509;
                $this->return['balance'] = $result['data']['balance'];
                $this->return['msg']     = 'success';
                return;
            }
        }
        $this->_end();
        throw new \Exception('http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message);
    }

    //代付订单查询
    public function getTransferResult() {
        $params       = [
            'merchNo' => $this->partnerID,
            'orderNo' => $this->orderID
        ];
        $this->payUrl .= "/api/query_payout_order";
        $this->initParam($params);
        $this->basePostNew();

        $result  = json_decode($this->re, true);
        if(!$result['data']){
            $code = 1;
            $message = 'errorMsg:' . (string)$this->re;
        }else{
            $result = $result['data'];
            $code    = isset($result['orderState']) ? $result['orderState'] : 1;
            $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;
        }

        if($this->httpCode == 200) {
            //订单状态：0 处理中 1 成功 2 失败
            if($code == '0') {
                $status       = 'paid';
                $this->return = ['code' => 1, 'msg' => $message];
            } elseif($code == '4') {
                $status       = 'failed';
                $this->return = ['code' => 0, 'msg' => $message];
            } else{
                $status       = "pending";
                $this->return = ['code' => 0, 'msg' => $message];
            }

            $real_money = $result['amount'];
            $fee        = $this->money - $real_money;
            $this->updateTransferOrder($this->money, $real_money, $result['businessNo'], '', $status, $fee, $message);
            return;
        }

        $this->return = ['code' => 886, 'transfer' => '', 'msg' => 'http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message];
    }

    //组装数组
    public function initParam($params = []) {
        $data['data']    = $params;
        $data['sign']    = $this->sign($params);  //校验码
        $data['signType'] = 'md5';
        $this->parameter = $data;
    }

    //验证回调签名
    public function sign($data) {
        $str = '';
        ksort($data);

        foreach($data as $k => $v) {
            if($v === '' || $k == 'sign') {
                continue;
            }
            $str .= $k . "=" . $v . "&";
        }
        $str     = rtrim($str, '&');
        $signStr = $str . '&key='.$this->key;
        return md5($signStr);
    }

    public function basePostNew($referer = null) {
        $this->payRequestUrl = $this->payUrl;
        $params_data = json_encode($this->parameter, JSON_UNESCAPED_UNICODE);
        $ch          = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->payUrl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        if($referer) {
            curl_setopt($ch, CURLOPT_REFERER, $referer);
        }
        $response        = curl_exec($ch);
        $this->curlError = curl_error($ch);
        $this->httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->re        = $response;
        curl_close($ch);
    }

    /**
     * 代付异步回调
     * @param array $params 异步参数
     * @return mixed
     */
    public function callbackResult($params) {
        $this->parameter = $params;
        $item = $params['data'];

        if($this->sign($item) != $params['sign']) {
            throw new \Exception('Sign error');
        }

        //订单状态已经成功 或者 消息与订单状态一致
        if($this->order['status'] == 'paid') {
            return;
        }
        $real_money = $item['amount'];//实际到账金额

        //订单状态付款状态：(//订单状态：0 处理中 1 成功 2 失败)
        $message = isset($item['reason']) ? $item['reason'] : '';
        if($item['orderState'] == '0') {
            $status       = 'paid';
            $this->return = ['code' => 1, 'msg' => ''];
        } elseif($item['orderState'] == '4') {
            $status       = 'failed';
            $msg = isset($item['reason']) ? $item['reason'] : "代付失败";
            $this->return = ['code' => 0, 'msg' => $msg];
        } else {
            $this->return = ['code' => 0, 'msg' => 'error'];
            return;
        }

        $this->re = $this->return;
        $this->updateTransferOrder($this->money, $real_money, $item['businessNo'],//第三方转账编号
            '', $status, 0, $message);
    }

    /**
     * 银行名称
     * @return mixed
     */
    private function getBankName() {
        //查询银行代码
        $code_num = '';
        if(!empty($this->bankCode)){
            $bank_info = \DB::table('bank')->where('code',$this->bankCode)->first();
            if(!empty($bank_info)){
                $code_num = $bank_info->code_num;
            }
        }

        return $code_num;
    }
}
