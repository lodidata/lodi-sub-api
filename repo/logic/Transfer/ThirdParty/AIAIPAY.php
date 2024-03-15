<?php

namespace Logic\Transfer\ThirdParty;

use Logic\Recharge\Recharge;

class AIAIPAY extends BASES {
    private $httpCode = '';

    //回调，不处理逻辑
    public function callbackMsg() {
        return 'success';
    }

    //请求代付接口
    public function runTransfer() {
        $third_uid = 15;
        $config_params = !empty($this->thirdConfig['params']) ? json_decode($this->thirdConfig['params'],true) : [];
        if(!empty($config_params) && isset($config_params['uid'])){
            $third_uid = $config_params['uid'];
        }
        //组装参数
        $data = [
            'uid'              => $third_uid, //
            'merchant_num'     => $this->partnerID,
            'order'            => $this->orderID,
            'coin'             => $this->money,
            'target_bank'      => $this->bankCard,
            'bank_name'        => $this->getBankName(),
            'target_bank_user' => $this->bankUserName,
            'type'             => 2,
            'order_date'       => date('Y-m-d H:i:s'),
            'notifyurl'        => $this->payCallbackDomain . '/thirdAdvance/callback/aiaipay',
        ];

        $data['sign'] = $this->sign($data);

        $this->payUrl      .= '/Order';
        $this->parameter   = $data;
        $this->basePostNew();
        $result = json_decode($this->re, true);
        $code    = isset($result['code']) ? $result['code'] : 0;
        $message = isset($result['data']['msg']) ? $result['data']['msg'] : 'errorMsg:' . (string)$this->re;
        if($this->httpCode == 200) {
            if($code == 1) {
                $this->return['code']    = 10500;
                $this->return['balance'] = $this->money;
                $this->return['msg']     = $message;
                $this->transferNo        = null;//第三方订单号
                //成功就直接返回了
                return;
            }else{
                $message = "curlError:{$this->curlError},http_code:{$this->httpCode},errorMsg:". json_encode($result, JSON_UNESCAPED_UNICODE);
                $this->updateTransferOrder($this->money, 0, '', '', 'failed', null, $message);
                $this->return['code']    = 886;
                $this->return['balance'] = 0;
                $this->return['msg']     = 'AIAIPAY:' . $message ?? '代付失败';
                return;
            }
        }

        $this->return['code']    = 886;
        $this->return['balance'] = $this->money;
        $this->return['msg']     = $message;
        $this->transferNo        = null;//第三方订单号
    }

