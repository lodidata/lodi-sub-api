<?php

namespace Logic\Transfer\ThirdParty;

use Logic\Recharge\Recharge;

class RUSPAY extends BASES {
    private $httpCode = '';

    //回调，不处理逻辑
    public function callbackMsg() {
        return 'success';
    }

    //请求代付接口
    public function runTransfer() {
        $channelId = 187;
        $config_params = !empty($this->thirdConfig['params']) ? json_decode($this->thirdConfig['params'],true) : [];
        if(!empty($config_params) && isset($config_params['channel_id'])){
            $channelId = $config_params['channel_id'];
        }

        //组装参数
        $data                 = [
            'mch_id'            => $this->partnerID,
            'channel_id'        => $channelId,
            'order_sn'          => $this->orderID,
            'amount'            => bcdiv($this->money,100),          //整数，单位元
            'payer_ifsc'        => 'no',
            'payer_account'     => trim($this->bankCard),                  //银行名称
            'payer_name'        => $this->bankUserName,                    //银行帐号
            'notify_url'        => $this->payCallbackDomain . '/thirdAdvance/callback/ruspay'
        ];
        $data['sign'] = $this->sign($data);

        $this->payUrl         .= '/api/Pay/addPayOut';
        $this->parameter      = $data;
        $this->basePostNew();

        $result = json_decode($this->re, true);

        $code = isset($result['code']) ? $result['code'] : 20000;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;
        if($this->httpCode == 200) {
            if ($code == 20000) {
                $this->return['code']    = 10500;
                $this->return['balance'] = $this->money;
                $this->return['msg']     = $message;
                $this->transferNo        = $this->orderID;//第三方订单号
                //成功就直接返回了
                return;
            } else {
                $message = 'http_code:' . $this->httpCode . 'errorMsg:' . json_encode($result, JSON_UNESCAPED_UNICODE);
                $this->updateTransferOrder($this->money, 0, '', '', 'failed', null, $message);
                $this->return['code']    = 886;
                $this->return['balance'] = 0;
                $this->return['msg']     = 'ruspay:' . $message ?? '代付失败';
                return;
            }
        }

        $this->return['code']    = 886;
        $this->return['balance'] = $this->money;
        $this->return['msg']     = $message;
        $this->transferNo        = '';//第三方订单号
    }

    //查询余额
    public function getThirdBalance() {
        $params                 = [
            'merchant_id' => $this->partnerID,
        ];

        $this->parameter = $params;

        $this->payUrl .= "/api/PayoutOrder/getMerchantBalance";
        $this->formGet();
        $result  = json_decode($this->re, true);

        $code    = isset($result['code']) ? $result['code'] : 20000;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;

        if ($code == 20000) {
            $this->return['code']    = 10509;
            $this->return['balance'] = bcmul($result['result']['total_valid_withdrawamount'], 100);
            $this->return['msg']     = $message;
            return;
        }

        $this->_end();
        throw new \Exception('http_code:' . $this->httpCode . ' code:' . $code . ' message:' . $message);
    }

    //查询代付结果
    public function getTransferResult() {
        $data                 = [
            'merchant_id' => $this->partnerID,
            'merchant_order' => $this->orderID,
        ];

        $this->payUrl    .= '/api/PayoutOrder/getPayOutOrder';
        $this->parameter = $data;

        $this->formGet();

        $result = json_decode($this->re, true);

        $code    = isset($result['code']) ? $result['code'] : 20000;
        $message = isset($result['message']) ? $result['message'] : 'errorMsg:' . (string)$this->re;

        if($this->httpCode == 200 && $code == 20000) {
            //订单状态 status (0 处理中；1 出款成功；2 出款拒绝)
            $third_no = $this->orderID;
            if($result['result']['status']==1) {
                $status       = 'paid';
                $this->return = ['code' => 1, 'msg' => $message];
            } elseif($result['result']['status']==2) {
                $status       = 'failed';
                $this->return = ['code' => 0, 'msg' => $message];
            } else {
                $this->return = ['code' => 0, 'msg' => $message];
                return;
            }

            $real_money = bcmul($result['result']['money'], 100);
            $fee        = $this->money - $real_money;
            $this->updateTransferOrder($this->money, $real_money, $third_no,//第三方转账编号
                '', $status, $fee, $message);
            return;
        }
        $this->return = ['code' => 886, 'transfer' => '', 'msg' => 'http_code:' . $this->httpCode . ' message:' . $message];
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
            'Content-Length: ' . strlen($params_data)
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

    public function formGet() {
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
        $this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        //释放curl句柄
        curl_close($ch);
        $this->re = $output;
    }

    //生成签名
    public function sign($data)
    {
        unset($data['sign']);
        ksort($data);

        $str = '';
        foreach ($data as $k => $v) {
            if(is_null($v) || $v === '') continue;     //值为 null 则不加入签名
            $str .= $k . '=' . $v . '&';
        }

        $sign_str = $str . 'key=' . $this->key;

        return strtolower(md5($sign_str));
    }

    /**
     * 代付异步回调
     * @param array $params 异步参数
     * @return mixed
     */
    public function callbackResult($params) {
        $this->parameter = $params;
        $message = $params['message'];

        $param = $params;

        $signData['mch_id'] = $param['mch_id'];
        $signData['channel_id'] = $param['channel_id'];
        $signData['amount'] = intval($param['amount']);
        $signData['order_sn'] = $param['order_sn'];
        $signData['payer_ifsc'] = $param['payer_ifsc'];
        $signData['payer_account'] = $param['payer_account'];
        $signData['payer_name'] = $param['payer_name'];
        $signData['notify_url'] = $this->payCallbackDomain . '/thirdAdvance/callback/ruspay';

        if($this->sign($signData) != $params['sign']) {
            throw new \Exception('Sign error');
        }

        //订单状态已经成功 或者 消息与订单状态一致
        if($this->order['status'] == 'paid') {
            return;
        }
        $amount     = bcmul($params['amount'], 100);//以分为单位
        $real_money = $amount;//实际到账金额

        //订单状态 status (0 失败；1 支付成功)
        if($params['tradeResult']==1) {
            $status       = 'paid';
            $this->return = ['code' => 1, 'msg' => $message];
        } elseif($params['tradeResult']==0) {
            $status       = 'failed';
            $this->return = ['code' => 0, 'msg' => $message];
        } else {
            $this->return = ['code' => 0, 'msg' => $message];
            return;
        }

        $this->re = $this->return;
        $this->updateTransferOrder($this->money, $real_money, $params['orderId'],//第三方转账编号
            '', $status, 0, $message);
    }
}