    //查询余额
    public function getThirdBalance() {
        $third_uid = 15;
        $config_params = !empty($this->thirdConfig['params']) ? json_decode($this->thirdConfig['params'],true) : [];
        if(!empty($config_params) && isset($config_params['uid'])){
            $third_uid = $config_params['uid'];
        }
        $data         = [
            'uid'          => $third_uid,
            'merchant_num' => $this->partnerID,
            'date'         => date('Y-m-d H:i:s')
        ];
        $data['sign'] = $this->sign($data);

        $this->parameter = $data;
        $this->payUrl    .= "/Findcoin";
        $this->basePostNew();
        $result  = json_decode($this->re, true);
        $code    = isset($result['code']) ? $result['code'] : 0;
        $message = isset($result['msg']) ? $result['msg'] : 'errorMsg:' . (string)$this->re;

        if($this->httpCode == 200) {
            if($code == '1') {
                $this->return['code']    = 10509;
                $this->return['balance'] = $result['data']['coin'];
                $this->return['msg']     = $message;
                return;
            }
        }
        $this->_end();
        throw new \Exception('http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message);
    }

    //查询代付结果
    public function getTransferResult() {
        $third_uid = 15;
        $config_params = !empty($this->thirdConfig['params']) ? json_decode($this->thirdConfig['params'],true) : [];
        if(!empty($config_params) && isset($config_params['uid'])){
            $third_uid = $config_params['uid'];
        }
        $data         = [
            'uid'          => $third_uid,
            'merchant_num' => $this->partnerID,
            'order'        => $this->orderID,
            'order_date'   => date('Y-m-d H:i:s')
        ];
        $data['sign'] = $this->sign($data);

        $this->payUrl    .= '/Orderinfo';
        $this->parameter = $data;

        $this->basePostNew();

        $result = json_decode($this->re, true);
        $code   = isset($result['code']) ? $result['code'] : 0;

        $message = isset($result['data']['msg']) ? $result['data']['msg'] : 'errorMsg:' . (string)$this->re;

        if($this->httpCode == 200) {
            if($code == '1') {
                //订单状态 state (1 成功 2 驳回 3 处理中)
                if($result['data']['state'] == '1') {
                    $status       = 'paid';
                    $this->return = ['code' => 1, 'msg' => $message];
                } elseif($result['data']['state'] == '2') {
                    $status       = 'failed';
                    $this->return = ['code' => 0, 'msg' => $message];
                } else {
                    $status='pending';
                    $this->return = ['code' => 0, 'msg' => $message];
                }

                $real_money = $result['data']['success_coin'];
                $fee        = $this->money - $real_money;
                $this->updateTransferOrder($this->money, $real_money, $result['data']['serial_number'],//第三方转账编号
                    '', $status, $fee,$message);
                return;
            }
            //查不到订单 code为0
            if($code === 0){
                $this->updateTransferOrder($this->money, 0, '', '', 'failed', 0, $message);
                return;
            }
        }
        $this->return = ['code' => 886, 'transfer' => '', 'msg' => 'http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message];
    }

    public function basePostNew() {
        $this->payRequestUrl = $this->payUrl;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->payUrl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->parameter));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
        ]);

        $response        = curl_exec($ch);
        $this->curlError = curl_error($ch);
        $this->httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->re        = $response;
        curl_close($ch);
    }

    //生成签名
    public function sign($data) {
        unset($data['sign']);
        ksort($data);
        reset($data);

        $str = '';
        foreach($data as $k => $v) {
            if(is_null($v) || empty($v))
                continue;
            $str .= $k . '=' . $v . '&';
        }
        $str = trim($str, '&');

        $sign_str = $str . '&key=' . $this->key;
        $sign     = strtoupper(md5($sign_str));
        return $sign;
    }

    /**
     * 代付异步回调
     * @param array $params 异步参数
     * @return mixed
     */
    public function callbackResult($params) {
        $this->parameter = $params;

        if($params['sign'] != $this->sign($params)) {
            throw new \Exception('Sign error');
        }

        //订单状态已经成功 或者 消息与订单状态一致
        if($this->order['status'] == 'paid') {
            return;
        }

        $amount     = $params['coin'];//以分为单位
        $real_money = $amount;//实际到账金额

        //订单状态付款状态：(1、 付款中 2、 付款失败 3、 付款成功)
        if($params['code'] == 1) {
            $status       = 'paid';
            $this->return = ['code' => 1, 'msg' => ''];
        } else {
            $status       = 'failed';
            $this->return = ['code' => 0, 'msg' => $params['msg']];
        }

        $this->re = $this->return;
        $this->updateTransferOrder($this->money, $real_money, $params['serial_number'],//第三方转账编号
            '', $status,0,$params['msg']);
    }

    /**
     * 银行名称
     * @return mixed
     */
    private function getBankName() {

        $banks = [
            "AUB"     => "AUB",
            "BPI"     => "BPI",
            "BOC"     => "BOC",
            "CTBC"    => "CTBC",
            "CBS"     => "CBS",
            "DBI"     => "DBI",
            "EB"      => "EB",
            "Gcash"   => "GCASH",
            "GrabPay" => "GRABPAY",
            "Omnipay" => "OMNIPAY",
            "PBC"     => "PBC",
            "PNB"     => "PNB",
            "PSB"     => "PSB",
            "PBB"     => "PBB",
            "PTC"     => "PTC",
            "RB"      => "RB",
            "SBC"     => "SBC",
            "SCB"     => "SCB",
            "STP"     => "STP",
            "SSB"     => "SSB",
            "Maybank Philippines, Inc."             => "MAYBANK",
            "Partner Rural Bank (Cotabato), Inc."   => "PRB",
            "Starpay"   => "STP",
        ];
        return $banks[$this->bankCode];
    }

}
